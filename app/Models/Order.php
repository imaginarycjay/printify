<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $order_number
 * @property int $print_shop_id
 * @property int $customer_id
 * @property string $service_key
 * @property string $order_status
 * @property string $payment_status
 * @property float $subtotal_amount
 * @property float $rush_fee_amount
 * @property float $total_amount
 * @property bool $is_rush
 * @property CarbonInterface|null $target_completion_date
 * @property string|null $payment_proof_path
 * @property string|null $payment_reference_no
 * @property CarbonInterface|null $payment_verified_at
 * @property int|null $payment_verified_by
 * @property int|null $assigned_staff_id
 * @property string|null $assigned_machine
 * @property string $production_stage
 * @property CarbonInterface|null $production_started_at
 * @property CarbonInterface|null $production_completed_at
 * @property string|null $staff_notes
 * @property string|null $rejection_reason
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'order_number',
    'print_shop_id',
    'customer_id',
    'service_key',
    'order_status',
    'payment_status',
    'subtotal_amount',
    'rush_fee_amount',
    'total_amount',
    'is_rush',
    'target_completion_date',
    'payment_proof_path',
    'payment_reference_no',
    'payment_verified_at',
    'payment_verified_by',
    'assigned_staff_id',
    'assigned_machine',
    'production_stage',
    'production_started_at',
    'production_completed_at',
    'staff_notes',
    'rejection_reason',
])]
class Order extends Model
{
    // Order Lifecycle Statuses
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_IN_QUEUE = 'in_queue';

    public const STATUS_IN_PRODUCTION = 'in_production';

    public const STATUS_QUALITY_CHECK = 'quality_check';

    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    // Production Specific Sub-Stages
    public const STAGE_QUEUE = 'queue';

    public const STAGE_PRINTING = 'printing';

    public const STAGE_BINDING = 'binding';

    public const STAGE_QUALITY_CHECK = 'quality_check';

    public const STAGE_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STAGE_COMPLETED = 'completed';

    // Payment Statuses
    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING_VERIFICATION = 'pending_verification';

    public const PAYMENT_VERIFIED_PAID = 'verified_paid';

    public const PAYMENT_PAID = 'verified_paid';

