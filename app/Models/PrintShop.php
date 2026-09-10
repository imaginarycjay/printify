<?php

namespace App\Models;

use App\Services\PrintServiceCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $is_setup_completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'name', 'is_setup_completed'])]
class PrintShop extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_setup_completed' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the print shop.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the services associated with the print shop.
     *
     * @return HasMany<ShopService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(ShopService::class);
    }

    /**
     * Get the inventory items for the print shop.
     *
     * @return HasMany<InventoryItem, $this>
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get the unified Bill of Materials (BOM) recipes for the print shop.
     *
     * @return HasMany<ServiceBom, $this>
     */
    public function serviceBoms(): HasMany
    {
        return $this->hasMany(ServiceBom::class);
    }

    /**
     * Get the thesis binding configuration for the print shop.
     *
     * @return HasOne<ThesisBindingConfig, $this>
     */
    public function thesisBindingConfig(): HasOne
    {
        return $this->hasOne(ThesisBindingConfig::class);
    }

    /**
     * Get the orders placed with this print shop.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * Check if the shop has an active service by key.
     */
    public function hasService(string $serviceKey): bool
    {
        if (PrintServiceCatalog::isPreinstalled($serviceKey)) {
            return true;
        }

        return $this->services()
            ->where('service_key', $serviceKey)
            ->where('is_active', true)
            ->exists();
    }
}
