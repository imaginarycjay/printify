<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $print_shop_id
 * @property string $name
 * @property array<int, string>|null $supported_sizes
 * @property string $status
 * @property bool $is_wide_format
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'print_shop_id',
    'name',
    'supported_sizes',
    'status',
    'is_wide_format',
])]
class Printer extends Model
{
    public const STATUS_ONLINE = 'online';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_OFFLINE = 'offline';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supported_sizes' => 'array',
            'is_wide_format' => 'boolean',
        ];
    }

    /**
     * Print shop owning this printer workstation.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Order line items assigned to this printer.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Determine whether this printer is ready to accept queued print jobs.
     */
    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * Determine whether this printer is currently under maintenance.
     */
    public function isUnderMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    /**
     * Determine whether this printer supports the requested paper size.
     */
    public function supportsSize(?string $paperSize): bool
    {
        $sizes = $this->supported_sizes;
        if (! is_array($sizes) || $sizes === []) {
            return true;
        }

        if ($paperSize === null || trim($paperSize) === '') {
            return true;
        }

        $target = $this->normalizePaperToken($paperSize);

        foreach ($sizes as $supported) {
            if (trim($supported) === '') {
                continue;
            }

            if ($this->normalizePaperToken($supported) === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count active unfinished line items assigned to this printer.
     */
    public function activeJobsCount(): int
    {
        return $this->orderItems()
            ->whereIn('production_stage', [
                OrderItem::STAGE_QUEUE,
                OrderItem::STAGE_PRINTING,
                OrderItem::STAGE_BINDING,
                OrderItem::STAGE_QUALITY_CHECK,
                OrderItem::STAGE_ON_HOLD,
            ])
            ->whereHas('order', function ($query): void {
                $query->whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED]);
            })
            ->count();
    }

    protected function normalizePaperToken(string $value): string
    {
        $lower = strtolower(trim($value));

        if (str_contains($lower, 'letter') || str_contains($lower, 'short')) {
            return 'letter';
        }

        if (str_contains($lower, 'legal') || str_contains($lower, 'long') || str_contains($lower, 'folio')) {
            return 'legal';
        }

        return $lower;
    }
}
