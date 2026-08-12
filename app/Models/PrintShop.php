<?php

namespace App\Models;

use App\Services\PrintServiceCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
