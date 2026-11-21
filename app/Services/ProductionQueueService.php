<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\SpecRevision;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\OrderPickupReadyNotification;
use App\Notifications\PrepressActionRequiredNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductionQueueService
{
    public function __construct(
        private InventoryDeductionService $inventoryDeductionService,
        private SpecRevisionService $specRevisionService,
        private OrderCheckoutService $orderCheckoutService,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Approve submitted prepress artwork and lock specifications prior to printing.
     */
    public function approvePrepress(OrderItem $item, User $staff): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);

        abort_if(
            $item->isCoverOnly() && ! $item->is_paper_received,
            422,
            'Confirm physical customer paper intake before approving Cover-Only prepress.',
        );

        $item->update([
            'prepress_status' => OrderItem::PREPRESS_APPROVED,
            'prepress_rejection_reason' => null,
            'is_spec_locked' => true,
            'production_stage' => ($item->production_stage ?? OrderItem::STAGE_QUEUE) === OrderItem::STAGE_ON_HOLD
                ? OrderItem::STAGE_QUEUE
                : ($item->production_stage ?? OrderItem::STAGE_QUEUE),
        ]);

        $order->syncParentStateFromItems();

        $this->auditLogger->log(
            action: 'prepress.approved',
            auditable: $item,
            printShopId: $order->print_shop_id,
            actor: $staff,
            metadata: ['order_id' => $order->id],
        );

        return $item->fresh() ?? $item;
    }

    /**
     * Reject defective artwork and place the item on hold until customer re-upload.
     *
     * @throws ValidationException
     */
    public function rejectPrepress(OrderItem $item, User $staff, string $reason): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);
        abort_if($item->hasStartedPrinting(), 422, 'Cannot reject prepress once printing has started.');

        $trimmed = trim($reason);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'reason' => 'A prepress rejection reason is required.',
            ]);
        }

        $item->update([
            'prepress_status' => OrderItem::PREPRESS_REJECTED,
            'prepress_rejection_reason' => $trimmed,
            'is_spec_locked' => false,
            'production_stage' => OrderItem::STAGE_ON_HOLD,
        ]);

        $order->syncParentStateFromItems();

        $this->auditLogger->log(
            action: 'prepress.rejected',
            auditable: $item,
            printShopId: $order->print_shop_id,
            actor: $staff,
            metadata: ['order_id' => $order->id, 'reason' => $trimmed],
        );

        $order->loadMissing('customer');
        if ($order->customer instanceof User) {
            $order->customer->notify((new PrepressActionRequiredNotification($item, 'rejected', $trimmed))->afterCommit());
        }

        return $item->fresh() ?? $item;
    }

    /**
     * Allow the owning customer to replace a rejected manuscript/document file and return the item to queue.
     *
     * @throws ValidationException
     */
    public function reuploadArtwork(OrderItem $item, User $customer, UploadedFile $file): OrderItem
    {
        $item->loadMissing('order');
        $order = $item->order;

        abort_unless($order instanceof Order, 404);
        abort_unless($order->customer_id === $customer->id, 403, 'You do not own this order item.');
        abort_unless($item->prepress_status === OrderItem::PREPRESS_REJECTED, 422, 'Artwork replacement is only allowed for rejected prepress items.');
        abort_if($item->hasStartedPrinting(), 422, 'Cannot re-upload artwork once printing has started.');

        $this->orderCheckoutService->validateUploadFile($file, true, 'artwork_file');

        $folder = $item->isThesisBinding() ? 'orders/manuscripts' : 'orders/documents';
        $storedPath = $file->store($folder, 'local');
        $oldPath = $item->document_file_path;

        $item->update([
            'document_file_path' => is_string($storedPath) ? $storedPath : $oldPath,
            'document_original_name' => $file->getClientOriginalName(),
            'prepress_status' => OrderItem::PREPRESS_PENDING,
            'prepress_rejection_reason' => null,
            'is_spec_locked' => false,
            'production_stage' => OrderItem::STAGE_QUEUE,
        ]);

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $storedPath) {
            Storage::disk('local')->delete($oldPath);
        }

        $order->syncParentStateFromItems();

        $this->auditLogger->log(
            action: 'prepress.artwork_reuploaded',
            auditable: $item,
            printShopId: $order->print_shop_id,
            actor: $customer,
            metadata: ['order_id' => $order->id],
        );

        $order->loadMissing('printShop.user');
        $owner = $order->printShop?->user;
        if ($owner instanceof User) {
            $owner->notify((new PrepressActionRequiredNotification($item, 'reuploaded'))->afterCommit());
        }

        return $item->fresh() ?? $item;
    }

    /**
     * Confirm customer-supplied physical pages meet shop standards for Cover-Only binding.
     */
    public function confirmCoverOnlyPaperIntake(OrderItem $item, User $staff): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);
        abort_unless($item->isCoverOnly(), 422, 'Only Cover-Only items require physical paper intake verification.');

        $item->update([
            'is_paper_received' => true,
            'prepress_status' => OrderItem::PREPRESS_APPROVED,
            'prepress_rejection_reason' => null,
            'is_spec_locked' => true,
        ]);

        $this->auditLogger->log(
            action: 'prepress.cover_only_paper_received',
            auditable: $item,
            printShopId: $order->print_shop_id,
            actor: $staff,
            metadata: ['order_id' => $order->id],
        );

        return $item->fresh() ?? $item;
    }

    /**
     * Reject defective customer-supplied Cover-Only paper and shift the job onto shop-supplied paper with BOM/total update.
     *
     * @throws ValidationException
     */
    public function rejectCoverOnlyAndShiftToShopPaper(
        OrderItem $item,
        User $staff,
        string $reason,
        ?int $bwPages = null,
        int $colorPages = 0,
    ): SpecRevision {
        $order = $this->authorizeItemOperator($item, $staff);
        abort_unless($item->isCoverOnly(), 422, 'Item is not in Cover-Only mode.');
        abort_if($item->hasStartedPrinting(), 422, 'Cannot shift paper source after printing has started.');

        $trimmed = trim($reason);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'reason' => 'Please enter why the customer-supplied paper was rejected.',
            ]);
        }

        $resolvedColor = max(0, $colorPages);
        $resolvedBw = $bwPages !== null
            ? max(0, $bwPages)
            : max(0, max(1, (int) $item->total_pages_count) - $resolvedColor);

        return DB::transaction(function () use ($item, $order, $staff, $trimmed, $resolvedBw, $resolvedColor): SpecRevision {
            $revision = $this->specRevisionService->requestRevision(
                item: $item,
                actor: $staff,
                newSpecs: [
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'bw_pages' => $resolvedBw,
                    'color_pages' => $resolvedColor,
                ],
                notes: "Cover-Only paper rejected: {$trimmed}",
                revisionTypeOverride: SpecRevision::TYPE_COVER_ONLY_TO_FULL,
            );

            $applied = $this->specRevisionService->approveAndApplyRevision($revision, $staff);

            $this->auditLogger->log(
                action: 'prepress.cover_only_shifted_to_shop_paper',
                auditable: $item->fresh() ?? $item,
                printShopId: $order->print_shop_id,
                actor: $staff,
                metadata: [
                    'order_id' => $order->id,
                    'spec_revision_id' => $applied->id,
                    'reason' => $trimmed,
                ],
            );

            $order->loadMissing('customer');
            if ($order->customer instanceof User) {
                $order->customer->notify((new PrepressActionRequiredNotification($item->fresh() ?? $item, 'shifted_to_shop_paper', $trimmed))->afterCommit());
            }

            return $applied;
        });
    }

    /**
     * Assign a printer workstation and/or staff operator to a specific line item.
     *
     * @throws ValidationException
     */
    public function assignItem(OrderItem $item, User $actor, ?int $printerId = null, ?int $staffId = null): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $actor);

        if ($printerId !== null) {
            $printer = Printer::where('print_shop_id', $order->print_shop_id)->find($printerId);
            if (! $printer instanceof Printer) {
                throw ValidationException::withMessages([
                    'printer_id' => 'Selected printer does not belong to this shop.',
                ]);
            }

            if (! $printer->isOnline()) {
                throw ValidationException::withMessages([
                    'printer_id' => "Printer '{$printer->name}' is currently {$printer->status} and cannot accept jobs.",
                ]);
            }

            if (! $printer->supportsSize($item->paper_size)) {
                throw ValidationException::withMessages([
                    'printer_id' => "Printer '{$printer->name}' does not support paper size {$item->paper_size}.",
                ]);
            }
        }

        if ($staffId !== null) {
            $assignee = User::find($staffId);
            $belongsToShop = $assignee !== null
                && $assignee->operationalShop()?->id === $order->print_shop_id;
            if (! $belongsToShop) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => 'Selected operator does not belong to this shop.',
                ]);
            }
        }

        $item->update([
            'printer_id' => $printerId,
            'assigned_staff_id' => $staffId,
        ]);

        $this->auditLogger->log(
            action: 'production.item_assigned',
            auditable: $item,
            printShopId: $order->print_shop_id,
            actor: $actor,
            metadata: ['printer_id' => $printerId, 'assigned_staff_id' => $staffId],
        );

        return $item->fresh() ?? $item;
    }

    /**
     * Update printer operational status and batch-failover active queued/printing items when offline or in maintenance.
     *
     * @throws ValidationException
     */
    public function setPrinterStatusWithFailover(
        Printer $printer,
        User $actor,
        string $newStatus,
        ?int $targetPrinterId = null,
    ): int {
        abort_unless($actor->operationalShop()?->id === $printer->print_shop_id, 403, 'Unauthorized printer update.');
        abort_unless(
            in_array($newStatus, [Printer::STATUS_ONLINE, Printer::STATUS_MAINTENANCE, Printer::STATUS_OFFLINE], true),
            422,
            'Invalid printer status.',
        );

        if ($targetPrinterId !== null) {
            $target = Printer::where('print_shop_id', $printer->print_shop_id)->find($targetPrinterId);
            if (! $target instanceof Printer || $target->id === $printer->id || ! $target->isOnline()) {
                throw ValidationException::withMessages([
                    'target_printer_id' => 'Failover printer must be a different online printer in this shop.',
                ]);
            }
        }

        return DB::transaction(function () use ($printer, $actor, $newStatus, $targetPrinterId): int {
            $printer->update(['status' => $newStatus]);

            if ($newStatus === Printer::STATUS_ONLINE) {
                return 0;
            }

            $impacted = OrderItem::query()
                ->where('printer_id', $printer->id)
                ->whereIn('production_stage', [OrderItem::STAGE_QUEUE, OrderItem::STAGE_PRINTING])
                ->whereHas('order', function ($query) use ($printer): void {
                    $query->where('print_shop_id', $printer->print_shop_id)
                        ->whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED]);
                })
                ->get();

            if ($impacted->isEmpty()) {
                return 0;
            }

            if ($targetPrinterId !== null) {
                /** @var Printer $target */
                $target = Printer::where('print_shop_id', $printer->print_shop_id)->findOrFail($targetPrinterId);
                foreach ($impacted as $impactedItem) {
                    if (! $target->supportsSize($impactedItem->paper_size)) {
                        throw ValidationException::withMessages([
                            'target_printer_id' => "Failover printer '{$target->name}' cannot handle item size {$impactedItem->paper_size}.",
                        ]);
                    }
                }
            }

            foreach ($impacted as $impactedItem) {
                $impactedItem->update(['printer_id' => $targetPrinterId]);
            }

            $this->auditLogger->log(
                action: 'printer.failover_reassigned',
                auditable: $printer,
                printShopId: $printer->print_shop_id,
                actor: $actor,
                metadata: [
                    'new_status' => $newStatus,
                    'target_printer_id' => $targetPrinterId,
                    'reassigned_count' => $impacted->count(),
                ],
            );

            return $impacted->count();
        });
    }

    /**
     * Start printing an item, locking specs and committing reserved stock into physical deduction.
     */
    public function startPrintingItem(OrderItem $item, User $staff): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);

        abort_if(
            in_array($order->order_status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_CANCELLED], true),
            422,
            'Order must clear initial payment verification before printing starts.',
        );
        abort_if(($item->production_stage ?? OrderItem::STAGE_QUEUE) === OrderItem::STAGE_ON_HOLD, 422, 'Item is currently on hold.');
        abort_if($item->prepress_status === OrderItem::PREPRESS_REJECTED, 422, 'Prepress artwork was rejected and awaits re-upload.');
        abort_if($item->isCoverOnly() && ! $item->is_paper_received, 422, 'Confirm Cover-Only paper intake before starting.');

        return DB::transaction(function () use ($item, $order, $staff): OrderItem {
            $item->update([
                'production_stage' => OrderItem::STAGE_PRINTING,
                'assigned_staff_id' => $item->assigned_staff_id ?? $staff->id,
                'is_spec_locked' => true,
            ]);

            $this->inventoryDeductionService->commitReservationOnPrint($order, $staff, $item);
            $order->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'production.item_printing_started',
                auditable: $item,
                printShopId: $order->print_shop_id,
                actor: $staff,
                metadata: ['order_id' => $order->id],
            );

            return $item->fresh() ?? $item;
        });
    }

    /**
     * Advance an item along its intermediate production stages before final QC sign-off.
     */
    public function advanceItemStage(OrderItem $item, User $staff): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);
        $current = $item->production_stage ?? OrderItem::STAGE_QUEUE;

        if ($current === OrderItem::STAGE_QUEUE) {
            return $this->startPrintingItem($item, $staff);
        }

        $next = match ($current) {
            OrderItem::STAGE_PRINTING => $item->isThesisBinding() ? OrderItem::STAGE_BINDING : OrderItem::STAGE_QUALITY_CHECK,
            OrderItem::STAGE_BINDING => OrderItem::STAGE_QUALITY_CHECK,
            default => $current,
        };

        if ($next !== $current) {
            $item->update([
                'production_stage' => $next,
                'assigned_staff_id' => $item->assigned_staff_id ?? $staff->id,
            ]);
            $order->syncParentStateFromItems();
        }

        return $item->fresh() ?? $item;
    }

    /**
     * Pass QC for an item, assign shelf/bin location, stamp ready_at, and mark parent Partially Ready or Ready.
     *
     * @throws ValidationException
     */
    public function passQualityCheck(OrderItem $item, User $staff, string $shelfBinCode): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);
        $trimmedShelf = trim($shelfBinCode);

        if ($trimmedShelf === '') {
            throw ValidationException::withMessages([
                'shelf_bin_code' => 'Please enter a shelf or bin location code before marking ready for pickup.',
            ]);
        }

        return DB::transaction(function () use ($item, $order, $staff, $trimmedShelf): OrderItem {
            $this->inventoryDeductionService->commitReservationOnPrint($order, $staff, $item);

            $item->update([
                'production_stage' => OrderItem::STAGE_READY_FOR_PICKUP,
                'shelf_bin_code' => $trimmedShelf,
                'ready_at' => $item->ready_at ?? now(),
                'is_spec_locked' => true,
            ]);

            $order->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'qa.item_passed',
                auditable: $item,
                printShopId: $order->print_shop_id,
                actor: $staff,
                metadata: [
                    'order_id' => $order->id,
                    'shelf_bin_code' => $trimmedShelf,
                    'order_status' => $order->order_status,
                ],
            );

            $freshItem = $item->fresh() ?? $item;
            $order->loadMissing('customer');
            if ($order->customer instanceof User) {
                $order->customer->notify((new OrderPickupReadyNotification(
                    $freshItem,
                    $order->order_status === Order::STATUS_PARTIALLY_READY,
                ))->afterCommit());
            }

            return $freshItem;
        });
    }

    /**
     * Fail QC and log dual Paper + Ink/Toner rework spoilage linked to order_item_id without changing customer billing.
     *
     * @return array<int, StockMovement>
     *
     * @throws ValidationException
     */
    public function failQualityCheckWithDualSpoilage(
        OrderItem $item,
        User $staff,
        string $reason,
        float $spoiledSheets,
        ?int $paperItemId = null,
        ?int $inkItemId = null,
        ?float $inkQty = null,
    ): array {
        $order = $this->authorizeItemOperator($item, $staff);
        $shop = $order->printShop;
        abort_unless($shop !== null, 404);

        $trimmed = trim($reason);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'reason' => 'Please specify the QC defect reason.',
            ]);
        }

        if ($spoiledSheets <= 0.0) {
            throw ValidationException::withMessages([
                'spoiled_sheets' => 'Spoiled sheet count must be greater than zero.',
            ]);
        }

        $resolved = $this->inventoryDeductionService->resolvePaperAndInkItemsForItem($shop, $order, $item);
        $paperItem = $paperItemId !== null
            ? InventoryItem::where('print_shop_id', $shop->id)->find($paperItemId)
            : $resolved['paper'];
        $inkItem = $inkItemId !== null
            ? InventoryItem::where('print_shop_id', $shop->id)->find($inkItemId)
            : $resolved['ink'];

        return DB::transaction(function () use ($item, $order, $staff, $trimmed, $spoiledSheets, $paperItem, $inkItem, $inkQty, $resolved): array {
            $movements = [];

            if ($paperItem instanceof InventoryItem) {
                $movements[] = $this->inventoryDeductionService->recordSpoilage(
                    item: $paperItem,
                    wastedQty: $spoiledSheets,
                    reason: "QA Rework (#{$order->order_number} / Item #{$item->id}): {$trimmed}",
                    staff: $staff,
                    orderItemId: $item->id,
                    movementType: StockMovement::TYPE_REWORK_SPOILAGE,
                );
            }

            if ($inkItem instanceof InventoryItem) {
                $computedInk = $inkQty !== null && $inkQty > 0.0
                    ? round($inkQty, 2)
                    : max(0.01, round($spoiledSheets * $resolved['ink_per_sheet'], 2));

                $movements[] = $this->inventoryDeductionService->recordSpoilage(
                    item: $inkItem,
                    wastedQty: $computedInk,
                    reason: "QA Ink/Toner Rework (#{$order->order_number} / Item #{$item->id}): {$trimmed}",
                    staff: $staff,
                    orderItemId: $item->id,
                    movementType: StockMovement::TYPE_REWORK_SPOILAGE,
                );
            }

            $item->update([
                'production_stage' => OrderItem::STAGE_PRINTING,
                'shelf_bin_code' => null,
                'ready_at' => null,
            ]);

            $notePrefix = '['.now()->format('M d, H:i').'] QA Rework (Item #'.$item->id.'): ';
            $order->staff_notes = ($order->staff_notes ? $order->staff_notes."\n" : '').$notePrefix.$trimmed;
            $order->save();
            $order->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'qa.item_failed_rework_spoilage',
                auditable: $item,
                printShopId: $order->print_shop_id,
                actor: $staff,
                metadata: [
                    'order_id' => $order->id,
                    'reason' => $trimmed,
                    'spoiled_sheets' => $spoiledSheets,
                    'movement_ids' => array_map(fn (StockMovement $m): int => $m->id, $movements),
                ],
            );

            return $movements;
        });
    }

    /**
     * Claim a ready line item at pickup, enforcing the zero-balance gate before final parent order completion.
     *
     * @throws ValidationException
     */
    public function claimItem(OrderItem $item, User $staff): OrderItem
    {
        $order = $this->authorizeItemOperator($item, $staff);
        abort_unless($item->isReadyForPickup(), 422, 'Item must be Ready for Pickup before claiming.');

        return DB::transaction(function () use ($item, $order, $staff): OrderItem {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $unclaimedSiblingCount = $lockedOrder->items()
                ->where('id', '!=', $item->id)
                ->whereNull('claimed_at')
                ->where('production_stage', '!=', OrderItem::STAGE_CLAIMED)
                ->count();

            if ($unclaimedSiblingCount === 0 && (float) $lockedOrder->balance_due > 0.001) {
                $bal = number_format((float) $lockedOrder->balance_due, 2);
                throw ValidationException::withMessages([
                    'balance_due' => "Settle the remaining balance of ₱{$bal} before releasing the final item.",
                ]);
            }

            $item->update([
                'production_stage' => OrderItem::STAGE_CLAIMED,
                'claimed_at' => now(),
            ]);

            $lockedOrder->syncParentStateFromItems();

            $this->auditLogger->log(
                action: 'production.item_claimed',
                auditable: $item,
                printShopId: $lockedOrder->print_shop_id,
                actor: $staff,
                metadata: [
                    'order_id' => $lockedOrder->id,
                    'order_status' => $lockedOrder->order_status,
                ],
            );

            return $item->fresh() ?? $item;
        });
    }

    protected function authorizeItemOperator(OrderItem $item, User $actor): Order
    {
        $item->loadMissing('order.printShop');
        $order = $item->order;
        $shop = $actor->operationalShop();

        abort_unless($order instanceof Order, 404);
        abort_unless($shop !== null && $shop->id === $order->print_shop_id, 403, 'Unauthorized shop operation.');

        return $order;
    }
}
