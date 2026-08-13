<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property float $stock_qty
 * @property string $unit
 * @property float $reorder_level
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['print_shop_id', 'name', 'sku', 'category', 'stock_qty', 'unit', 'reorder_level'])]
class InventoryItem extends Model
{
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
}
