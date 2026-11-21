<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $default_retention_days
 * @property int $min_retention_days
 * @property int $max_retention_days
 * @property int $max_upload_mb
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'default_retention_days',
    'min_retention_days',
    'max_retention_days',
    'max_upload_mb',
    'updated_by',
])]
class PlatformSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_retention_days' => 'integer',
            'min_retention_days' => 'integer',
            'max_retention_days' => 'integer',
            'max_upload_mb' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * Administrator who last updated the global platform policy.
     *
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Retrieve or initialize the singleton platform settings record.
     */
    public static function current(): self
    {
        $existing = static::query()->first();
        if ($existing instanceof self) {
            return $existing;
        }

        return static::create([
            'default_retention_days' => 14,
            'min_retention_days' => 7,
            'max_retention_days' => 90,
            'max_upload_mb' => 50,
        ]);
    }
}
