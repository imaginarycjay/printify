<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $binding_type
 * @property int $bw_pages_count
 * @property int $color_pages_count
 * @property int $total_pages_count
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_id',
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
])]
class OrderItem extends Model
{
    public const FULFILLMENT_FULL_PACKAGE = 'full_package';

    public const FULFILLMENT_COVER_ONLY = 'cover_only';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
        return ($this->order && $this->order->service_key === 'document_printing') ||
            (isset($this->custom_fields_data['service_key']) && $this->custom_fields_data['service_key'] === 'document_printing') ||
            isset($this->custom_fields_data['print_sides']) ||
            isset($this->custom_fields_data['finishing_type']);
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
            'staple' => '📎 Corner Staple',
            'folder' => '📁 Sliding Folder & Fastener',
            'ring_bind' => '🌀 Plastic Ring Binding',
            'booklet' => '📘 Booklet Staple & Fold',
            default => '📄 Loose Sheets (No Binding)',
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
}
