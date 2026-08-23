<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $print_shop_id
 * @property string $name
 * @property string|null $sku
 * @property string $category
 * @property string $item_type
 * @property string|null $service_tag
 * @property float $stock_qty
 * @property string $unit
 * @property float $reorder_level
 * @property float $unit_cost
 * @property float|null $selling_price
 * @property string|null $supplier_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'print_shop_id',
    'name',
    'sku',
    'category',
    'item_type',
    'service_tag',
    'stock_qty',
    'unit',
    'reorder_level',
    'unit_cost',
    'selling_price',
    'supplier_name',
])]
class InventoryItem extends Model
{
    public const TYPE_RAW_MATERIAL = 'raw_material';

    public const TYPE_READY_TO_SELL = 'ready_to_sell';

    public const TYPE_CONSUMABLE = 'consumable';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_qty' => 'float',
            'reorder_level' => 'float',
            'unit_cost' => 'float',
            'selling_price' => 'float',
        ];
    }

    /**
     * Get the print shop that owns the inventory item.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Get the BOM item usage records referencing this inventory item.
     *
     * @return HasMany<ThesisBindingBomItem, $this>
     */
    public function thesisBindingBomItems(): HasMany
    {
        return $this->hasMany(ThesisBindingBomItem::class);
    }

    /**
     * Get the stock movements log for this inventory item.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /**
     * Check if the item is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->stock_qty > 0 && $this->stock_qty <= $this->reorder_level;
    }

    /**
     * Check if the item is completely out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->stock_qty <= 0;
    }

    /**
     * Get stock status string: 'out_of_stock', 'low_stock', or 'in_stock'.
     */
    public function stockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Scope query to raw materials only.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeRawMaterials(Builder $query): void
    {
        $query->where('item_type', self::TYPE_RAW_MATERIAL);
    }

    /**
     * Scope query to ready-to-sell products.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeReadyToSell(Builder $query): void
    {
        $query->where('item_type', self::TYPE_READY_TO_SELL);
    }

    /**
     * Scope query to low or out-of-stock items.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock_qty', '<=', 'reorder_level');
    }

    /**
     * Scope query for a specific service tag.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeForService(Builder $query, string $serviceKey): void
    {
        $query->where(function (Builder $q) use ($serviceKey) {
            $q->where('service_tag', $serviceKey)
                ->orWhere('service_tag', 'all')
                ->orWhereNull('service_tag');
        });
    }
}
