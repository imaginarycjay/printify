<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property string $movement_type
 * @property float $quantity
 * @property float $previous_stock
 * @property float $resulting_stock
 * @property string|null $reference_note
 * @property int|null $logged_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'inventory_item_id',
    'movement_type',
    'quantity',
    'previous_stock',
    'resulting_stock',
    'reference_note',
    'logged_by',
])]
class StockMovement extends Model
{
    public const TYPE_MANUAL_STOCK_IN = 'manual_stock_in';

    public const TYPE_PRODUCTION_DEDUCTION = 'production_deduction';

    public const TYPE_SPOILAGE_WASTE = 'spoilage_waste';

    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'previous_stock' => 'float',
            'resulting_stock' => 'float',
        ];
    }

    /**
     * Get the parent inventory item.
     *
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who logged this movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
