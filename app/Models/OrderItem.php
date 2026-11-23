<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property string|null $service_key
 * @property int|null $printer_id
 * @property int|null $assigned_staff_id
 * @property string $binding_type
 * @property int $bw_pages_count
 * @property int $color_pages_count
 * @property int $total_pages_count
 * @property array<string, mixed>|null $specifications
 * @property string|null $cover_color
 * @property string|null $foil_color
 * @property string $paper_size
 * @property int $copies_count
 * @property array<string, mixed>|null $custom_fields_data
 * @property array<int, array{id: int, name: string, price: float, qty: int}>|null $selected_addons
 * @property string|null $document_file_path
 * @property string|null $document_original_name
 * @property float $unit_price
 * @property float $total_price
 * @property string $fulfillment_type
 * @property bool $is_paper_received
 * @property float $estimated_spine_thickness_mm
 * @property string $production_stage
 * @property string $prepress_status
 * @property string|null $prepress_rejection_reason
 * @property bool $is_spec_locked
 * @property string|null $shelf_bin_code
 * @property CarbonInterface|null $ready_at
 * @property CarbonInterface|null $claimed_at
 * @property CarbonInterface|null $file_purged_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'order_id',
    'service_key',
    'printer_id',
    'assigned_staff_id',
    'binding_type',
    'fulfillment_type',
    'is_paper_received',
    'estimated_spine_thickness_mm',
    'bw_pages_count',
    'color_pages_count',
    'total_pages_count',
    'specifications',
    'cover_color',
    'foil_color',
    'paper_size',
    'copies_count',
    'custom_fields_data',
    'selected_addons',
    'document_file_path',
    'document_original_name',
    'unit_price',
    'total_price',
    'production_stage',
    'prepress_status',
    'prepress_rejection_reason',
    'is_spec_locked',
    'shelf_bin_code',
    'ready_at',
    'claimed_at',
    'file_purged_at',
])]
class OrderItem extends Model
{
    public const FULFILLMENT_FULL_PACKAGE = 'full_package';

    public const FULFILLMENT_COVER_ONLY = 'cover_only';

    public const PREPRESS_PENDING = 'pending';

    public const PREPRESS_APPROVED = 'approved';

    public const PREPRESS_REJECTED = 'rejected';

    public const STAGE_QUEUE = 'queue';

    public const STAGE_ON_HOLD = 'on_hold';

    public const STAGE_PRINTING = 'printing';

    public const STAGE_BINDING = 'binding';

    public const STAGE_QUALITY_CHECK = 'quality_check';