    public const PAYMENT_REJECTED = 'rejected';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'float',
            'rush_fee_amount' => 'float',
            'total_amount' => 'float',
            'is_rush' => 'boolean',
            'target_completion_date' => 'date',
            'payment_verified_at' => 'datetime',
            'production_started_at' => 'datetime',
            'production_completed_at' => 'datetime',
        ];
    }

    /**
     * Customer who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Print shop fulfilling the order.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Line items within this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * User who verified the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Production staff member assigned to the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * Generate unique sequential order number (e.g. ORD-2026-0001).
     */
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = static::whereYear('created_at', $year)->latest('id')->first();
        $nextNum = $lastOrder ? ((int) substr($lastOrder->order_number, -4)) + 1 : 1;

        return sprintf('ORD-%s-%04d', $year, $nextNum);
    }

    /**
     * Check if physical paper intake is pending for Cover-Only order.
     */
    public function isPaperIntakePending(): bool
    {
        $item = $this->items->first();

        return $item && $item->isCoverOnly() && ! $item->is_paper_received;
    }

    /**
     * Advance order to the next logical production stage.
     */
    public function advanceStage(?int $staffId = null, ?string $machine = null): void
    {
        $current = $this->production_stage ?? self::STAGE_QUEUE;

        if ($staffId) {
            $this->assigned_staff_id = $staffId;
        }
        if ($machine) {
            $this->assigned_machine = $machine;
        }

        switch ($current) {
            case self::STAGE_QUEUE:
                $this->production_stage = self::STAGE_PRINTING;
                $this->order_status = self::STATUS_IN_PRODUCTION;
                $this->production_started_at = $this->production_started_at ?? now();
                break;

            case self::STAGE_PRINTING:
                $this->production_stage = self::STAGE_BINDING;
                $this->order_status = self::STATUS_IN_PRODUCTION;
                break;

            case self::STAGE_BINDING:
                $this->production_stage = self::STAGE_QUALITY_CHECK;
                $this->order_status = self::STATUS_QUALITY_CHECK;
                break;

            case self::STAGE_QUALITY_CHECK:
                $this->production_stage = self::STAGE_READY_FOR_PICKUP;
                $this->order_status = self::STATUS_READY_FOR_PICKUP;
                $this->production_completed_at = now();
                break;

            case self::STAGE_READY_FOR_PICKUP:
                $this->production_stage = self::STAGE_COMPLETED;
                $this->order_status = self::STATUS_COMPLETED;
                if ($this->payment_status !== self::PAYMENT_VERIFIED_PAID && $this->payment_status !== self::PAYMENT_REJECTED) {
                    $this->payment_status = self::PAYMENT_VERIFIED_PAID;
                    $this->payment_verified_at = now();
                    if ($staffId) {
                        $this->payment_verified_by = $staffId;
                    }
                }
                break;
        }

        $this->save();
    }

    /**
     * Step back to the previous production stage (e.g. if QC fails).
     */
    public function stepBackStage(?string $reason = null): void
    {
        $current = $this->production_stage ?? self::STAGE_QUEUE;

        if ($reason) {
            $this->staff_notes = ($this->staff_notes ? $this->staff_notes."\n" : '').'['.now()->format('M d, H:i').'] QC Rejection: '.$reason;
        }

        switch ($current) {
            case self::STAGE_COMPLETED:
                $this->production_stage = self::STAGE_READY_FOR_PICKUP;
                $this->order_status = self::STATUS_READY_FOR_PICKUP;
                break;

            case self::STAGE_READY_FOR_PICKUP:
                $this->production_stage = self::STAGE_QUALITY_CHECK;
                $this->order_status = self::STATUS_QUALITY_CHECK;
                break;

            case self::STAGE_QUALITY_CHECK:
                $this->production_stage = self::STAGE_BINDING;
                $this->order_status = self::STATUS_IN_PRODUCTION;
                break;

            case self::STAGE_BINDING:
                $this->production_stage = self::STAGE_PRINTING;
                $this->order_status = self::STATUS_IN_PRODUCTION;
                break;

            case self::STAGE_PRINTING:
                $this->production_stage = self::STAGE_QUEUE;
                $this->order_status = self::STATUS_IN_QUEUE;
                break;
        }

        $this->save();
    }

    /**
     * Human-readable label for production stage.
     */
    public function stageLabel(): string
    {
        return match ($this->production_stage) {
            self::STAGE_PRINTING => 'Printing in Progress',
            self::STAGE_BINDING => 'Cover Assembly & Stamping',
            self::STAGE_QUALITY_CHECK => 'Quality Inspection (QC)',
            self::STAGE_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STAGE_COMPLETED => 'Order Completed & Received',
            default => 'Queued for Production',
        };
    }

    /**
     * Get the 1-indexed stage number (1 to 5) for the customer progress stepper.
     */
    public function currentStageIndex(): int
    {
        // Check fine-grained production stage if available
        if ($this->production_stage) {
            return match ($this->production_stage) {
                self::STAGE_QUEUE => 2,
                self::STAGE_PRINTING, self::STAGE_BINDING => 3,
                self::STAGE_QUALITY_CHECK => 4,
                self::STAGE_READY_FOR_PICKUP, self::STAGE_COMPLETED => 5,
                default => 2,
            };
        }

        return match ($this->order_status) {
            self::STATUS_PENDING_PAYMENT => 1,
            self::STATUS_IN_QUEUE => 2,
            self::STATUS_IN_PRODUCTION => 3,
            self::STATUS_QUALITY_CHECK => 4,
            self::STATUS_READY_FOR_PICKUP, self::STATUS_COMPLETED => 5,
            default => 1,
        };
    }

    /**
     * Get user-friendly status badge styling classes.
     */
    public function statusBadgeColor(): string
    {
        return match ($this->order_status) {
            self::STATUS_PENDING_PAYMENT => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            self::STATUS_IN_QUEUE => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
            self::STATUS_IN_PRODUCTION => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
            self::STATUS_QUALITY_CHECK => 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
            self::STATUS_READY_FOR_PICKUP => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
            self::STATUS_COMPLETED => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            self::STATUS_CANCELLED => 'bg-red-500/15 text-red-400 border-red-500/30',
            default => 'bg-stone-500/15 text-stone-400 border-stone-500/30',
        };
    }

    /**
     * Human-readable label for payment status.
     */
    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_VERIFIED_PAID => 'Verified Paid',
            self::PAYMENT_PENDING_VERIFICATION => 'Pending Verification',
            self::PAYMENT_UNPAID => 'Pay at Counter (Unpaid)',
            self::PAYMENT_REJECTED => 'Payment Rejected',
            default => ucfirst(str_replace('_', ' ', $this->payment_status)),
        };
    }

    /**
     * Get user-friendly payment status badge styling classes.
     */
    public function paymentStatusBadgeColor(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_VERIFIED_PAID => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            self::PAYMENT_PENDING_VERIFICATION => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            self::PAYMENT_UNPAID => 'bg-orange-500/15 text-orange-400 border-orange-500/30',
            self::PAYMENT_REJECTED => 'bg-red-500/15 text-red-400 border-red-500/30',
            default => 'bg-stone-500/15 text-stone-400 border-stone-500/30',
        };
    }
}
