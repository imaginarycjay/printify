<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private InventoryDeductionService $inventoryDeductionService,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Submit or retry an upfront / balance payment proof for an existing order.
     *
     * @throws ValidationException
     */
    public function submitProof(
        Order $order,
        User $customer,
        string $paymentOption,
        string $paymentMethod,
        float $amount,
        string $referenceNo,
        ?UploadedFile $proofFile = null,
        ?string $existingProofPath = null,
    ): Payment {
        abort_unless($order->customer_id === $customer->id, 403, 'You do not own this order.');
        abort_if($order->order_status === Order::STATUS_CANCELLED, 422, 'Cancelled orders cannot accept payments.');

        $remainingBalance = $this->effectiveBalanceDue($order);
        if ($remainingBalance <= 0.0) {
            throw ValidationException::withMessages([
                'amount' => 'This order is already fully paid.',
            ]);
        }

        if (! in_array($paymentOption, [Order::PAYMENT_OPTION_DOWNPAYMENT_50, Order::PAYMENT_OPTION_FULL_100], true)) {
            throw ValidationException::withMessages([
                'payment_option' => 'Select either 50% Downpayment or 100% Full Payment.',
            ]);
        }

        $roundedAmount = round($amount, 2);
        if ((float) $order->amount_paid <= 0.0) {
            $minRequired = $paymentOption === Order::PAYMENT_OPTION_DOWNPAYMENT_50
                ? $order->minimumUpfrontAmount()
                : round((float) $order->total_amount, 2);

            if ($roundedAmount + 0.001 < $minRequired) {
                $minFormatted = number_format($minRequired, 2);
                throw ValidationException::withMessages([
                    'amount' => "Upfront payment must be at least ₱{$minFormatted} for the selected payment option.",
                ]);
            }
        } elseif ($roundedAmount <= 0.0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        if ($roundedAmount - 0.001 > $remainingBalance) {
            $balFormatted = number_format($remainingBalance, 2);
            throw ValidationException::withMessages([
                'amount' => "Payment amount cannot exceed the remaining balance of ₱{$balFormatted}.",
            ]);
        }

        $normalizedRef = strtoupper(trim($referenceNo));
        if (strlen($normalizedRef) < 4 || strlen($normalizedRef) > 100) {
            throw ValidationException::withMessages([
                'reference_no' => 'Please enter a valid payment reference number (4–100 characters).',
            ]);
        }

        $duplicateExists = Payment::query()
            ->where('print_shop_id', $order->print_shop_id)
            ->where('reference_no', $normalizedRef)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'reference_no' => 'This payment reference number has already been submitted for this print shop.',
            ]);
        }

        if ($proofFile !== null) {
            Validator::make(
                ['proof_file' => $proofFile],
                ['proof_file' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']],
            )->validate();
        }

        $storedProofPath = $existingProofPath;
        if ($proofFile !== null) {
            $stored = $proofFile->store('orders/payments', 'local');
            if (is_string($stored)) {
                $storedProofPath = $stored;
            }
        }

        return DB::transaction(function () use (
            $order,
            $customer,
            $paymentOption,
            $paymentMethod,
            $roundedAmount,
            $normalizedRef,
            $storedProofPath,
        ): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $paymentType = (float) $lockedOrder->amount_paid > 0.0
                ? Payment::TYPE_BALANCE_SETTLEMENT
                : ($paymentOption === Order::PAYMENT_OPTION_DOWNPAYMENT_50
                    ? Payment::TYPE_DOWNPAYMENT_50
                    : Payment::TYPE_FULL_100);

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'print_shop_id' => $lockedOrder->print_shop_id,
                'submitted_by' => $customer->id,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'amount' => $roundedAmount,
                'reference_no' => $normalizedRef,
                'proof_path' => $storedProofPath,
                'status' => Payment::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            $lockedOrder->update([
                'payment_option' => $paymentOption,
                'payment_status' => Order::PAYMENT_PENDING_VERIFICATION,
                'payment_reference_no' => $normalizedRef,
                'payment_proof_path' => $storedProofPath ?? $lockedOrder->payment_proof_path,
                'rejection_reason' => null,
            ]);

            $this->auditLogger->log(
                action: 'payment.proof_submitted',
                auditable: $payment,
                printShopId: $lockedOrder->print_shop_id,
                actor: $customer,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'reference_no' => $normalizedRef,
                    'amount' => $roundedAmount,
                ],
            );

            $lockedOrder->loadMissing('printShop.user');
            $owner = $lockedOrder->printShop?->user;
            if ($owner instanceof User) {
                $owner->notify((new PaymentStatusNotification($lockedOrder, 'proof_submitted'))->afterCommit());
            }

            return $payment;
        });
    }

    /**
     * Accept a pending payment proof, recompute order balance, and admit pending_payment orders to queue.
     */
    public function verifyPayment(Payment $payment, User $verifier): Payment
    {
        $shop = $verifier->operationalShop();
        abort_unless($shop !== null && $shop->id === $payment->print_shop_id, 403, 'Unauthorized payment verification.');
        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'Only pending payments can be verified.');

        return DB::transaction(function () use ($payment, $verifier): Payment {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            $lockedPayment->update([
                'status' => Payment::STATUS_VERIFIED,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->recalculateOrderLedger($lockedOrder, $verifier);

            $this->auditLogger->log(
                action: 'payment.verified',
                auditable: $lockedPayment,
                printShopId: $lockedOrder->print_shop_id,
                actor: $verifier,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'amount' => $lockedPayment->amount,
                    'balance_due' => $lockedOrder->balance_due,
                ],
            );

            $lockedOrder->loadMissing('customer');
            if ($lockedOrder->customer instanceof User) {
                $lockedOrder->customer->notify((new PaymentStatusNotification($lockedOrder, 'verified'))->afterCommit());
            }

            return $lockedPayment->fresh() ?? $lockedPayment;
        });
    }

    /**
     * Reject a submitted payment proof with reason while keeping order & BOM reservation alive for retry.
     *
     * @throws ValidationException
     */
    public function rejectPayment(Payment $payment, User $verifier, string $reason): Payment
    {
        $shop = $verifier->operationalShop();
        abort_unless($shop !== null && $shop->id === $payment->print_shop_id, 403, 'Unauthorized payment verification.');
        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'Only pending payments can be rejected.');

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason is required when rejecting payment proof.',
            ]);
        }

        return DB::transaction(function () use ($payment, $verifier, $trimmedReason): Payment {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            $lockedPayment->update([
                'status' => Payment::STATUS_REJECTED,
                'rejection_reason' => $trimmedReason,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

            $lockedOrder->update([
                'payment_status' => Order::PAYMENT_REJECTED,
                'rejection_reason' => $trimmedReason,
            ]);

            $this->auditLogger->log(
                action: 'payment.rejected',
                auditable: $lockedPayment,
                printShopId: $lockedOrder->print_shop_id,
                actor: $verifier,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'reason' => $trimmedReason,
                ],
            );

            $lockedOrder->loadMissing('customer');
            if ($lockedOrder->customer instanceof User) {
                $lockedOrder->customer->notify((new PaymentStatusNotification($lockedOrder, 'rejected', $trimmedReason))->afterCommit());
            }

            return $lockedPayment->fresh() ?? $lockedPayment;
        });
    }

    /**
     * Record an offline counter verification or remaining pickup balance settlement.
     *
     * @throws ValidationException
     */
    public function recordOfflineCounterPayment(
        Order $order,
        User $actor,
        float $amount,
        string $paymentMethod = 'cash_counter',
        ?string $referenceNo = null,
    ): Payment {
        $shop = $actor->operationalShop();
        abort_unless($shop !== null && $shop->id === $order->print_shop_id, 403, 'Unauthorized counter settlement.');
        abort_if($order->order_status === Order::STATUS_CANCELLED, 422, 'Cancelled orders cannot accept payments.');

        return DB::transaction(function () use ($order, $actor, $amount, $paymentMethod, $referenceNo): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $effectiveBalance = $this->effectiveBalanceDue($lockedOrder);
            $roundedAmount = round($amount, 2);

            if ($roundedAmount <= 0.0) {
                throw ValidationException::withMessages([
                    'amount' => 'Settlement amount must be greater than zero.',
                ]);
            }

            if ($roundedAmount - 0.001 > $effectiveBalance) {
                $balFormatted = number_format($effectiveBalance, 2);
                throw ValidationException::withMessages([
                    'amount' => "Settlement amount cannot exceed remaining balance (₱{$balFormatted}).",
                ]);
            }

            if ((float) $lockedOrder->amount_paid <= 0.0 && $roundedAmount + 0.001 < $lockedOrder->minimumUpfrontAmount()) {
                $minFormatted = number_format($lockedOrder->minimumUpfrontAmount(), 2);
                throw ValidationException::withMessages([
                    'amount' => "Pre-order counter payment must be at least 50% (₱{$minFormatted}).",
                ]);
            }

            $normalizedRef = $referenceNo !== null && trim($referenceNo) !== ''
                ? strtoupper(trim($referenceNo))
                : null;

            if ($normalizedRef !== null) {
                $duplicate = Payment::query()
                    ->where('print_shop_id', $lockedOrder->print_shop_id)
                    ->where('reference_no', $normalizedRef)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'reference_no' => 'This reference number has already been recorded for this print shop.',
                    ]);
                }
            }

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'print_shop_id' => $lockedOrder->print_shop_id,
                'submitted_by' => $actor->id,
                'payment_type' => (float) $lockedOrder->amount_paid > 0.0
                    ? Payment::TYPE_BALANCE_SETTLEMENT
                    : Payment::TYPE_OFFLINE_COUNTER,
                'payment_method' => $paymentMethod,
                'amount' => $roundedAmount,
                'reference_no' => $normalizedRef,
                'status' => Payment::STATUS_VERIFIED,
                'verified_by' => $actor->id,
                'submitted_at' => now(),
                'verified_at' => now(),
            ]);

            if ((float) $lockedOrder->amount_paid <= 0.0 && $roundedAmount + 0.001 < (float) $lockedOrder->total_amount) {
                $lockedOrder->payment_option = Order::PAYMENT_OPTION_DOWNPAYMENT_50;
            } elseif ((float) $lockedOrder->amount_paid <= 0.0) {
                $lockedOrder->payment_option = Order::PAYMENT_OPTION_COUNTER_OFFLINE;
            }

            $this->recalculateOrderLedger($lockedOrder, $actor);

            $this->auditLogger->log(
                action: 'payment.counter_settled',
                auditable: $payment,
                printShopId: $lockedOrder->print_shop_id,
                actor: $actor,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'amount' => $roundedAmount,
                    'balance_due' => $lockedOrder->balance_due,
                ],
            );

            $lockedOrder->loadMissing('customer');
            if ($lockedOrder->customer instanceof User) {
                $lockedOrder->customer->notify((new PaymentStatusNotification($lockedOrder, 'counter_settled'))->afterCommit());
            }

            return $payment;
        });
    }

    /**
     * Cancel an unpaid pending order and release its reserved inventory.
     */
    public function cancelUnpaidOrder(Order $order, User $actor, ?string $reason = null): Order
    {
        $isOwnerOrStaff = $actor->operationalShop()?->id === $order->print_shop_id;
        abort_unless($actor->id === $order->customer_id || $isOwnerOrStaff, 403, 'Unauthorized order cancellation.');
        abort_unless(
            (float) $order->amount_paid <= 0.0 && $order->order_status === Order::STATUS_PENDING_PAYMENT,
            422,
            'Only unpaid pending orders can be cancelled here.',
        );

        return DB::transaction(function () use ($order, $actor, $reason): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $this->inventoryDeductionService->releaseReservation(
                $lockedOrder,
                $actor,
                $reason ?? 'Order cancelled',
            );

            $lockedOrder->update([
                'order_status' => Order::STATUS_CANCELLED,
                'rejection_reason' => $reason,
            ]);

            $this->auditLogger->log(
                action: 'order.cancelled',
                auditable: $lockedOrder,
                printShopId: $lockedOrder->print_shop_id,
                actor: $actor,
                metadata: ['reason' => $reason],
            );

            return $lockedOrder->fresh() ?? $lockedOrder;
        });
    }

    /**
     * Recompute an order's paid total, remaining balance, and lifecycle statuses from verified ledger entries.
     */
    protected function recalculateOrderLedger(Order $order, User $verifier): void
    {
        $verifiedTotal = round((float) $order->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount'), 2);
        $orderTotal = round((float) $order->total_amount, 2);
        $balanceDue = max(0.0, round($orderTotal - $verifiedTotal, 2));

        $paymentStatus = $balanceDue <= 0.0
            ? Order::PAYMENT_VERIFIED_PAID
            : ($verifiedTotal > 0.0 ? Order::PAYMENT_PARTIALLY_PAID : Order::PAYMENT_UNPAID);

        $orderStatus = $order->order_status === Order::STATUS_PENDING_PAYMENT && $verifiedTotal > 0.0
            ? Order::STATUS_IN_QUEUE
            : $order->order_status;

        $order->amount_paid = $verifiedTotal;
        $order->balance_due = $balanceDue;
        $order->payment_status = $paymentStatus;
        $order->order_status = $orderStatus;
        $order->payment_verified_at = now();
        $order->payment_verified_by = $verifier->id;
        $order->rejection_reason = null;
        $order->save();
    }

    /**
     * Determine remaining collectible balance for orders (including legacy rows without balance_due initialized).
     */
    protected function effectiveBalanceDue(Order $order): float
    {
        if ((float) $order->amount_paid > 0.0 || (float) $order->balance_due > 0.0) {
            return max(0.0, round((float) $order->balance_due, 2));
        }

        if ($order->payment_status === Order::PAYMENT_VERIFIED_PAID) {
            return 0.0;
        }

        return max(0.0, round((float) $order->total_amount, 2));
    }
}