    public const STAGE_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STAGE_CLAIMED = 'claimed';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'printer_id' => 'integer',
            'assigned_staff_id' => 'integer',
            'is_paper_received' => 'boolean',
            'estimated_spine_thickness_mm' => 'float',
            'bw_pages_count' => 'integer',
            'color_pages_count' => 'integer',
            'total_pages_count' => 'integer',
            'copies_count' => 'integer',
            'specifications' => 'array',
            'custom_fields_data' => 'array',
            'selected_addons' => 'array',
            'unit_price' => 'float',
            'total_price' => 'float',
            'is_spec_locked' => 'boolean',
            'ready_at' => 'datetime',
            'claimed_at' => 'datetime',
            'file_purged_at' => 'datetime',
        ];
    }

    /**
     * Check if this item is Cover & Binding only (pre-printed customer pages).
     */
    public function isCoverOnly(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_COVER_ONLY;
    }

    /**
     * Check if this item is a full print & bind package.
     */
    public function isFullPackage(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_FULL_PACKAGE;
    }

    /**
     * Check if this item belongs to document printing service.
     */
    public function isDocumentPrinting(): bool
    {
        return $this->resolvedServiceKey() === 'document_printing';
    }

    /**
     * Check if this item belongs to thesis binding service.
     */
    public function isThesisBinding(): bool
    {
        return $this->resolvedServiceKey() === 'thesis_binding';
    }

    /**
     * Resolve the authoritative service key for this line item.
     */
    public function resolvedServiceKey(): string
    {
        if (is_string($this->service_key) && $this->service_key !== '') {
            return $this->service_key;
        }

        $customService = $this->custom_fields_data['service_key'] ?? null;
        if (is_string($customService) && $customService !== '') {
            return $customService;
        }

        if ($this->order && is_string($this->order->service_key) && $this->order->service_key !== '') {
            return $this->order->service_key;
        }

        if (isset($this->custom_fields_data['finishing_type']) || isset($this->custom_fields_data['print_sides'])) {
            return 'document_printing';
        }

        return 'thesis_binding';
    }

    /**
     * Physical sheet count per bound copy (accounts for duplex printing).
     */
    public function getSheetsPerCopyForBinding(): int
    {
        $totalPages = max(1, (int) $this->total_pages_count);

        if ($this->isDuplex()) {
            return (int) ceil($totalPages / 2);
        }

        return $totalPages;
    }

    /**
     * Get print sides (simplex vs duplex).
     */
    public function getPrintSides(): string
    {
        return (string) ($this->custom_fields_data['print_sides'] ?? 'simplex');
    }

    /**
     * Check if duplex (back-to-back).
     */
    public function isDuplex(): bool
    {
        return $this->getPrintSides() === 'duplex';
    }

    /**
     * Get the number of physical paper sheets required.
     * If duplex, sheets = ceil(total_pages / 2) * copies.
     */
    public function getPhysicalSheetsCount(): int
    {
        $totalPages = max(1, (int) $this->total_pages_count);
        $copies = max(1, (int) $this->copies_count);

        if ($this->isCoverOnly()) {
            return 0;
        }

        if ($this->isDuplex()) {
            return (int) ceil($totalPages / 2) * $copies;
        }

        return $totalPages * $copies;
    }

    /**
     * Get finishing type (loose, staple, folder, ring_bind, booklet).
     */
    public function getFinishingType(): string
    {
        return (string) ($this->custom_fields_data['finishing_type'] ?? 'loose');
    }

    /**
     * Get human-readable finishing label.
     */
    public function getFinishingLabel(): string
    {
        return match ($this->getFinishingType()) {
            'staple' => 'Corner Staple',
            'folder' => 'Sliding Folder & Fastener',
            'ring_bind' => 'Plastic Ring Binding',
            'booklet' => 'Booklet Staple & Fold',
            default => 'Loose Sheets (No Binding)',
        };
    }

    /**
     * Parent order.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Production staff member assigned to this line item.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * Printer workstation assigned to this line item.
     *
     * @return BelongsTo<Printer, $this>
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    /**
     * Specification revision history for this line item.
     *
     * @return HasMany<SpecRevision, $this>
     */
    public function specRevisions(): HasMany
    {
        return $this->hasMany(SpecRevision::class)->latest();
    }

    /**
     * Determine whether printing or downstream physical work has already started for this item.
     */
    public function hasStartedPrinting(): bool
    {
        return in_array($this->production_stage ?? self::STAGE_QUEUE, [
            self::STAGE_PRINTING,
            self::STAGE_BINDING,
            self::STAGE_QUALITY_CHECK,
            self::STAGE_READY_FOR_PICKUP,
            self::STAGE_CLAIMED,
        ], true);
    }

    /**
     * Determine whether pre-print specification revisions are still permitted for this item.
     */
    public function canReviseSpecifications(): bool
    {
        if ($this->hasStartedPrinting()) {
            return false;
        }

        $order = $this->order;
        if (! $order instanceof Order) {
            return true;
        }

        return ! in_array($order->order_status, [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED], true);
    }

    /**
     * Determine whether this line item is staged in a shelf/bin ready for customer pickup.
     */
    public function isReadyForPickup(): bool
    {
        return ($this->production_stage ?? self::STAGE_QUEUE) === self::STAGE_READY_FOR_PICKUP;
    }

    /**
     * Determine whether this line item has been claimed by the customer.
     */
    public function isClaimed(): bool
    {
        return ($this->production_stage ?? self::STAGE_QUEUE) === self::STAGE_CLAIMED || $this->claimed_at !== null;
    }

    /**
     * Determine whether a ready item has remained unclaimed beyond the given number of days.
     */
    public function isUnclaimedAging(int $days = 3): bool
    {
        return $this->isReadyForPickup()
            && $this->claimed_at === null
            && $this->ready_at !== null
            && $this->ready_at->lte(now()->subDays($days));
    }

    /**
     * Human-readable stage label for this line item.
     */
    public function stageLabel(): string
    {
        return match ($this->production_stage ?? self::STAGE_QUEUE) {
            self::STAGE_ON_HOLD => 'On Hold (Prepress / Revision)',
            self::STAGE_PRINTING => 'Printing Pages',
            self::STAGE_BINDING => 'Binding & Finishing',
            self::STAGE_QUALITY_CHECK => 'Quality Check (QC)',
            self::STAGE_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STAGE_CLAIMED => 'Claimed',
            default => 'In Queue',
        };
    }

    /**
     * Stock movements (reservations, deductions, rework) linked to this item.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }
}
