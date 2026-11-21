<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SpecRevision;
use App\Models\User;
use App\Notifications\SpecRevisionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpecRevisionService
{
    public function __construct(
        private OrderCheckoutService $orderCheckoutService,
        private InventoryDeductionService $inventoryDeductionService,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Put an unprinted line item on hold and record a pending specification revision request.
     *
     * @param  array<string, mixed>  $newSpecs
     *
     * @throws ValidationException
     */
    public function requestRevision(
        OrderItem $item,
        User $actor,
        array $newSpecs,
        ?string $notes = null,
        ?string $revisionTypeOverride = null,
    ): SpecRevision {
        $item->loadMissing('order.printShop');
        $order = $item->order;
        $shop = $order?->printShop;

        abort_unless($order !== null && $shop !== null, 404);

        $isShopOperator = $actor->operationalShop()?->id === $order->print_shop_id;
        $isCustomerOwner = $actor->id === $order->customer_id;
        abort_unless($isShopOperator || $isCustomerOwner, 403, 'You are not authorized to revise this order item.');

        abort_if($item->hasStartedPrinting(), 422, 'Specifications are permanently locked once printing has started.');
        abort_if(
            in_array($order->order_status, [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED], true),
            422,
            'Completed or cancelled orders cannot be revised.',
        );
        abort_if(
            $item->specRevisions()->where('status', SpecRevision::STATUS_PENDING)->exists(),
            422,
            'A pending specification revision is already awaiting review for this item.',
        );

        $serviceKey = $item->resolvedServiceKey();
        $oldSpecs = $this->extractItemSnapshot($item);

        $merged = array_merge($oldSpecs, $newSpecs);
        $fulfillmentType = (string) ($merged['fulfillment_type'] ?? $item->fulfillment_type);
        $isCoverOnly = $serviceKey === 'thesis_binding' && $fulfillmentType === OrderItem::FULFILLMENT_COVER_ONLY;
        $copiesCount = max(1, (int) ($merged['copies_count'] ?? $item->copies_count));
        $paperSize = (string) ($merged['paper_size'] ?? $item->paper_size);
        $printSides = (string) ($merged['print_sides'] ?? $item->getPrintSides());

        if ($serviceKey === 'thesis_binding') {
            $bwPages = array_key_exists('bw_pages', $newSpecs)
                ? max(0, (int) $newSpecs['bw_pages'])
                : max(0, (int) ($merged['bw_pages_count'] ?? 0));
            $colorPages = array_key_exists('color_pages', $newSpecs)
                ? max(0, (int) $newSpecs['color_pages'])
                : max(0, (int) ($merged['color_pages_count'] ?? 0));
            $preprintedTotal = array_key_exists('preprinted_total_pages', $newSpecs)
                ? max(1, (int) $newSpecs['preprinted_total_pages'])
                : max(1, (int) ($merged['preprinted_total_pages'] ?? $item->total_pages_count));

            $bindingType = $isCoverOnly
                ? 'hardbound'
                : (string) ($merged['binding_type'] ?? $item->binding_type);
            $coverColor = $bindingType === 'hardbound'
                ? (string) ($merged['cover_color'] ?? $item->cover_color ?? 'Maroon')
                : 'Clear PVC / Standard Cardstock';
            $foilColor = $bindingType === 'hardbound'
                ? ($merged['foil_color'] ?? $item->foil_color ?? 'Gold')
                : null;
            $addonIds = is_array($merged['selected_addon_ids'] ?? null)
                ? $merged['selected_addon_ids']
                : [];

            $pricingInput = [
                'service_key' => 'thesis_binding',
                'fulfillment_type' => $fulfillmentType,
                'binding_type' => $bindingType,
                'bw_pages' => $bwPages,
                'color_pages' => $colorPages,
                'preprinted_total_pages' => $preprintedTotal,
                'paper_size' => $paperSize,
                'print_sides' => $printSides,
                'copies_count' => $copiesCount,
                'selected_addon_ids' => $addonIds,
            ];

            $this->orderCheckoutService->validateBindingLimits($shop, $pricingInput);
            $pricing = $this->orderCheckoutService->calculateThesisItemPricing($shop, $pricingInput);

            $normalizedNewSpecs = [
                'service_key' => 'thesis_binding',
                'fulfillment_type' => $fulfillmentType,
                'binding_type' => $bindingType,
                'bw_pages_count' => $pricing['bw_pages_count'],
                'color_pages_count' => $pricing['color_pages_count'],
                'total_pages_count' => $pricing['total_pages'],
                'paper_size' => $paperSize,
                'cover_color' => $coverColor,
                'foil_color' => $foilColor,
                'copies_count' => $copiesCount,
                'print_sides' => $printSides,
                'estimated_spine_thickness_mm' => $pricing['estimated_spine_mm'],
                'selected_addons' => $pricing['selected_addons_details'],
                'unit_price' => $pricing['unit_price'],
                'total_price' => $pricing['total_price'],
            ];
        } else {
            $totalPages = array_key_exists('total_pages', $newSpecs)
                ? max(1, (int) $newSpecs['total_pages'])
                : max(1, (int) ($merged['total_pages_count'] ?? $item->total_pages_count));
            $colorMode = (string) ($merged['color_mode'] ?? 'bw');
            $customColorPages = (string) ($merged['custom_color_pages'] ?? '');
            $paperStock = (string) ($merged['paper_stock'] ?? '70gsm');
            $finishingType = (string) ($merged['finishing_type'] ?? 'loose');
            $coverColor = (string) ($merged['ring_back_cover_color'] ?? $merged['cover_color'] ?? $item->cover_color ?? 'Blue');

            $pricingInput = [
                'service_key' => 'document_printing',
                'total_pages' => $totalPages,
                'color_mode' => $colorMode,
                'custom_color_pages' => $customColorPages,
                'color_pages_count' => $newSpecs['color_pages_count'] ?? null,
                'paper_size' => $paperSize,
                'paper_stock' => $paperStock,
                'print_sides' => $printSides,
                'finishing_type' => $finishingType,
                'copies_count' => $copiesCount,
            ];

            $pricing = $this->orderCheckoutService->calculateDocumentItemPricing($shop, $pricingInput);

            $normalizedNewSpecs = [
                'service_key' => 'document_printing',
                'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                'binding_type' => $finishingType === 'ring_bind' ? 'ring_bind' : 'none',
                'bw_pages_count' => $pricing['bw_pages_count'],
                'color_pages_count' => $pricing['color_pages_count'],
                'total_pages_count' => $pricing['total_pages'],
                'paper_size' => $paperSize,
                'cover_color' => $coverColor,
                'foil_color' => null,
                'copies_count' => $copiesCount,
                'print_sides' => $printSides,
                'finishing_type' => $finishingType,
                'paper_stock' => $paperStock,
                'color_mode' => $colorMode,
                'custom_color_pages' => $customColorPages,
                'estimated_spine_thickness_mm' => round($pricing['physical_sheets_per_copy'] * 0.1, 1),
                'selected_addons' => null,
                'unit_price' => $pricing['per_copy_total'],
                'total_price' => $pricing['total_price'],
            ];
        }

        $oldTotal = round((float) $item->total_price, 2);
        $newTotal = round((float) $normalizedNewSpecs['total_price'], 2);
        $delta = round($newTotal - $oldTotal, 2);

        $revisionType = $revisionTypeOverride ?? match (true) {
            $delta > 0.001 => SpecRevision::TYPE_UPGRADE,
            $delta < -0.001 => SpecRevision::TYPE_DOWNGRADE,
            default => SpecRevision::TYPE_NEUTRAL,
        };

        return DB::transaction(function () use (
            $item,
            $order,
            $actor,
            $oldSpecs,
            $normalizedNewSpecs,
            $oldTotal,
            $newTotal,
            $delta,
            $revisionType,
            $notes,
        ): SpecRevision {
            $item->update([
                'production_stage' => OrderItem::STAGE_ON_HOLD,
                'is_spec_locked' => false,
            ]);

            $revision = SpecRevision::create([
                'order_item_id' => $item->id,
                'requested_by' => $actor->id,
                'old_specs' => $oldSpecs,
                'new_specs' => $normalizedNewSpecs,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'delta_amount' => $delta,
                'revision_type' => $revisionType,
                'status' => SpecRevision::STATUS_PENDING,
                'notes' => $notes,
            ]);

            $order->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'spec_revision.requested',
                auditable: $revision,
                printShopId: $order->print_shop_id,
                actor: $actor,
                metadata: [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'revision_type' => $revisionType,
                    'delta_amount' => $delta,
                ],
            );

            $order->loadMissing(['printShop.user', 'customer']);
            $recipient = $actor->id === $order->customer_id ? $order->printShop?->user : $order->customer;
            if ($recipient instanceof User) {
                $recipient->notify((new SpecRevisionNotification($revision, 'requested'))->afterCommit());
            }

            return $revision;
        });
    }

    /**
     * Approve and atomically apply a specification revision, swapping BOM stock reservations and updating balances.
     *
     * @throws InsufficientStockException
     */
    public function approveAndApplyRevision(SpecRevision $revision, User $approver): SpecRevision
    {
        $revision->loadMissing('orderItem.order.printShop');
        $item = $revision->orderItem;
        $order = $item?->order;
        $shop = $order?->printShop;

        abort_unless($item instanceof OrderItem && $order instanceof Order && $shop !== null, 404);
        abort_unless($approver->operationalShop()?->id === $order->print_shop_id, 403, 'Unauthorized revision approval.');
        abort_unless($revision->status === SpecRevision::STATUS_PENDING, 422, 'Only pending revisions can be applied.');
        abort_if($item->hasStartedPrinting(), 422, 'Specifications are permanently locked once printing has started.');

        return DB::transaction(function () use ($revision, $item, $order, $approver): SpecRevision {
            /** @var SpecRevision $lockedRevision */
            $lockedRevision = SpecRevision::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            /** @var OrderItem $lockedItem */
            $lockedItem = OrderItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            abort_if($lockedItem->hasStartedPrinting(), 422, 'Specifications are permanently locked once printing has started.');

            $this->inventoryDeductionService->releaseReservationForItem(
                $lockedItem,
                $approver,
                "Spec revision #{$lockedRevision->id} approved",
            );

            $specs = $lockedRevision->new_specs;
            $customFields = is_array($lockedItem->custom_fields_data) ? $lockedItem->custom_fields_data : [];
            $serviceKey = (string) ($specs['service_key'] ?? $lockedItem->resolvedServiceKey());

            $customFields['service_key'] = $serviceKey;
            $customFields['print_sides'] = (string) ($specs['print_sides'] ?? $lockedItem->getPrintSides());
            if ($serviceKey === 'document_printing') {
                $customFields['finishing_type'] = (string) ($specs['finishing_type'] ?? 'loose');
                $customFields['paper_stock'] = (string) ($specs['paper_stock'] ?? '70gsm');
                $customFields['color_mode'] = (string) ($specs['color_mode'] ?? 'bw');
                $customFields['custom_color_pages'] = (string) ($specs['custom_color_pages'] ?? '');
            }

            $newFulfillment = (string) ($specs['fulfillment_type'] ?? $lockedItem->fulfillment_type);

            $lockedItem->update([
                'fulfillment_type' => $newFulfillment,
                'binding_type' => (string) ($specs['binding_type'] ?? $lockedItem->binding_type),
                'bw_pages_count' => (int) ($specs['bw_pages_count'] ?? $lockedItem->bw_pages_count),
                'color_pages_count' => (int) ($specs['color_pages_count'] ?? $lockedItem->color_pages_count),
                'total_pages_count' => (int) ($specs['total_pages_count'] ?? $lockedItem->total_pages_count),
                'paper_size' => (string) ($specs['paper_size'] ?? $lockedItem->paper_size),
                'cover_color' => $specs['cover_color'] ?? $lockedItem->cover_color,
                'foil_color' => $specs['foil_color'] ?? null,
                'copies_count' => max(1, (int) ($specs['copies_count'] ?? $lockedItem->copies_count)),
                'estimated_spine_thickness_mm' => (float) ($specs['estimated_spine_thickness_mm'] ?? $lockedItem->estimated_spine_thickness_mm),
                'selected_addons' => $specs['selected_addons'] ?? $lockedItem->selected_addons,
                'unit_price' => (float) ($specs['unit_price'] ?? $lockedItem->unit_price),
                'total_price' => (float) ($specs['total_price'] ?? $lockedItem->total_price),
                'custom_fields_data' => $customFields,
                'is_paper_received' => $newFulfillment === OrderItem::FULFILLMENT_FULL_PACKAGE ? true : $lockedItem->is_paper_received,
                'production_stage' => OrderItem::STAGE_QUEUE,
                'is_spec_locked' => $lockedItem->prepress_status === OrderItem::PREPRESS_APPROVED,
            ]);

            $this->inventoryDeductionService->reserveForOrderItem($lockedOrder, $lockedItem, $approver);

            $newSubtotal = round((float) $lockedOrder->items()->sum('total_price'), 2);
            $newGrandTotal = round($newSubtotal + (float) $lockedOrder->rush_fee_amount, 2);
            $paid = round((float) $lockedOrder->amount_paid, 2);

            $lockedOrder->subtotal_amount = $newSubtotal;
            $lockedOrder->total_amount = $newGrandTotal;

            if ($paid > $newGrandTotal + 0.001) {
                $lockedOrder->balance_due = 0.0;
                $lockedOrder->refund_due = round($paid - $newGrandTotal, 2);
                $lockedOrder->refund_status = Order::REFUND_MANUAL_PENDING;
                $lockedOrder->payment_status = Order::PAYMENT_VERIFIED_PAID;
            } else {
                $lockedOrder->balance_due = max(0.0, round($newGrandTotal - $paid, 2));
                $lockedOrder->refund_due = 0.0;
                $lockedOrder->refund_status = Order::REFUND_NONE;
                if ($paid > 0.0 && $lockedOrder->balance_due > 0.001) {
                    $lockedOrder->payment_status = Order::PAYMENT_PARTIALLY_PAID;
                } elseif ($paid > 0.0) {
                    $lockedOrder->payment_status = Order::PAYMENT_VERIFIED_PAID;
                }
            }

            $lockedOrder->save();
            $lockedOrder->syncParentStateFromItems();

            $lockedRevision->update([
                'status' => SpecRevision::STATUS_APPLIED,
                'approved_by' => $approver->id,
            ]);

            $this->auditLogger->log(
                action: 'spec_revision.applied',
                auditable: $lockedRevision,
                printShopId: $lockedOrder->print_shop_id,
                actor: $approver,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_item_id' => $lockedItem->id,
                    'new_order_total' => $newGrandTotal,
                    'balance_due' => $lockedOrder->balance_due,
                    'refund_due' => $lockedOrder->refund_due,
                ],
            );

            $freshRevision = $lockedRevision->fresh() ?? $lockedRevision;
            $lockedOrder->loadMissing('customer');
            if ($lockedOrder->customer instanceof User) {
                $lockedOrder->customer->notify((new SpecRevisionNotification($freshRevision, 'applied'))->afterCommit());
            }

            return $freshRevision;
        });
    }

    /**
     * Reject a pending specification revision request and return the item cleanly to the active queue.
     *
     * @throws ValidationException
     */
    public function rejectRevision(SpecRevision $revision, User $approver, string $reason): SpecRevision
    {
        $revision->loadMissing('orderItem.order');
        $item = $revision->orderItem;
        $order = $item?->order;

        abort_unless($item instanceof OrderItem && $order instanceof Order, 404);
        abort_unless($approver->operationalShop()?->id === $order->print_shop_id, 403, 'Unauthorized revision decision.');
        abort_unless($revision->status === SpecRevision::STATUS_PENDING, 422, 'Only pending revisions can be rejected.');

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Please provide a reason for rejecting this revision.',
            ]);
        }

        return DB::transaction(function () use ($revision, $item, $order, $approver, $reason): SpecRevision {
            $revision->update([
                'status' => SpecRevision::STATUS_REJECTED,
                'approved_by' => $approver->id,
                'notes' => trim(($revision->notes ? $revision->notes."\n" : '').'Rejected: '.trim($reason)),
            ]);

            if (($item->production_stage ?? OrderItem::STAGE_QUEUE) === OrderItem::STAGE_ON_HOLD && $item->prepress_status !== OrderItem::PREPRESS_REJECTED) {
                $item->update([
                    'production_stage' => OrderItem::STAGE_QUEUE,
                    'is_spec_locked' => $item->prepress_status === OrderItem::PREPRESS_APPROVED,
                ]);
            }

            $order->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'spec_revision.rejected',
                auditable: $revision,
                printShopId: $order->print_shop_id,
                actor: $approver,
                metadata: ['reason' => trim($reason)],
            );

            $freshRevision = $revision->fresh() ?? $revision;
            $order->loadMissing('customer');
            if ($order->customer instanceof User) {
                $order->customer->notify((new SpecRevisionNotification($freshRevision, 'rejected', trim($reason)))->afterCommit());
            }

            return $freshRevision;
        });
    }

    /**
     * Record a manual offline refund payout and clear refund_due on an overpaid downgraded order.
     */
    public function settleManualRefund(Order $order, User $actor, ?string $referenceNo = null, ?string $notes = null): Payment
    {
        abort_unless($actor->operationalShop()?->id === $order->print_shop_id, 403, 'Unauthorized refund settlement.');
        abort_unless(
            (float) $order->refund_due > 0.0 && $order->refund_status === Order::REFUND_MANUAL_PENDING,
            422,
            'This order has no pending manual refund.',
        );

        return DB::transaction(function () use ($order, $actor, $referenceNo, $notes): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $refundAmount = round((float) $lockedOrder->refund_due, 2);
            abort_if($refundAmount <= 0.0, 422, 'No positive refund balance exists.');

            $normalizedRef = $referenceNo !== null && trim($referenceNo) !== ''
                ? strtoupper(trim($referenceNo))
                : 'REFUND-'.strtoupper(substr(uniqid(), -6));

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'print_shop_id' => $lockedOrder->print_shop_id,
                'submitted_by' => $actor->id,
                'payment_type' => Payment::TYPE_MANUAL_REFUND,
                'payment_method' => 'cash_counter',
                'amount' => -$refundAmount,
                'reference_no' => $normalizedRef,
                'status' => Payment::STATUS_VERIFIED,
                'verified_by' => $actor->id,
                'submitted_at' => now(),
                'verified_at' => now(),
                'rejection_reason' => $notes,
            ]);

            $lockedOrder->update([
                'amount_paid' => max(0.0, round((float) $lockedOrder->total_amount, 2)),
                'refund_due' => 0.0,
                'refund_status' => Order::REFUND_SETTLED,
            ]);

            SpecRevision::query()
                ->whereIn('order_item_id', $lockedOrder->items()->pluck('id'))
                ->where('status', SpecRevision::STATUS_APPLIED)
                ->where('delta_amount', '<', 0)
                ->whereNull('settled_at')
                ->update(['settled_at' => now()]);

            $this->auditLogger->log(
                action: 'refund.settled',
                auditable: $payment,
                printShopId: $lockedOrder->print_shop_id,
                actor: $actor,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'refund_amount' => $refundAmount,
                    'reference_no' => $normalizedRef,
                ],
            );

            $latestRev = SpecRevision::whereIn('order_item_id', $lockedOrder->items()->pluck('id'))->latest()->first();
            $lockedOrder->loadMissing('customer');
            if ($latestRev instanceof SpecRevision && $lockedOrder->customer instanceof User) {
                $lockedOrder->customer->notify((new SpecRevisionNotification(
                    $latestRev,
                    'refund_settled',
                    'Settled manual refund of ₱'.number_format($refundAmount, 2)." (Ref: {$normalizedRef}).",
                ))->afterCommit());
            }

            return $payment;
        });
    }

    /**
     * Build a normalized JSON snapshot of current line-item specs for revision audit diffing.
     *
     * @return array<string, mixed>
     */
    protected function extractItemSnapshot(OrderItem $item): array
    {
        $customFields = is_array($item->custom_fields_data) ? $item->custom_fields_data : [];

        return [
            'service_key' => $item->resolvedServiceKey(),
            'fulfillment_type' => $item->fulfillment_type,
            'binding_type' => $item->binding_type,
            'bw_pages_count' => (int) $item->bw_pages_count,
            'color_pages_count' => (int) $item->color_pages_count,
            'total_pages_count' => (int) $item->total_pages_count,
            'paper_size' => $item->paper_size,
            'cover_color' => $item->cover_color,
            'foil_color' => $item->foil_color,
            'copies_count' => max(1, (int) $item->copies_count),
            'print_sides' => $item->getPrintSides(),
            'finishing_type' => $item->getFinishingType(),
            'paper_stock' => (string) ($customFields['paper_stock'] ?? '70gsm'),
            'color_mode' => (string) ($customFields['color_mode'] ?? 'bw'),
            'custom_color_pages' => (string) ($customFields['custom_color_pages'] ?? ''),
            'selected_addon_ids' => collect($item->selected_addons ?? [])->pluck('id')->filter()->values()->all(),
            'unit_price' => round((float) $item->unit_price, 2),
            'total_price' => round((float) $item->total_price, 2),
        ];
    }
}
