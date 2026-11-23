<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Printer;
use App\Models\PrintShop;
use App\Models\SpecRevision;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryDeductionService;
use App\Services\PaymentService;
use App\Services\ProductionQueueService;
use App\Services\SpecRevisionService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Production Operations Hub')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $searchQuery = '';
    public string $selectedService = 'all'; // all, thesis_binding, document_printing
    public string $selectedFilter = 'all'; // all, rush_only, cover_only, full_package, hardbound, softbound, ring_bind, folder, staple, loose
    public string $selectedView = 'kanban'; // kanban, list

    // Job Ticket Modal
    public bool $showJobTicketModal = false;
    public ?int $selectedOrderId = null;
    public ?string $assignedMachine = '';
    public ?string $staffNotes = '';

    // QC Step-Back Modal
    public bool $showRejectModal = false;
    public ?int $rejectOrderId = null;
    public string $rejectReason = '';

    // Spoilage Modal
    public bool $showSpoilageModal = false;
    public ?int $spoilageItemId = null;
    public float $spoilageQty = 1;
    public string $spoilageReason = '';

    // WP-3 Direct Browser Print Preview Modal
    public bool $showPrintPreviewModal = false;
    #[Locked]
    public ?int $printPreviewItemId = null;

    // WP-3 Prepress Reject Modal
    public bool $showPrepressRejectModal = false;
    #[Locked]
    public ?int $prepressItemId = null;
    public string $prepressRejectReason = '';

    // WP-3 Cover-Only -> Shop Paper Shift Modal
    public bool $showShiftToShopPaperModal = false;
    #[Locked]
    public ?int $shiftingItemId = null;
    public string $shiftReason = '';
    public int $shiftBwPages = 100;
    public int $shiftColorPages = 0;

    // WP-3 Item QA Pass (Shelf Bin) Modal
    public bool $showItemQaPassModal = false;
    #[Locked]
    public ?int $qaPassItemId = null;
    public string $qaPassShelfBin = '';

    // WP-3 Item QA Fail (Dual Paper + Ink Rework Spoilage) Modal
    public bool $showItemQaFailModal = false;
    #[Locked]
    public ?int $qaFailItemId = null;
    public string $qaFailReason = '';
    public float $qaFailSpoiledSheets = 10;
    public ?int $qaFailPaperItemId = null;
    public ?int $qaFailInkItemId = null;
    public float $qaFailInkQty = 1;

    public function getShopProperty(): ?PrintShop
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->isOwner() && $user->printShop) {
            return $user->printShop;
        }

        // Default or first active shop for staff
        return $user->operationalShop();
    }

    public function getOrdersProperty()
    {
        $shop = $this->shop;
        if (! $shop) {
            return collect();
        }

        $query = Order::where('print_shop_id', $shop->id)
            ->where('payment_status', '!=', Order::PAYMENT_REJECTED)
            ->where('order_status', '!=', Order::STATUS_CANCELLED)
            ->with(['customer', 'items.printer', 'items.assignedStaff', 'items.specRevisions', 'assignedStaff'])
            ->latest('created_at');

        if ($this->selectedService !== 'all') {
            $query->where(function ($q): void {
                $q->where('service_key', $this->selectedService)
                    ->orWhereHas('items', fn ($iq) => $iq->where('service_key', $this->selectedService));
            });
        }

        if ($this->selectedFilter === 'rush_only') {
            $query->where('is_rush', true);
        } elseif ($this->selectedFilter === 'cover_only') {
            $query->whereHas('items', fn ($q) => $q->where('fulfillment_type', 'cover_only'));
        } elseif ($this->selectedFilter === 'full_package') {
            $query->whereHas('items', fn ($q) => $q->where('fulfillment_type', 'full_package'));
        } elseif ($this->selectedFilter === 'hardbound') {
            $query->whereHas('items', fn ($q) => $q->where('binding_type', 'hardbound'));
        } elseif ($this->selectedFilter === 'softbound') {
            $query->whereHas('items', fn ($q) => $q->where('binding_type', 'softbound'));
        } elseif ($this->selectedFilter === 'ring_bind') {
            $query->whereHas('items', fn ($q) => $q->where('custom_fields_data->finishing_type', 'ring_bind'));
        } elseif ($this->selectedFilter === 'folder') {
            $query->whereHas('items', fn ($q) => $q->where('custom_fields_data->finishing_type', 'folder'));
        } elseif ($this->selectedFilter === 'staple') {
            $query->whereHas('items', fn ($q) => $q->where('custom_fields_data->finishing_type', 'staple'));
        } elseif ($this->selectedFilter === 'loose') {
            $query->whereHas('items', fn ($q) => $q->where('custom_fields_data->finishing_type', 'loose'));
        }

        if (trim($this->searchQuery) !== '') {
            $search = '%' . trim($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
                    ->orWhere('tracking_code', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('items', fn ($i) => $i->where('custom_fields_data', 'like', $search));
            });
        }

        return $query->get();
    }

    public function getInventoryItemsProperty()
    {
        $shop = $this->shop;
        if (! $shop) {
            return collect();
        }

        return InventoryItem::where('print_shop_id', $shop->id)->orderBy('name')->get();
    }

    public function getSelectedOrderProperty(): ?Order
    {
        if (! $this->selectedOrderId) {
            return null;
        }

        return Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)
            ->with(['customer', 'items.printer', 'items.assignedStaff', 'items.specRevisions', 'assignedStaff'])
            ->find($this->selectedOrderId);
    }

    public function getShopPrintersProperty()
    {
        $shop = $this->shop;
        if (! $shop) {
            return collect();
        }

        return $shop->printers()->get();
    }

    public function getShopOperatorsProperty()
    {
        $shop = $this->shop;
        if (! $shop) {
            return collect();
        }

        return User::query()
            ->where('id', $shop->user_id)
            ->orWhere('staff_shop_id', $shop->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Mark customer pre-printed paper as received at shop counter.
     */
    public function markPaperReceived(int $orderId): void
    {
        $order = Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->with('items')->find($orderId);
        if (! $order) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->isCoverOnly()) {
                $item->update(['is_paper_received' => true]);
            }
        }

        Flux::toast(
            text: "Physical paper for Order {$order->order_number} marked received at counter.",
            heading: 'Paper Intake Confirmed',
            variant: 'success'
        );
    }

    /**
     * Advance order to the next production stage.
     */
    public function advanceOrder(int $orderId, InventoryDeductionService $deductionService): void
    {
        $order = Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->with(['items', 'printShop'])->find($orderId);
        if (! $order) {
            return;
        }

        $user = auth()->user();
        $prevStage = $order->production_stage ?? Order::STAGE_QUEUE;

        $order->advanceStage($user?->id);

        $newStage = $order->production_stage;

        // If advanced to ready_for_pickup or completed, trigger automated BOM inventory deduction
        if (in_array($newStage, [Order::STAGE_READY_FOR_PICKUP, Order::STAGE_COMPLETED]) && ! in_array($prevStage, [Order::STAGE_READY_FOR_PICKUP, Order::STAGE_COMPLETED])) {
            $deductions = $deductionService->deductForOrder($order, $user);
            
            $summaryText = count($deductions) > 0 
                ? count($deductions) . ' raw materials automatically deducted from inventory.'
                : 'Order moved to pickup bay.';

            Flux::toast(
                text: "Order {$order->order_number} advanced to Ready for Pickup. {$summaryText}",
                heading: 'Stage Completed & Materials Consumed',
                variant: 'success'
            );
        } else {
            Flux::toast(
                text: "Order {$order->order_number} advanced to {$order->stageLabel()}.",
                heading: 'Stage Advanced',
                variant: 'success'
            );
        }
    }

    /**
     * Open Digital Job Ticket modal.
     */
    public function viewJobTicket(int $orderId): void
    {
        $order = Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->with(['customer', 'items', 'assignedStaff'])->find($orderId);
        if (! $order) {
            return;
        }

        $this->selectedOrderId = $orderId;
        $this->assignedMachine = $order->assigned_machine ?? 'Canon Laser ImageRUNNER Pro 1';
        $this->staffNotes = $order->staff_notes ?? '';
        $this->showJobTicketModal = true;
    }

    /**
     * Save machine and operator notes on job ticket.
     */
    public function saveJobDetails(): void
    {
        if (! $this->selectedOrderId) {
            return;
        }

        $order = Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->find($this->selectedOrderId);
        if ($order) {
            $order->update([
                'assigned_machine' => $this->assignedMachine,
                'staff_notes' => $this->staffNotes,
                'assigned_staff_id' => auth()->id(),
            ]);

            Flux::toast(
                text: "Job ticket specifications and machine assignment updated.",
                heading: 'Job Details Saved',
                variant: 'success'
            );
        }

        $this->showJobTicketModal = false;
    }

    /**
     * Verify payment directly from staff counter floor.
     */
    public function verifyPayment(int $orderId): void
    {
        $user = auth()->user();
        $shopId = $user?->operationalShop()?->id ?? 0;
        $order = Order::where('print_shop_id', $shopId)->find($orderId);
        if (! $order || ! $user) {
            return;
        }

        $pendingPayment = $order->payments()->where('status', Payment::STATUS_PENDING)->latest()->first();
        if ($pendingPayment) {
            app(PaymentService::class)->verifyPayment($pendingPayment, $user);
        } else {
            $remaining = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
            if ($remaining > 0.0) {
                app(PaymentService::class)->recordOfflineCounterPayment($order, $user, $remaining);
            } else {
                $order->update([
                    'payment_status' => Order::PAYMENT_VERIFIED_PAID,
                    'payment_verified_at' => now(),
                    'payment_verified_by' => $user->id,
                ]);
            }
        }

        Flux::toast(
            text: "Payment for Order {$order->order_number} verified and confirmed at counter.",
            heading: 'Payment Confirmed',
            variant: 'success'
        );
    }

    /**
     * Collect remaining balance at pickup / counter via PaymentService.
     */
    public function settleCounterBalance(int $orderId): void
    {
        $user = auth()->user();
        $shopId = $user?->operationalShop()?->id ?? 0;
        $order = Order::where('print_shop_id', $shopId)->find($orderId);
        if (! $order || ! $user) {
            return;
        }

        $remaining = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
        if ($remaining > 0.0) {
            app(PaymentService::class)->recordOfflineCounterPayment($order, $user, $remaining);
        }

        Flux::toast(
            text: "Remaining balance for Order {$order->order_number} settled at counter.",
            heading: 'Balance Settled',
            variant: 'success'
        );
    }

    /**
     * Open QC step-back modal.
     */
    public function openRejectModal(int $orderId): void
    {
        $this->rejectOrderId = $orderId;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    /**
     * Step back an order if QC failed.
     */
    public function submitQcRejection(): void
    {
        if (! $this->rejectOrderId) {
            return;
        }

        $order = Order::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->find($this->rejectOrderId);
        if ($order) {
            $order->stepBackStage($this->rejectReason ?: 'Quality check issue detected.');

            Flux::toast(
                text: "Order {$order->order_number} stepped back to {$order->stageLabel()}.",
                heading: 'QC Rejection Logged',
                variant: 'warning'
            );
        }

        $this->showRejectModal = false;
        $this->rejectOrderId = null;
    }

    /**
     * Open Material Spoilage modal.
     */
    public function openSpoilageModal(): void
    {
        $items = $this->inventoryItems;
        $this->spoilageItemId = $items->first()?->id;
        $this->spoilageQty = 1;
        $this->spoilageReason = '';
        $this->showSpoilageModal = true;
    }

    /**
     * Submit reported material wastage/spoilage.
     */
    public function submitSpoilage(InventoryDeductionService $deductionService): void
    {
        $this->validate([
            'spoilageItemId' => 'required|exists:inventory_items,id',
            'spoilageQty' => 'required|numeric|min:0.1',
            'spoilageReason' => 'required|string|min:3',
        ]);

        $item = InventoryItem::where('print_shop_id', auth()->user()?->operationalShop()?->id ?? 0)->find($this->spoilageItemId);
        if ($item) {
            $deductionService->recordSpoilage($item, (float) $this->spoilageQty, $this->spoilageReason, auth()->user());

            Flux::toast(
                text: "Reported {$this->spoilageQty} {$item->unit} of {$item->name} as spoilage. Stock adjusted.",
                heading: 'Spoilage Logged',
                variant: 'danger'
            );
        }

        $this->showSpoilageModal = false;
    }

    public function openPrintPreview(int $itemId): void
    {
        $item = $this->findShopOrderItem($itemId);
        if (! $item || ! $item->document_file_path) {
            return;
        }

        $this->printPreviewItemId = $item->id;
        $this->showPrintPreviewModal = true;
    }

    public function assignItemPrinter(int $itemId, mixed $printerId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        $resolvedPrinterId = is_numeric($printerId) && (int) $printerId > 0 ? (int) $printerId : null;
        app(ProductionQueueService::class)->assignItem($item, $user, $resolvedPrinterId, $item->assigned_staff_id);
        Flux::toast(text: 'Printer workstation assignment updated.', heading: 'Printer Assigned', variant: 'success');
    }

    public function assignItemStaff(int $itemId, mixed $staffId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        $resolvedStaffId = is_numeric($staffId) && (int) $staffId > 0 ? (int) $staffId : null;
        app(ProductionQueueService::class)->assignItem($item, $user, $item->printer_id, $resolvedStaffId);
        Flux::toast(text: 'Operator assignment updated.', heading: 'Operator Assigned', variant: 'success');
    }

    public function approveItemPrepress(int $itemId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->approvePrepress($item, $user);
        Flux::toast(text: 'Prepress artwork approved and specifications locked.', heading: 'Prepress Approved', variant: 'success');
    }

    public function openItemPrepressRejectModal(int $itemId): void
    {
        $item = $this->findShopOrderItem($itemId);
        if (! $item) {
            return;
        }

        $this->prepressItemId = $item->id;
        $this->prepressRejectReason = '';
        $this->showPrepressRejectModal = true;
    }

    public function confirmPrepressRejection(): void
    {
        $user = auth()->user();
        $item = $this->prepressItemId ? $this->findShopOrderItem($this->prepressItemId) : null;
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->rejectPrepress($item, $user, $this->prepressRejectReason);
        $this->showPrepressRejectModal = false;
        $this->prepressItemId = null;
        Flux::toast(text: 'Artwork rejected and item placed on hold.', heading: 'Prepress Rejected', variant: 'warning');
    }

    public function confirmCoverOnlyIntake(int $itemId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->confirmCoverOnlyPaperIntake($item, $user);
        Flux::toast(text: 'Customer paper intake confirmed and specifications locked.', heading: 'Paper Intake Approved', variant: 'success');
    }

    public function openShiftToShopPaperModal(int $itemId): void
    {
        $item = $this->findShopOrderItem($itemId);
        if (! $item) {
            return;
        }

        $this->shiftingItemId = $item->id;
        $this->shiftReason = '';
        $this->shiftBwPages = max(1, (int) $item->total_pages_count);
        $this->shiftColorPages = 0;
        $this->showShiftToShopPaperModal = true;
    }

    public function confirmShiftToShopPaper(): void
    {
        $user = auth()->user();
        $item = $this->shiftingItemId ? $this->findShopOrderItem($this->shiftingItemId) : null;
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->rejectCoverOnlyAndShiftToShopPaper(
            item: $item,
            staff: $user,
            reason: $this->shiftReason,
            bwPages: $this->shiftBwPages,
            colorPages: $this->shiftColorPages,
        );

        $this->showShiftToShopPaperModal = false;
        $this->shiftingItemId = null;
        Flux::toast(text: 'Item shifted to Full Package (Shop Paper) with recalculated total and locked BOM stock.', heading: 'Shifted to Shop Paper', variant: 'success');
    }

    public function startItemPrinting(int $itemId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->startPrintingItem($item, $user);
        Flux::toast(text: 'Item printing started and specs locked.', heading: 'Printing Started', variant: 'success');
    }

    public function advanceItemWorkflow(int $itemId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        $updated = app(ProductionQueueService::class)->advanceItemStage($item, $user);
        Flux::toast(text: "Item moved to {$updated->stageLabel()}.", heading: 'Item Stage Updated', variant: 'success');
    }

    public function openItemQaPassModal(int $itemId): void
    {
        $item = $this->findShopOrderItem($itemId);
        if (! $item) {
            return;
        }

        $this->qaPassItemId = $item->id;
        $this->qaPassShelfBin = $item->shelf_bin_code ?: 'BIN-A1';
        $this->showItemQaPassModal = true;
    }

    public function confirmItemQaPass(): void
    {
        $user = auth()->user();
        $item = $this->qaPassItemId ? $this->findShopOrderItem($this->qaPassItemId) : null;
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->passQualityCheck($item, $user, $this->qaPassShelfBin);
        $this->showItemQaPassModal = false;
        $this->qaPassItemId = null;
        Flux::toast(text: 'QA passed and item assigned to pickup bin.', heading: 'Ready for Pickup', variant: 'success');
    }

    public function openItemQaFailModal(int $itemId): void
    {
        $item = $this->findShopOrderItem($itemId);
        $shop = $this->shop;
        if (! $item || ! $shop || ! $item->order) {
            return;
        }

        $resolved = app(InventoryDeductionService::class)->resolvePaperAndInkItemsForItem($shop, $item->order, $item);

        $this->qaFailItemId = $item->id;
        $this->qaFailReason = '';
        $this->qaFailSpoiledSheets = max(1.0, (float) min(10, max(1, $item->getPhysicalSheetsCount())));
        $this->qaFailPaperItemId = $resolved['paper']?->id;
        $this->qaFailInkItemId = $resolved['ink']?->id;
        $this->qaFailInkQty = max(0.1, round($this->qaFailSpoiledSheets * $resolved['ink_per_sheet'], 2));
        $this->showItemQaFailModal = true;
    }

    public function confirmItemQaFail(): void
    {
        $user = auth()->user();
        $item = $this->qaFailItemId ? $this->findShopOrderItem($this->qaFailItemId) : null;
        if (! $user || ! $item) {
            return;
        }

        app(ProductionQueueService::class)->failQualityCheckWithDualSpoilage(
            item: $item,
            staff: $user,
            reason: $this->qaFailReason,
            spoiledSheets: (float) $this->qaFailSpoiledSheets,
            paperItemId: $this->qaFailPaperItemId,
            inkItemId: $this->qaFailInkItemId,
            inkQty: (float) $this->qaFailInkQty,
        );

        $this->showItemQaFailModal = false;
        $this->qaFailItemId = null;
        Flux::toast(text: 'QA rework logged for paper and ink without changing customer billing.', heading: 'QA Rework Logged', variant: 'warning');
    }

    public function claimOrderItem(int $itemId): void
    {
        $user = auth()->user();
        $item = $this->findShopOrderItem($itemId);
        if (! $user || ! $item) {
            return;
        }

        try {
            app(ProductionQueueService::class)->claimItem($item, $user);
            Flux::toast(text: 'Item marked claimed.', heading: 'Item Claimed', variant: 'success');
        } catch (ValidationException $e) {
            Flux::toast(text: collect($e->errors())->flatten()->first() ?? 'Settle remaining balance before final release.', heading: 'Balance Required', variant: 'danger');
        }
    }

    public function approveItemRevision(int $revisionId): void
    {
        $user = auth()->user();
        $shopId = $this->shop?->id ?? 0;
        $revision = SpecRevision::whereHas('orderItem.order', fn ($q) => $q->where('print_shop_id', $shopId))->findOrFail($revisionId);
        if (! $user) {
            return;
        }

        app(SpecRevisionService::class)->approveAndApplyRevision($revision, $user);
        Flux::toast(text: 'Specification revision applied and BOM reservations updated.', heading: 'Revision Applied', variant: 'success');
    }

    public function rejectItemRevision(int $revisionId): void
    {
        $user = auth()->user();
        $shopId = $this->shop?->id ?? 0;
        $revision = SpecRevision::whereHas('orderItem.order', fn ($q) => $q->where('print_shop_id', $shopId))->findOrFail($revisionId);
        if (! $user) {
            return;
        }

        app(SpecRevisionService::class)->rejectRevision($revision, $user, 'Rejected by production floor');
        Flux::toast(text: 'Revision rejected and item returned to queue.', heading: 'Revision Rejected', variant: 'warning');
    }

    public function settleOrderManualRefund(int $orderId): void
    {
        $user = auth()->user();
        $shopId = $this->shop?->id ?? 0;
        $order = Order::where('print_shop_id', $shopId)->findOrFail($orderId);
        if (! $user) {
            return;
        }

        app(SpecRevisionService::class)->settleManualRefund($order, $user);
        Flux::toast(text: 'Manual refund recorded and cleared.', heading: 'Refund Settled', variant: 'success');
    }

    protected function findShopOrderItem(int $itemId): ?OrderItem
    {
        $shopId = $this->shop?->id ?? 0;

        return OrderItem::with(['order', 'printer'])
            ->whereHas('order', fn ($q) => $q->where('print_shop_id', $shopId))
            ->find($itemId);
    }
}; ?>

