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
 * @property string $service_key
 * @property bool $is_active
 * @property int $display_order
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['print_shop_id', 'service_key', 'is_active', 'display_order', 'settings'])]
class ShopService extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * Get the print shop that owns the service.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Get the BOM recipes configured for this service in the shop.
     *
     * @return HasMany<ServiceBom, $this>
     */
    public function bomRecipes(): HasMany
    {
        return $this->hasMany(ServiceBom::class, 'service_key', 'service_key')
            ->where('print_shop_id', $this->print_shop_id);
    }

    /**
     * Get a specific setting value with dot notation.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update a specific setting value.
     */
    public function updateSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }
}
