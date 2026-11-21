<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int|null $requested_by
 * @property array<string, mixed> $old_specs
 * @property array<string, mixed> $new_specs
 * @property float $old_total
 * @property float $new_total
 * @property float $delta_amount
 * @property string $revision_type
 * @property string $status
 * @property int|null $approved_by
 * @property CarbonInterface|null $settled_at
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'order_item_id',
    'requested_by',
    'old_specs',
    'new_specs',
    'old_total',
    'new_total',
    'delta_amount',
    'revision_type',
    'status',
    'approved_by',
    'settled_at',
    'notes',
])]
class SpecRevision extends Model
{
    public const TYPE_UPGRADE = 'upgrade';

    public const TYPE_DOWNGRADE = 'downgrade';

    public const TYPE_NEUTRAL = 'neutral';

    public const TYPE_COVER_ONLY_TO_FULL = 'cover_only_to_full';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_specs' => 'array',
            'new_specs' => 'array',
            'old_total' => 'float',
            'new_total' => 'float',
            'delta_amount' => 'float',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * Line item undergoing specification revision.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * User who requested the specification change.
     *
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Shop owner or staff member who approved or rejected the revision.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
