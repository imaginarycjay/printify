<?php

namespace App\Models;

use App\Services\PrintServiceCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $is_setup_completed
 * @property string $approval_status
 * @property string|null $rejection_reason
 * @property string|null $address
 * @property string|null $contact
 * @property string|null $description
 * @property int|null $file_retention_days
 * @property int $busy_threshold
 * @property int $at_capacity_threshold
 * @property string|null $operating_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'name',
    'is_setup_completed',
    'approval_status',
    'rejection_reason',
    'address',
    'contact',
    'description',
    'file_retention_days',
    'busy_threshold',
    'at_capacity_threshold',
    'operating_hours',
])]
class PrintShop extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_setup_completed' => 'boolean',
            'file_retention_days' => 'integer',
            'busy_threshold' => 'integer',
            'at_capacity_threshold' => 'integer',
        ];
    }

    /**
     * Get the user that owns the print shop.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the services associated with the print shop.
     *
     * @return HasMany<ShopService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(ShopService::class);
    }

    /**
     * Get the inventory items for the print shop.
     *
     * @return HasMany<InventoryItem, $this>
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get the unified Bill of Materials (BOM) recipes for the print shop.
     *
     * @return HasMany<ServiceBom, $this>
     */
    public function serviceBoms(): HasMany
    {
        return $this->hasMany(ServiceBom::class);
    }

    /**
     * Get the thesis binding configuration for the print shop.
     *
     * @return HasOne<ThesisBindingConfig, $this>
     */
    public function thesisBindingConfig(): HasOne
    {
        return $this->hasOne(ThesisBindingConfig::class);
    }

    /**
     * Get the orders placed with this print shop.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * Check whether the shop is suspended by platform governance.
     */
    public function isSuspended(): bool
    {
        return $this->getAttribute('approval_status') === self::STATUS_SUSPENDED;
    }

    /**
     * Check whether the shop is approved for public directory listing.
     */
    public function isApproved(): bool
    {
        return $this->getAttribute('approval_status') === self::STATUS_APPROVED;
    }

    /**
     * Resolve the effective file retention window in days, clamped to platform bounds.
     */
    public function effectiveFileRetentionDays(?PlatformSetting $settings = null): int
    {
        $settings ??= PlatformSetting::current();
        $days = $this->file_retention_days ?? $settings->default_retention_days;

        return max($settings->min_retention_days, min($settings->max_retention_days, $days));
    }

    /**
     * Get the printer fleet workstations registered for this print shop.
     *
     * @return HasMany<Printer, $this>
     */
    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class)->orderBy('name');
    }

    /**
     * Count active in-flight order line items awaiting completion or QC.
     */
    public function activeWorkloadItemCount(): int
    {
        return OrderItem::query()
            ->whereHas('order', function ($query): void {
                $query->where('print_shop_id', $this->id)
                    ->whereNotIn('order_status', [
                        Order::STATUS_CANCELLED,
                        Order::STATUS_COMPLETED,
                        Order::STATUS_PENDING_PAYMENT,
                    ])
                    ->where('payment_status', '!=', Order::PAYMENT_REJECTED);
            })
            ->whereIn('production_stage', [
                OrderItem::STAGE_QUEUE,
                OrderItem::STAGE_ON_HOLD,
                OrderItem::STAGE_PRINTING,
                OrderItem::STAGE_BINDING,
                OrderItem::STAGE_QUALITY_CHECK,
            ])
            ->count();
    }

    /**
     * Compute shop-level public workload indicator against configured thresholds.
     */
    public function workloadStatus(): string
    {
        $busy = max(1, (int) ($this->busy_threshold ?: 5));
        $atCapacity = max($busy, (int) ($this->at_capacity_threshold ?: 10));
        $count = $this->activeWorkloadItemCount();

        if ($count >= $atCapacity) {
            return 'At capacity';
        }

        if ($count >= $busy) {
            return 'Busy';
        }

        return 'Normal';
    }

    /**
     * Audit log entries associated with this print shop.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    /**
     * Check if the shop has an active service by key.
     */
    public function hasService(string $serviceKey): bool
    {
        if (PrintServiceCatalog::isPreinstalled($serviceKey)) {
            return true;
        }

        return $this->services()
            ->where('service_key', $serviceKey)
            ->where('is_active', true)
            ->exists();
    }
}