<div class="min-h-screen w-full pq-page flex flex-col justify-between relative">
    @php
        $user = auth()->user();
        $shop = $this->shop;
        $allOrders = $this->orders;
        $totalActive = $allOrders->whereNotIn('order_status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])->count();
        $rushCount = $allOrders->where('is_rush', true)->whereNotIn('order_status', [Order::STATUS_COMPLETED])->count();
        $pendingPaper = $allOrders->filter(fn ($o) => $o->isPaperIntakePending())->count();
        $readyPickup = $allOrders->where('order_status', Order::STATUS_READY_FOR_PICKUP)->count();
    @endphp

    <!-- Top Navigation Header -->
    <header class="w-full pq-topbar sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-3">
        <!-- Brand & Hub Identity -->
        <div class="flex items-center gap-3.5 min-w-0">
            @if ($user?->isOwner())
                <x-app-back-link :href="route('owner.dashboard')" label="App Launcher" :navigate="true" />
            @endif
            <span class="hidden sm:flex size-10 items-center justify-center rounded-xl bg-amber-500 text-stone-950 font-black shadow-sm shrink-0">
                <flux:icon name="queue-list" class="size-5" />
            </span>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-bold text-stone-900 dark:text-white tracking-tight truncate">
                    Production Operations Hub
                </h1>
                <p class="text-[11px] text-stone-600 dark:text-stone-400 truncate">{{ $shop ? $shop->name : 'PressQueue' }} &bull; Digital Job Queue, Machine Scheduling & BOM Consumption</p>
            </div>
        </div>

        <!-- Quick Actions & User Navigation -->
        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
            <button 
                wire:click="openSpoilageModal"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-stone-950 hover:bg-red-50 dark:hover:bg-red-500/10 text-stone-700 dark:text-stone-300 hover:text-red-700 dark:hover:text-red-400 border border-stone-300 dark:border-stone-800 hover:border-red-300 dark:hover:border-red-500/30 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
                <flux:icon name="trash" class="size-3.5 text-red-600 dark:text-red-400" />
                <span class="hidden sm:inline">Report Spoilage</span>
            </button>

            <!-- Notifications & Theme Toggle -->
            <livewire:notification-bell />
            <x-theme-toggle />

            <!-- Profile Settings Link -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2 bg-stone-50 dark:bg-stone-950 border border-stone-200 dark:border-stone-800 hover:border-amber-400 dark:hover:border-amber-500/50 rounded-xl py-1 px-2.5 transition-colors cursor-pointer"
                title="Account Profile Settings"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-amber-500/40" />
                @else
                    <span class="size-6 rounded-full bg-amber-500 text-stone-950 text-[10px] font-black flex items-center justify-center">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <div class="leading-tight hidden md:block text-left">
                    <p class="text-xs font-bold text-stone-800 dark:text-stone-200 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $user?->name }}</p>
                    <p class="text-[9px] text-stone-500 uppercase font-semibold">{{ $user?->isOwner() ? 'Owner' : 'Floor Staff' }}</p>
                </div>
            </a>

            <!-- Log Out Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                @csrf
                <button 
                    type="submit" 
                    title="Log Out"
                    class="p-2 sm:px-3 sm:py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-stone-950 hover:bg-red-50 dark:hover:bg-red-500/10 text-red-700 dark:text-red-400 border border-stone-300 dark:border-stone-800 hover:border-red-300 dark:hover:border-red-500/30 transition-colors flex items-center gap-1.5 shadow-sm cursor-pointer"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                    <span class="hidden sm:inline">Log Out</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Operations Hub Body -->
    <main class="flex-1 w-full px-4 sm:px-8 py-6 space-y-6 max-w-full">
        
        <!-- Live Floor KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="pq-card p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Active Jobs in Floor</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white mt-0.5 tabular-nums">{{ $totalActive }}</h3>
                </div>
                <div class="size-11 rounded-xl pq-badge-info flex items-center justify-center">
                    <flux:icon name="printer" class="size-5" />
                </div>
            </div>

            <div class="pq-card p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Rush Jobs Active</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-amber-700 dark:text-amber-400 mt-0.5 tabular-nums">{{ $rushCount }}</h3>
                </div>
                <div class="size-11 rounded-xl pq-badge-warning flex items-center justify-center">
                    <flux:icon name="bolt" class="size-5" />
                </div>
            </div>

            <div class="pq-card p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400">Paper Drop-offs Needed</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-purple-700 dark:text-purple-400 mt-0.5 tabular-nums">{{ $pendingPaper }}</h3>
                </div>
                <div class="size-11 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 dark:bg-purple-500/15 dark:border-purple-500/30 dark:text-purple-300 flex items-center justify-center">
                    <flux:icon name="archive-box" class="size-5" />
                </div>
            </div>

            <div class="pq-card p-4 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Ready for Pickup</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5 tabular-nums">{{ $readyPickup }}</h3>
                </div>
                <div class="size-11 rounded-xl pq-badge-success flex items-center justify-center">
                    <flux:icon name="check-circle" class="size-5" />
                </div>
            </div>
        </div>

        <!-- Search, Filters & View Controls Bar -->
        <div class="pq-card p-3.5 flex flex-col gap-3.5">
            <!-- Top Row: Service Category Tabs & View Switcher -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-stone-200 dark:border-stone-800 pb-3">
                <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
                    <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider mr-1 hidden sm:inline">Category:</span>
                    <button 
                        wire:click="$set('selectedService', 'all')"
                        class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer {{ $selectedService === 'all' ? 'bg-amber-500 text-stone-950 shadow-sm font-black' : 'bg-stone-50 dark:bg-stone-950 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white border border-stone-200 dark:border-stone-800 font-bold' }}"
                    >
                        All Services
                    </button>
                    <button 
                        wire:click="$set('selectedService', 'thesis_binding')"
                        class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer inline-flex items-center gap-1.5 {{ $selectedService === 'thesis_binding' ? 'pq-badge-warning font-bold' : 'bg-stone-50 dark:bg-stone-950 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white border border-stone-200 dark:border-stone-800 font-bold' }}"
                    >
                        <flux:icon name="book-open" class="size-3.5" />
                        <span>Thesis Binding</span>
                    </button>
                    <button 
                        wire:click="$set('selectedService', 'document_printing')"
                        class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer inline-flex items-center gap-1.5 {{ $selectedService === 'document_printing' ? 'pq-badge-info font-bold' : 'bg-stone-50 dark:bg-stone-950 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white border border-stone-200 dark:border-stone-800 font-bold' }}"
                    >
                        <flux:icon name="document-text" class="size-3.5" />
                        <span>Document Printing</span>
                    </button>
                </div>

                <div class="flex items-center bg-stone-100 dark:bg-stone-950 p-0.5 rounded-xl border border-stone-200 dark:border-stone-800 shrink-0 self-end sm:self-auto">
                    <button 
                        wire:click="$set('selectedView', 'kanban')" 
                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $selectedView === 'kanban' ? 'bg-white dark:bg-stone-800 text-amber-700 dark:text-amber-400 shadow-sm' : 'text-stone-500 hover:text-stone-800 dark:hover:text-stone-300' }}"
                        title="Kanban Board View"
                    >
                        <flux:icon name="squares-2x2" class="size-4" />
                    </button>
                    <button 
                        wire:click="$set('selectedView', 'list')" 
                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $selectedView === 'list' ? 'bg-white dark:bg-stone-800 text-amber-700 dark:text-amber-400 shadow-sm' : 'text-stone-500 hover:text-stone-800 dark:hover:text-stone-300' }}"
                        title="Tabular List View"
                    >
                        <flux:icon name="queue-list" class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Bottom Row: Search & Sub-filters -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="relative w-full md:w-80">
                    <flux:icon name="magnifying-glass" class="size-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input 
                        wire:model.live.debounce.200ms="searchQuery" 
                        type="text" 
                        placeholder="Search Order #, Customer Name, Title..."
                        class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl pl-10 pr-4 py-1.5 text-xs text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-500 outline-none transition-colors"
                    />
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                    @php
                        if ($selectedService === 'document_printing') {
                            $filters = [
                                'all' => 'All Docs',
                                'rush_only' => 'Rush Only',
                                'ring_bind' => 'Ring Bound',
                                'folder' => 'Folders',
                                'staple' => 'Stapled',
                                'loose' => 'Loose Sheets',
                            ];
                        } elseif ($selectedService === 'thesis_binding') {
                            $filters = [
                                'all' => 'All Thesis',
                                'rush_only' => 'Rush Only',
                                'cover_only' => 'Cover Only',
                                'full_package' => 'Full Package',
                                'hardbound' => 'Hardbound',
                                'softbound' => 'Softbound',
                            ];
                        } else {
                            $filters = [
                                'all' => 'All Jobs',
                                'rush_only' => 'Rush Only',
                                'cover_only' => 'Cover Only',
                                'full_package' => 'Full Package',
                                'hardbound' => 'Hardbound',
                                'softbound' => 'Softbound',
                            ];
                        }
                    @endphp

                    @foreach ($filters as $key => $label)
                        <button 
                            wire:click="$set('selectedFilter', '{{ $key }}')"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-colors cursor-pointer inline-flex items-center gap-1 {{ $selectedFilter === $key ? 'bg-stone-900 dark:bg-stone-800 text-white border border-stone-900 dark:border-stone-700' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800/50' }}"
                        >
                            @if ($key === 'rush_only')
                                <flux:icon name="bolt" class="size-3 text-amber-500" />
                            @endif
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 5-Stage Kanban Board -->
        @if ($selectedView === 'kanban')
            @php
                $stages = [
                    Order::STAGE_QUEUE => [
                        'title' => '1. In Queue / Ready',
                        'icon' => 'clock',
                        'color' => 'border-sky-200 dark:border-blue-500/30 text-sky-800 dark:text-blue-400',
                        'headerBg' => 'bg-sky-50 dark:bg-blue-500/10',
                        'nextAction' => 'Start Printing',
                        'nextIcon' => 'printer',
                    ],
                    Order::STAGE_PRINTING => [
                        'title' => '2. Printing Pages',
                        'icon' => 'printer',
                        'color' => 'border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-400',
                        'headerBg' => 'bg-amber-50 dark:bg-amber-500/10',
                        'nextAction' => 'Move to Binding',
                        'nextIcon' => 'book-open',
                    ],
                    Order::STAGE_BINDING => [
                        'title' => '3. Cover & Foil Stamping',
                        'icon' => 'book-open',
                        'color' => 'border-purple-200 dark:border-purple-500/30 text-purple-800 dark:text-purple-400',
                        'headerBg' => 'bg-purple-50 dark:bg-purple-500/10',
                        'nextAction' => 'Send to QC',
                        'nextIcon' => 'magnifying-glass',
                    ],
                    Order::STAGE_QUALITY_CHECK => [
                        'title' => '4. Quality Inspection (QC)',
                        'icon' => 'check-badge',
                        'color' => 'border-cyan-200 dark:border-cyan-500/30 text-cyan-800 dark:text-cyan-400',
                        'headerBg' => 'bg-cyan-50 dark:bg-cyan-500/10',
                        'nextAction' => 'Pass & Ready',
                        'nextIcon' => 'check',
                    ],
                    Order::STAGE_READY_FOR_PICKUP => [
                        'title' => '5. Ready for Pickup',
                        'icon' => 'gift',
                        'color' => 'border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-400',
                        'headerBg' => 'bg-emerald-50 dark:bg-emerald-500/10',
                        'nextAction' => 'Mark Picked Up',
                        'nextIcon' => 'archive-box',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4 items-start w-full">
                @foreach ($stages as $stageKey => $stageMeta)
                    @php
                        $stageOrders = $allOrders->filter(function ($order) use ($stageKey) {
                            $current = $order->production_stage ?? Order::STAGE_QUEUE;
                            return $current === $stageKey;
                        });
                    @endphp

                    <div class="pq-card flex flex-col overflow-hidden">
                        <!-- Column Header -->
                        <div class="p-3.5 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between gap-2 {{ $stageMeta['headerBg'] }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-bold text-xs text-stone-900 dark:text-white tracking-tight truncate">{{ $stageMeta['title'] }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold tabular-nums shrink-0 {{ $stageMeta['color'] }} bg-white dark:bg-stone-950 border">
                                {{ $stageOrders->count() }}
                            </span>
                        </div>

                        <!-- Column Cards Container -->
                        <div class="p-3 space-y-3 min-h-[500px] max-h-[780px] overflow-y-auto">
                            @forelse ($stageOrders as $order)
                                @php
                                    $item = $order->items->first();
                                    $isCoverOnly = $item?->isCoverOnly() ?? false;
                                    $isPendingPaper = $order->isPaperIntakePending();
                                @endphp

                                <div class="p-3.5 rounded-xl bg-stone-50 dark:bg-stone-950 border {{ $order->is_rush ? 'border-amber-400 dark:border-amber-500/60 shadow-sm ring-1 ring-amber-400/40 dark:ring-amber-500/30' : 'border-stone-200 dark:border-stone-800/90 hover:border-stone-300 dark:hover:border-stone-700' }} transition-colors space-y-3">
                                    
                                    <!-- Card Top Row: Order Number, File Link & Payment / Rush -->
                                    <div class="flex items-center justify-between gap-1.5 flex-wrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-xs font-bold text-stone-900 dark:text-white tracking-wider">
                                                {{ $order->order_number }}
                                            </span>
                                            @if ($item?->document_file_path)
                                                <button
                                                    type="button"
                                                    wire:click="openPrintPreview({{ $item->id }})"
                                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold pq-badge-info hover:bg-sky-100 dark:hover:bg-sky-500/30 flex items-center gap-1 transition-colors cursor-pointer"
                                                    title="Direct Print Preview: {{ $item->document_original_name }}"
                                                >
                                                    <flux:icon name="printer" class="size-2.5" />
                                                    <span>Print Preview</span>
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border {{ $order->paymentStatusBadgeColor() }}">
                                                {{ $order->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID ? 'PAID' : ($order->payment_status === \App\Models\Order::PAYMENT_UNPAID ? 'UNPAID' : 'PENDING') }}
                                            </span>
                                            @if ($order->is_rush)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold pq-badge-warning inline-flex items-center gap-1">
                                                    <flux:icon name="bolt" class="size-3" />
                                                    <span>RUSH</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Service Tag & Customer Name -->
                                    @php
                                        $isDocPrinting = $order->service_key === 'document_printing' || ($item && $item->isDocumentPrinting());
                                    @endphp
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if ($isDocPrinting)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-info inline-flex items-center gap-1">
                                                    <flux:icon name="document-text" class="size-3" />
                                                    <span>Doc Print</span>
                                                </span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-stone-200/80 dark:bg-stone-800 text-stone-700 dark:text-stone-300">
                                                    {{ $item?->paper_size ?? 'Letter' }}
                                                </span>
                                                <span class="text-[10px] font-semibold text-stone-600 dark:text-stone-400 tabular-nums">
                                                    x{{ $item?->copies_count ?? 1 }} copy
                                                </span>
                                            @else
                                                @if ($isCoverOnly)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30 inline-flex items-center gap-1">
                                                        <flux:icon name="archive-box" class="size-3" />
                                                        <span>Cover Only</span>
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-info inline-flex items-center gap-1">
                                                        <flux:icon name="document-duplicate" class="size-3" />
                                                        <span>Full Package</span>
                                                    </span>
                                                @endif

                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-stone-200/80 dark:bg-stone-800 text-stone-700 dark:text-stone-300">
                                                    {{ ucfirst($item?->binding_type ?? 'Hardbound') }}
                                                </span>
                                                <span class="text-[10px] font-semibold text-stone-600 dark:text-stone-400 tabular-nums">
                                                    x{{ $item?->copies_count ?? 1 }} copy
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-bold text-stone-900 dark:text-stone-100 mt-1.5 truncate">
                                            {{ $order->customer->name ?? 'Customer' }}
                                        </p>
                                    </div>

                                    <!-- Physical Paper Intake Banner (For Thesis Cover-Only) -->
                                    @if (! $isDocPrinting && $isCoverOnly)
                                        @if ($isPendingPaper)
                                            <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 space-y-1.5">
                                                <p class="text-[10px] font-bold text-amber-800 dark:text-amber-400 flex items-center gap-1">
                                                    <flux:icon name="clock" class="size-3 shrink-0" />
                                                    <span>Paper Drop-off Needed</span>
                                                </p>
                                                <button 
                                                    wire:click="markPaperReceived({{ $order->id }})"
                                                    class="w-full py-1 rounded-md text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-stone-950 transition-colors flex items-center justify-center gap-1 shadow-sm cursor-pointer"
                                                >
                                                    <flux:icon name="check" class="size-3" />
                                                    <span>Mark Paper Received</span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="px-2 py-1 rounded-lg pq-badge-success text-[10px] font-bold flex items-center gap-1.5">
                                                <flux:icon name="check-circle" class="size-3 shrink-0" />
                                                <span>Paper In-Shop & Received</span>
                                            </div>
                                        @endif
                                    @endif

                                    <!-- Page Breakdown & Specs Badge -->
                                    @if ($isDocPrinting)
                                        <div class="grid grid-cols-2 gap-1.5 py-1.5 text-[11px] border-y border-stone-200 dark:border-stone-800">
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400 text-[10px] block">Pages (B/W + Col)</span>
                                                <span class="font-bold text-stone-800 dark:text-stone-300 tabular-nums">
                                                    {{ $item?->bw_pages_count ?? 0 }} / {{ $item?->color_pages_count ?? 0 }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400 text-[10px] block">Paper Feed</span>
                                                <span class="font-bold text-sky-700 dark:text-blue-400 font-mono tabular-nums">
                                                    {{ $item?->getPhysicalSheetsCount() }} sh ({{ $item?->isDuplex() ? 'Duplex' : 'Simplex' }})
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 text-[10px] flex-wrap">
                                            <span class="px-1.5 py-0.5 rounded bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-stone-700 dark:text-stone-300 font-medium">
                                                {{ $item?->getFinishingLabel() }}
                                            </span>
                                            @if ($item?->cover_color && $item?->getFinishingType() === 'ring_bind')
                                                <span class="px-1.5 py-0.5 rounded bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400">
                                                    Cover: {{ $item->cover_color }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="grid grid-cols-2 gap-1.5 py-1.5 text-[11px] border-y border-stone-200 dark:border-stone-800">
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400 text-[10px] block">Pages (B/W + Col)</span>
                                                <span class="font-bold text-stone-800 dark:text-stone-300 tabular-nums">
                                                    {{ $item?->bw_pages_count ?? 0 }} / {{ $item?->color_pages_count ?? 0 }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400 text-[10px] block">Spine Width</span>
                                                <span class="font-bold text-amber-700 dark:text-amber-400 font-mono tabular-nums">
                                                    ~{{ $item?->estimated_spine_thickness_mm ?? '15.0' }} mm
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Cover & Foil Color Chips -->
                                        @if ($item?->cover_color || $item?->foil_color)
                                            <div class="flex items-center gap-1.5 text-[10px] flex-wrap">
                                                @if ($item?->cover_color)
                                                    <span class="px-1.5 py-0.5 rounded bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-stone-700 dark:text-stone-300 font-medium">
                                                        Cover: {{ $item->cover_color }}
                                                    </span>
                                                @endif
                                                @if ($item?->foil_color)
                                                    <span class="px-1.5 py-0.5 rounded pq-badge-warning font-medium">
                                                        Foil: {{ $item->foil_color }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @endif

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1.5 pt-1">
                                        <button 
                                            wire:click="viewJobTicket({{ $order->id }})"
                                            class="py-1.5 px-2.5 rounded-lg text-xs font-bold bg-white dark:bg-stone-900 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-300 dark:border-stone-800 transition-colors flex items-center justify-center gap-1 whitespace-nowrap shrink-0 cursor-pointer"
                                        >
                                            <flux:icon name="document-text" class="size-3 text-stone-500 dark:text-stone-400" />
                                            <span>Ticket</span>
                                        </button>

                                        <!-- QC Rejection Step Back (Only in QC stage) -->
                                        @if ($stageKey === Order::STAGE_QUALITY_CHECK)
                                            <button 
                                                wire:click="openRejectModal({{ $order->id }})"
                                                class="p-1.5 rounded-lg text-xs font-bold pq-badge-danger hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors shrink-0 cursor-pointer"
                                                title="Step Back / QC Reject"
                                            >
                                                <flux:icon name="arrow-uturn-left" class="size-3.5" />
                                            </button>
                                        @endif

                                        <!-- Forward Advance Button -->
                                        <button 
                                            wire:click="advanceOrder({{ $order->id }})"
                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-stone-950 transition-colors flex items-center justify-center gap-1 shadow-sm whitespace-nowrap cursor-pointer"
                                        >
                                            <span>{{ $stageMeta['nextAction'] }}</span>
                                            <flux:icon name="arrow-right" class="size-3 shrink-0" />
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="py-16 text-center text-stone-500 dark:text-stone-500">
                                    <flux:icon name="inbox" class="size-7 mx-auto mb-2 opacity-40" />
                                    <p class="text-xs">No orders in this stage</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Tabular List View -->
            <div class="pq-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300">
                        <thead class="bg-stone-50 dark:bg-stone-950/90 border-b border-stone-200 dark:border-stone-800 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                            <tr>
                                <th class="p-4">Order Code</th>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Service & Mode</th>
                                <th class="p-4">Spine & Pages</th>
                                <th class="p-4">Current Stage</th>
                                <th class="p-4">Intake Status</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 dark:divide-stone-800/60 font-medium">
                            @forelse ($allOrders as $order)
                                @php
                                    $item = $order->items->first();
                                    $isCoverOnly = $item?->isCoverOnly() ?? false;
                                @endphp
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-900 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-stone-900 dark:text-white text-xs">{{ $order->order_number }}</span>
                                            @if ($order->is_rush)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold pq-badge-warning">
                                                    RUSH
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-stone-500 dark:text-stone-400">{{ $order->created_at->format('M d, H:i') }}</span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-stone-900 dark:text-white">{{ $order->customer->name ?? 'Customer' }}</p>
                                        <p class="text-[10px] text-stone-500 dark:text-stone-400">{{ $order->customer->email ?? '' }}</p>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isCoverOnly ? 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30' : 'pq-badge-info' }}">
                                                {{ $isCoverOnly ? 'Cover Only' : 'Full Package' }}
                                            </span>
                                            <span class="text-stone-600 dark:text-stone-400">({{ ucfirst($item?->binding_type ?? 'Hardbound') }})</span>
                                        </div>
                                    </td>
                                    <td class="p-4 tabular-nums">
                                        <p class="font-bold text-stone-800 dark:text-stone-200">{{ $item?->total_pages_count ?? 0 }} pages</p>
                                        <p class="text-[10px] text-amber-700 dark:text-amber-400 font-mono">Spine: ~{{ $item?->estimated_spine_thickness_mm ?? '15.0' }}mm</p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $order->statusBadgeColor() }} border">
                                            {{ $order->stageLabel() }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        @if ($isCoverOnly)
                                            @if ($order->isPaperIntakePending())
                                                <button 
                                                    wire:click="markPaperReceived({{ $order->id }})"
                                                    class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-500 text-stone-950 hover:bg-amber-600 transition-colors cursor-pointer"
                                                >
                                                    Mark Paper Received
                                                </button>
                                            @else
                                                <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                                    <flux:icon name="check-circle" class="size-3" />
                                                    Received
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-stone-500 dark:text-stone-400">PDF Uploaded</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right space-x-1.5 whitespace-nowrap">
                                        <button 
                                            wire:click="viewJobTicket({{ $order->id }})"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-stone-950 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-300 dark:border-stone-800 transition-colors cursor-pointer"
                                        >
                                            Ticket
                                        </button>
                                        <button 
                                            wire:click="advanceOrder({{ $order->id }})"
                                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-stone-950 transition-colors cursor-pointer"
                                        >
                                            Advance
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-12 text-center text-stone-500 dark:text-stone-400">No orders matching your criteria</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>

    <!-- Footer Bar -->
    <footer class="w-full bg-white dark:bg-stone-900 border-t border-stone-200 dark:border-stone-800 px-4 sm:px-8 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-500 dark:text-stone-400 gap-2">
        <span>&copy; {{ date('Y') }} {{ $shop ? $shop->name : 'PressQueue' }} &bull; Floor Operations System</span>
        <span class="font-medium tabular-nums">
            Active in Floor: {{ $totalActive }} jobs &bull; Ready: {{ $readyPickup }}
        </span>
    </footer>

    <!-- Digital Job Ticket Modal -->
    @if ($showJobTicketModal && $this->selectedOrder)
        @php
            $ticketOrder = $this->selectedOrder;
            $ticketItem = $ticketOrder->items->first();
            $customFields = is_array($ticketItem?->custom_fields_data) ? $ticketItem->custom_fields_data : [];
        @endphp

        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden shadow-xl">
                
                <!-- Ticket Header -->
                <div class="p-5 border-b border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950/80 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="size-10 rounded-xl pq-badge-warning flex items-center justify-center shrink-0">
                            <flux:icon name="ticket" class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-bold text-stone-900 dark:text-white">Digital Job Ticket: {{ $ticketOrder->order_number }}</h3>
                                @if ($ticketOrder->tracking_code)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold pq-badge-neutral">{{ $ticketOrder->tracking_code }}</span>
                                @endif
                                @if ($ticketOrder->is_rush)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-warning inline-flex items-center gap-1">
                                        <flux:icon name="bolt" class="size-3" />
                                        <span>RUSH ORDER</span>
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-stone-600 dark:text-stone-400 tabular-nums">
                                Customer: {{ $ticketOrder->customer->name }} ({{ $ticketOrder->customer->email }})
                                &bull; Paid: ₱{{ number_format((float) $ticketOrder->amount_paid, 2) }}
                                &bull; Balance: ₱{{ number_format(max(0.0, (float) $ticketOrder->total_amount - (float) $ticketOrder->amount_paid), 2) }}
                            </p>
                        </div>
                    </div>

                    <button 
                        wire:click="$set('showJobTicketModal', false)"
                        class="p-2 rounded-xl text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white hover:bg-stone-200/70 dark:hover:bg-stone-800 transition-colors cursor-pointer"
                        aria-label="Close ticket modal"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <!-- Ticket Scrollable Body -->
                <div class="p-6 overflow-y-auto space-y-6 text-xs text-stone-700 dark:text-stone-300">
                    
                    <!-- Specifications Grid -->
                    @php
                        $isTicketDoc = $ticketOrder->service_key === 'document_printing' || ($ticketItem && $ticketItem->isDocumentPrinting());
                    @endphp

                    @if ($isTicketDoc)
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Paper Size & Weight</span>
                                <span class="font-bold text-stone-900 dark:text-white mt-0.5 block">
                                    {{ $ticketItem?->paper_size }} ({{ $customFields['paper_stock'] ?? '70gsm' }})
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Sides & Physical Feed</span>
                                <span class="font-bold text-sky-700 dark:text-blue-400 mt-0.5 block tabular-nums">
                                    {{ $ticketItem?->getPhysicalSheetsCount() }} sh ({{ ($customFields['print_sides'] ?? 'simplex') === 'duplex' ? 'Duplex' : 'Simplex' }})
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Page Breakdown</span>
                                <span class="font-bold text-stone-900 dark:text-white mt-0.5 block tabular-nums">
                                    {{ $ticketItem?->bw_pages_count ?? 0 }} B/W + {{ $ticketItem?->color_pages_count ?? 0 }} Col
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Finishing & Copies</span>
                                <span class="font-bold text-amber-700 dark:text-amber-400 mt-0.5 block tabular-nums">
                                    {{ $ticketItem?->getFinishingLabel() }} (x{{ $ticketItem?->copies_count ?? 1 }})
                                </span>
                            </div>
                        </div>

                        <!-- Document Print Instructions Card -->
                        <div class="pq-well p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-2">
                                <h4 class="font-bold text-sky-700 dark:text-blue-400 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                    <flux:icon name="document-text" class="size-3.5" />
                                    Document Printing Specifications
                                </h4>
                                @if (! empty($customFields['ring_back_cover_color']))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-neutral">
                                        Back Cover: {{ $customFields['ring_back_cover_color'] }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div class="p-3 rounded-xl bg-white dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800">
                                    <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 block">Color Mode</span>
                                    <p class="font-bold text-stone-900 dark:text-white mt-0.5">{{ strtoupper($customFields['color_mode'] ?? 'BW') }}</p>
                                    @if (! empty($customFields['custom_color_pages']))
                                        <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1">Color Pages: {{ $customFields['custom_color_pages'] }}</p>
                                    @endif
                                </div>

                                <div class="p-3 rounded-xl bg-white dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800">
                                    <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 block">Finishing Service</span>
                                    <p class="font-bold text-stone-900 dark:text-white mt-0.5">{{ $ticketItem?->getFinishingLabel() }}</p>
                                    <p class="text-[11px] text-stone-600 dark:text-stone-400 mt-1 tabular-nums">{{ $ticketItem?->copies_count ?? 1 }} sets to produce</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Fulfillment Mode</span>
                                <span class="font-bold text-stone-900 dark:text-white mt-0.5 block">
                                    {{ $ticketItem?->isCoverOnly() ? 'Cover Only' : 'Full Package' }}
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Binding & Copies</span>
                                <span class="font-bold text-amber-700 dark:text-amber-400 mt-0.5 block tabular-nums">
                                    {{ ucfirst($ticketItem?->binding_type ?? 'Hardbound') }} ({{ $ticketItem?->copies_count ?? 1 }}x)
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Pages (B/W vs Color)</span>
                                <span class="font-bold text-stone-900 dark:text-white mt-0.5 block tabular-nums">
                                    {{ $ticketItem?->bw_pages_count ?? 0 }} B/W + {{ $ticketItem?->color_pages_count ?? 0 }} Col
                                </span>
                            </div>

                            <div class="pq-well p-3">
                                <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase font-bold block">Calculated Spine</span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-400 font-mono mt-0.5 block tabular-nums">
                                    ~{{ $ticketItem?->estimated_spine_thickness_mm ?? '15.0' }} mm
                                </span>
                            </div>
                        </div>

                        <!-- Hot Foil Stamping Specifications Card -->
                        <div class="pq-well p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-2">
                                <h4 class="font-bold text-amber-700 dark:text-amber-400 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                    <flux:icon name="sparkles" class="size-3.5" />
                                    Hot Foil Stamping & Cover Metadata
                                </h4>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-neutral">
                                        Cover: {{ $ticketItem?->cover_color ?? 'Maroon' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-warning">
                                        Foil: {{ $ticketItem?->foil_color ?? 'Gold' }}
                                    </span>
                                </div>
                            </div>

                            @if (count($customFields) > 0)
                                <div class="grid grid-cols-1 gap-2.5">
                                    @foreach ($customFields as $fieldKey => $fieldValue)
                                        <div class="p-3 rounded-xl bg-white dark:bg-stone-900/70 border border-stone-200 dark:border-stone-800 flex items-start justify-between gap-3">
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 block">
                                                    {{ ucwords(str_replace('_', ' ', $fieldKey)) }}
                                                </span>
                                                <p class="font-bold text-stone-900 dark:text-white text-xs mt-0.5 select-all">{{ $fieldValue }}</p>
                                            </div>
                                            <button 
                                                onclick="navigator.clipboard.writeText('{{ addslashes($fieldValue) }}')"
                                                class="p-1.5 rounded-lg text-stone-500 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors cursor-pointer"
                                                title="Copy Text"
                                            >
                                                <flux:icon name="clipboard" class="size-3.5" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-stone-500 dark:text-stone-400 text-xs italic">No custom foil fields submitted.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Line-Item Scheduling, Prepress, Printers, QA & Split Pickup -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h4 class="font-bold text-amber-700 dark:text-amber-400 text-xs uppercase tracking-wider">Line-Item Production Routing &amp; Controls ({{ $ticketOrder->items->count() }})</h4>
                            @if ($ticketOrder->refund_status === Order::REFUND_MANUAL_PENDING && (float) $ticketOrder->refund_due > 0)
                                <button type="button" wire:click="settleOrderManualRefund({{ $ticketOrder->id }})" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold cursor-pointer">
                                    Settle Manual Refund (₱{{ number_format((float) $ticketOrder->refund_due, 2) }})
                                </button>
                            @endif
                        </div>

                        @foreach ($ticketOrder->items as $lineItem)
                            @php
                                $pendingRev = $lineItem->specRevisions->where('status', SpecRevision::STATUS_PENDING)->first();
                            @endphp
                            <div wire:key="ticket-item-{{ $lineItem->id }}" class="pq-well p-4 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-stone-900 dark:text-white">{{ $lineItem->isThesisBinding() ? 'Thesis Binding' : 'Document Printing' }} (x{{ $lineItem->copies_count }})</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-warning">{{ $lineItem->stageLabel() }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $lineItem->prepress_status === OrderItem::PREPRESS_APPROVED ? 'pq-badge-success' : ($lineItem->prepress_status === OrderItem::PREPRESS_REJECTED ? 'pq-badge-danger' : 'pq-badge-neutral') }}">
                                            Prepress: {{ ucfirst($lineItem->prepress_status) }}
                                        </span>
                                        @if ($lineItem->is_spec_locked)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30">Specs Locked</span>
                                        @endif
                                        @if ($lineItem->shelf_bin_code)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-success">Shelf: {{ $lineItem->shelf_bin_code }}</span>
                                        @endif
                                        @if ($lineItem->isUnclaimedAging(3))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-warning">Aging Pickup (&gt;3d)</span>
                                        @endif
                                    </div>
                                    <span class="font-mono font-bold text-stone-800 dark:text-stone-200 tabular-nums">₱{{ number_format((float) $lineItem->total_price, 2) }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase mb-1">Assigned Printer</label>
                                        <select wire:change="assignItemPrinter({{ $lineItem->id }}, $event.target.value)" class="w-full rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-800 px-3 py-1.5 text-xs text-stone-900 dark:text-white">
                                            <option value="">Unassigned</option>
                                            @foreach ($this->shopPrinters as $shopPrinter)
                                                <option value="{{ $shopPrinter->id }}" @selected($lineItem->printer_id === $shopPrinter->id)>
                                                    {{ $shopPrinter->name }} ({{ ucfirst($shopPrinter->status) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase mb-1">Assigned Operator</label>
                                        <select wire:change="assignItemStaff({{ $lineItem->id }}, $event.target.value)" class="w-full rounded-xl bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-800 px-3 py-1.5 text-xs text-stone-900 dark:text-white">
                                            <option value="">Unassigned</option>
                                            @foreach ($this->shopOperators as $shopOp)
                                                <option value="{{ $shopOp->id }}" @selected($lineItem->assigned_staff_id === $shopOp->id)>
                                                    {{ $shopOp->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @if ($pendingRev)
                                    <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <div class="font-bold text-amber-900 dark:text-amber-300">Pending Spec Revision ({{ ucwords(str_replace('_', ' ', $pendingRev->revision_type)) }})</div>
                                            <div class="text-[11px] text-stone-700 dark:text-stone-300 tabular-nums">
                                                New total: ₱{{ number_format((float) $pendingRev->new_total, 2) }}
                                                (Delta: {{ $pendingRev->delta_amount >= 0 ? '+' : '' }}₱{{ number_format((float) $pendingRev->delta_amount, 2) }})
                                            </div>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <button type="button" wire:click="approveItemRevision({{ $pendingRev->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer">Approve &amp; Apply</button>
                                            <button type="button" wire:click="rejectItemRevision({{ $pendingRev->id }})" class="px-2.5 py-1 rounded-lg pq-badge-danger text-[11px] font-bold cursor-pointer">Reject</button>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @if ($lineItem->document_file_path)
                                        <button type="button" wire:click="openPrintPreview({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-[11px] font-bold cursor-pointer">
                                            Direct Print Preview
                                        </button>
                                        @if ($lineItem->printer?->is_wide_format)
                                            <a href="{{ route('orders.file', ['order' => $lineItem->order_id, 'kind' => 'document', 'item' => $lineItem->id]) }}" class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30 text-[11px] font-bold">
                                                RIP Export
                                            </a>
                                        @endif
                                    @endif

                                    @if ($lineItem->isCoverOnly() && ! $lineItem->is_paper_received && ! $lineItem->hasStartedPrinting())
                                        <button type="button" wire:click="confirmCoverOnlyIntake({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg pq-badge-success text-[11px] font-bold cursor-pointer">
                                            Confirm Paper Intake
                                        </button>
                                        <button type="button" wire:click="openShiftToShopPaperModal({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg pq-badge-danger text-[11px] font-bold cursor-pointer">
                                            Reject Paper &rarr; Shop Paper
                                        </button>
                                    @endif

                                    @if (! $lineItem->hasStartedPrinting() && $lineItem->prepress_status !== OrderItem::PREPRESS_APPROVED && (! $lineItem->isCoverOnly() || $lineItem->is_paper_received))
                                        <button type="button" wire:click="approveItemPrepress({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer">
                                            Approve Prepress
                                        </button>
                                    @endif

                                    @if (! $lineItem->hasStartedPrinting())
                                        <button type="button" wire:click="openItemPrepressRejectModal({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg pq-badge-danger text-[11px] font-bold cursor-pointer">
                                            Reject Artwork
                                        </button>
                                    @endif

                                    @if (($lineItem->production_stage ?? OrderItem::STAGE_QUEUE) === OrderItem::STAGE_QUEUE && $lineItem->prepress_status !== OrderItem::PREPRESS_REJECTED && (! $lineItem->isCoverOnly() || $lineItem->is_paper_received))
                                        <button type="button" wire:click="startItemPrinting({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-stone-950 text-[11px] font-bold cursor-pointer">
                                            Start Printing
                                        </button>
                                    @elseif (in_array($lineItem->production_stage ?? OrderItem::STAGE_QUEUE, [OrderItem::STAGE_PRINTING, OrderItem::STAGE_BINDING], true))
                                        <button type="button" wire:click="advanceItemWorkflow({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-stone-950 text-[11px] font-bold cursor-pointer">
                                            Advance Stage
                                        </button>
                                    @elseif (($lineItem->production_stage ?? OrderItem::STAGE_QUEUE) === OrderItem::STAGE_QUALITY_CHECK)
                                        <button type="button" wire:click="openItemQaPassModal({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer">
                                            Pass QC &amp; Bin
                                        </button>
                                        <button type="button" wire:click="openItemQaFailModal({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold cursor-pointer">
                                            Fail QC (Rework)
                                        </button>
                                    @elseif ($lineItem->isReadyForPickup())
                                        <button type="button" wire:click="claimOrderItem({{ $lineItem->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer">
                                            Mark Item Claimed
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Customer Document Print Asset Box -->
                    @if ($ticketItem?->document_file_path)
                        <div class="p-4 rounded-2xl bg-sky-50 dark:bg-blue-950/30 border border-sky-200 dark:border-blue-500/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="size-11 rounded-xl pq-badge-info flex items-center justify-center shrink-0">
                                    <flux:icon name="document-text" class="size-5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-stone-900 dark:text-white text-xs truncate max-w-[280px] sm:max-w-md select-all">{{ $ticketItem->document_original_name ?? 'Document.pdf' }}</p>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-info shrink-0">
                                            Inline Browser Print Dispatch
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-stone-600 dark:text-stone-400 mt-0.5">Dispatched directly inside locked inline viewer without loose file copies.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                                <button
                                    type="button"
                                    wire:click="openPrintPreview({{ $ticketItem->id }})"
                                    class="flex-1 sm:flex-initial px-4 py-2 rounded-xl text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white transition-colors flex items-center justify-center gap-1.5 shadow-sm cursor-pointer"
                                >
                                    <flux:icon name="printer" class="size-3.5" />
                                    <span>Direct Print Preview</span>
                                </button>
                                @if ($ticketItem->printer?->is_wide_format)
                                    <a
                                        href="{{ route('orders.file', ['order' => $ticketItem->order_id, 'kind' => 'document', 'item' => $ticketItem->id]) }}"
                                        download="{{ $ticketItem->document_original_name ?? 'Document.pdf' }}"
                                        class="px-3 py-2 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 dark:text-indigo-300 dark:border-indigo-500/40 transition-colors flex items-center justify-center gap-1 cursor-pointer"
                                    >
                                        <span>RIP Export</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-xl pq-badge-warning flex items-center justify-center shrink-0">
                                    <flux:icon name="folder" class="size-5" />
                                </div>
                                <div>
                                    <p class="font-bold text-amber-900 dark:text-amber-300 text-xs">Counter File Intake (No Online File Uploaded)</p>
                                    <p class="text-[11px] text-stone-600 dark:text-stone-400 mt-0.5">Customer placed order without file attachment. Collect file via USB Flash Drive, Bluetooth, or Email at the counter.</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold pq-badge-warning shrink-0">
                                Walk-in Intake
                            </span>
                        </div>
                    @endif

                    <!-- Machine Assignment & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-stone-600 dark:text-stone-400 uppercase tracking-wider mb-1.5">
                                Assigned Equipment / Workstation
                            </label>
                            <select 
                                wire:model="assignedMachine"
                                class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-xs text-stone-900 dark:text-white outline-none"
                            >
                                <option value="Canon Laser ImageRUNNER Pro 1">Canon Laser ImageRUNNER Pro 1</option>
                                <option value="Epson Heavy Duty WorkForce 2">Epson Heavy Duty WorkForce 2</option>
                                <option value="Digital Hot Foil Press Alpha">Digital Hot Foil Press Alpha</option>
                                <option value="Fastback Thermal Binding Press">Fastback Thermal Binding Press</option>
                                <option value="Manual Stamping Jig Station">Manual Stamping Jig Station</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-stone-600 dark:text-stone-400 uppercase tracking-wider mb-1.5">
                                Operator & QA Notes
                            </label>
                            <textarea 
                                wire:model="staffNotes"
                                rows="2"
                                placeholder="Internal instructions, spine adjust notes..."
                                class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl px-3.5 py-2 text-xs text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-600 outline-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Ticket Footer -->
                <div class="p-4 border-t border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950/80 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-[11px] text-stone-600 dark:text-stone-400">
                        Status: <strong class="text-amber-700 dark:text-amber-400">{{ $ticketOrder->stageLabel() }}</strong>
                    </span>
                    
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('orders.receipt', $ticketOrder) }}"
                            target="_blank"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-stone-800 hover:bg-stone-100 dark:hover:bg-stone-700 border border-stone-300 dark:border-stone-700 text-amber-700 dark:text-amber-400 transition-colors"
                        >
                            Official Receipt
                        </a>
                        @if (! in_array($ticketOrder->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified']))
                            <button 
                                wire:click="verifyPayment({{ $ticketOrder->id }})"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-sm flex items-center gap-1.5 cursor-pointer tabular-nums"
                            >
                                <flux:icon name="check" class="size-3.5" />
                                <span>Verify / Collect (₱{{ number_format(max(0.0, (float) $ticketOrder->total_amount - (float) $ticketOrder->amount_paid), 2) }})</span>
                            </button>
                        @endif
                        <button 
                            wire:click="$set('showJobTicketModal', false)"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-white dark:bg-stone-800 hover:bg-stone-100 dark:hover:bg-stone-700 border border-stone-300 dark:border-stone-700 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                        >
                            Close
                        </button>
                        <button 
                            wire:click="saveJobDetails"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-stone-950 transition-colors shadow-sm cursor-pointer"
                        >
                            Save Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- QC Rejection Modal -->
    @if ($showRejectModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl">
                <div class="flex items-center gap-3 text-red-700 dark:text-red-400">
                    <flux:icon name="exclamation-triangle" class="size-6 shrink-0" />
                    <h3 class="text-base font-bold text-stone-900 dark:text-white">Quality Inspection Rejection</h3>
                </div>

                <p class="text-xs text-stone-600 dark:text-stone-400">
                    Specify the defect detected. The order will step back to the Binding stage for correction.
                </p>

                <div>
                    <label class="block text-[11px] font-bold text-stone-700 dark:text-stone-400 mb-1">Reason for Rejection</label>
                    <textarea 
                        wire:model="rejectReason"
                        rows="3"
                        placeholder="e.g. Misaligned gold foil text, crooked chipboard margin, missing colored page..."
                        class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-red-500 rounded-xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-600 outline-none"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        wire:click="$set('showRejectModal', false)"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="submitQcRejection"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-700 text-white transition-colors cursor-pointer"
                    >
                        Step Back Job
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Material Spoilage Logging Modal -->
    @if ($showSpoilageModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <flux:icon name="trash" class="size-6 shrink-0 text-red-600 dark:text-red-400" />
                    <div>
                        <h3 class="text-base font-bold text-stone-900 dark:text-white">Report Material Spoilage</h3>
                        <p class="text-xs text-stone-600 dark:text-stone-400">Deduct damaged or wasted materials from inventory</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Select Spoiled Material</label>
                        <select 
                            wire:model="spoilageItemId"
                            class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-stone-900 dark:text-white outline-none"
                        >
                            @foreach ($this->inventoryItems as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }} (In Stock: {{ $inv->stock_qty }} {{ $inv->unit }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Quantity Wasted</label>
                        <input 
                            wire:model="spoilageQty"
                            type="number" 
                            step="0.1" 
                            min="0.1" 
                            class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-stone-900 dark:text-white outline-none tabular-nums"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Reason / Notes</label>
                        <textarea 
                            wire:model="spoilageReason"
                            rows="2"
                            placeholder="e.g. Printer paper jam on fuser unit, crooked foil stamp alignment..."
                            class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 focus:border-amber-600 dark:focus:border-amber-500 rounded-xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 dark:placeholder-stone-600 outline-none"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        wire:click="$set('showSpoilageModal', false)"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="submitSpoilage"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-700 text-white transition-colors cursor-pointer"
                    >
                        Confirm Spoilage Deduction
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Direct Browser Print Preview Modal -->
    @if ($showPrintPreviewModal && $printPreviewItemId)
        @php
            $previewItem = $this->findShopOrderItem($printPreviewItemId);
        @endphp
        @if ($previewItem)
            <div class="fixed inset-0 z-50 bg-stone-950/75 dark:bg-stone-950/90 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-5xl h-[88vh] flex flex-col overflow-hidden shadow-xl">
                    <div class="p-4 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900 dark:text-white">Direct Print Dispatch: {{ $previewItem->document_original_name ?? 'Artwork.pdf' }}</h3>
                            <p class="text-[11px] text-stone-600 dark:text-stone-400">Protected inline preview stream (no local download for standard printers).</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="const frame = document.getElementById('pressqueue-print-frame'); if (frame && frame.contentWindow) { frame.contentWindow.focus(); frame.contentWindow.print(); }" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold cursor-pointer">
                                Print Now
                            </button>
                            <button type="button" wire:click="$set('showPrintPreviewModal', false)" class="px-3 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-300 text-xs font-bold cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                    <iframe
                        id="pressqueue-print-frame"
                        src="{{ route('orders.file.preview', ['order' => $previewItem->order_id, 'item' => $previewItem->id]) }}"
                        class="w-full flex-1 bg-white"
                    ></iframe>
                </div>
            </div>
        @endif
    @endif

    <!-- Prepress Rejection Modal -->
    @if ($showPrepressRejectModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Reject Prepress Artwork</h3>
                <textarea wire:model="prepressRejectReason" rows="3" placeholder="Explain what the customer must fix in their PDF..." class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-red-500"></textarea>
                @error('reason') <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showPrepressRejectModal', false)" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="confirmPrepressRejection" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-bold text-white cursor-pointer">Confirm Reject &amp; Hold</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Cover-Only Reject -> Shift to Shop Paper Modal -->
    @if ($showShiftToShopPaperModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Reject Customer Paper &amp; Shift to Shop Paper</h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">B&amp;W Pages</label>
                        <input type="number" min="0" wire:model="shiftBwPages" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-3 py-2 text-stone-900 dark:text-white tabular-nums" />
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Color Pages</label>
                        <input type="number" min="0" wire:model="shiftColorPages" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-3 py-2 text-stone-900 dark:text-white tabular-nums" />
                    </div>
                </div>
                <textarea wire:model="shiftReason" rows="2" placeholder="e.g. Supplied pages damp or mismatched paper size..." class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-amber-500"></textarea>
                @error('reason') <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showShiftToShopPaperModal', false)" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="confirmShiftToShopPaper" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-xs font-bold text-stone-950 cursor-pointer">Shift to Full Package</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Item QA Pass (Shelf / Bin Location) Modal -->
    @if ($showItemQaPassModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-sm p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Assign Pickup Shelf / Bin</h3>
                <input type="text" wire:model="qaPassShelfBin" placeholder="e.g. BIN-B2" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-3 py-2 text-xs text-stone-900 dark:text-white placeholder-stone-400 font-mono" />
                @error('shelf_bin_code') <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showItemQaPassModal', false)" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="confirmItemQaPass" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white cursor-pointer">Mark Ready</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Item QA Fail (Dual Paper + Ink Rework Spoilage) Modal -->
    @if ($showItemQaFailModal)
        <div class="fixed inset-0 z-50 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-xl text-xs">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">QC Fail &amp; Dual Material Rework Spoilage</h3>
                <p class="text-stone-600 dark:text-stone-400">Deducts spoiled paper and ink/toner while keeping customer billing locked.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Paper Item</label>
                        <select wire:model="qaFailPaperItemId" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-2.5 py-2 text-stone-900 dark:text-white">
                            <option value="">None</option>
                            @foreach ($this->inventoryItems as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Spoiled Sheets</label>
                        <input type="number" step="0.1" min="0.1" wire:model="qaFailSpoiledSheets" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-2.5 py-2 text-stone-900 dark:text-white tabular-nums" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Ink / Toner Item</label>
                        <select wire:model="qaFailInkItemId" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-2.5 py-2 text-stone-900 dark:text-white">
                            <option value="">None</option>
                            @foreach ($this->inventoryItems as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-400 mb-1">Ink Used (Units)</label>
                        <input type="number" step="0.01" min="0" wire:model="qaFailInkQty" class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl px-2.5 py-2 text-stone-900 dark:text-white tabular-nums" />
                    </div>
                </div>
                <textarea wire:model="qaFailReason" rows="2" placeholder="Describe defect (e.g. Roller smudge, ink streaking)..." class="w-full bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 rounded-xl p-2.5 text-stone-900 dark:text-white placeholder-stone-400"></textarea>
                @error('reason') <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showItemQaFailModal', false)" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="confirmItemQaFail" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 font-bold text-white cursor-pointer">Log Rework &amp; Reprint</button>
                </div>
            </div>
        </div>
    @endif

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
