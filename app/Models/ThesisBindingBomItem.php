<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $thesis_binding_config_id
 * @property int $inventory_item_id
 * @property string $binding_type
 * @property float $usage_qty
 * @property string $unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['thesis_binding_config_id', 'inventory_item_id', 'binding_type', 'usage_qty', 'unit'])]
class ThesisBindingBomItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_qty' => 'float',
        ];
    }

    /**
     * Get the parent thesis binding configuration.
     *
     * @return BelongsTo<ThesisBindingConfig, $this>
     */
    public function thesisBindingConfig(): BelongsTo
    {
        return $this->belongsTo(ThesisBindingConfig::class);
    }

    /**
     * Get the linked inventory item.
     *
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
