<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $print_shop_id
 * @property string $service_key
 * @property int $inventory_item_id
 * @property string $component_name
 * @property string $usage_type
 * @property float $usage_qty
 * @property string $unit
 * @property array<string, mixed>|null $conditions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'print_shop_id',
    'service_key',
    'inventory_item_id',
    'component_name',
    'usage_type',
    'usage_qty',
    'unit',
    'conditions',
])]
class ServiceBom extends Model
{
    public const USAGE_PER_COPY = 'per_copy';

    public const USAGE_PER_PAGE = 'per_page';

    public const USAGE_PER_SHEET = 'per_sheet';

    public const USAGE_FIXED = 'fixed';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_qty' => 'float',
            'conditions' => 'array',
        ];
    }

    /**
     * Get the owning print shop.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Get the inventory item deducted by this BOM recipe.
     *
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
