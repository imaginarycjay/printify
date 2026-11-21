<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $print_shop_id
 * @property int|null $submitted_by
 * @property string $payment_type
 * @property string $payment_method
 * @property float $amount
 * @property string|null $reference_no
 * @property string|null $proof_path
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $verified_by
 * @property CarbonInterface|null $verified_at
 * @property CarbonInterface|null $submitted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'order_id',
    'print_shop_id',
    'submitted_by',
    'payment_type',
    'payment_method',
    'amount',
    'reference_no',
    'proof_path',
    'status',
    'rejection_reason',
    'verified_by',
    'verified_at',
    'submitted_at',
])]
class Payment extends Model
{
    public const TYPE_UPFRONT = 'upfront';

    public const TYPE_DOWNPAYMENT_50 = 'downpayment_50';

    public const TYPE_FULL_100 = 'full_100';

    public const TYPE_BALANCE_SETTLEMENT = 'balance_settlement';

    public const TYPE_OFFLINE_COUNTER = 'offline_counter';

    public const TYPE_UPGRADE_TOPUP = 'upgrade_topup';

    public const TYPE_MANUAL_REFUND = 'manual_refund';

    public const STATUS_PENDING = 'pending_verification';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'verified_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Order settled or partially funded by this payment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Print shop receiving this payment.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * User who submitted the payment proof.
     *
     * @return BelongsTo<User, $this>
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Shop owner or staff member who verified the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Determine whether this pending proof has exceeded the review threshold.
     */
    public function isAging(int $hours = 2): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $referenceTime = $this->submitted_at ?? $this->created_at;

        return $referenceTime !== null && $referenceTime->lte(now()->subHours($hours));
    }
}
