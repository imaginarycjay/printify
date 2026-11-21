<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property int|null $order_item_id
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
    'order_item_id',
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

    public const TYPE_RESERVATION = 'stock_reservation';

    public const TYPE_RESERVATION_RELEASE = 'stock_reservation_release';

    public const TYPE_REWORK_SPOILAGE = 'rework_spoilage';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_item_id' => 'integer',
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
     * Get the order line item linked to this stock movement.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
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
