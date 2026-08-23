<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryDeductionService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Production Operations Hub')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $searchQuery = '';
    public string $selectedFilter = 'all'; // all, rush_only, cover_only, full_package, hardbound, softbound
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
        return PrintShop::first();
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
            ->with(['customer', 'items', 'assignedStaff'])
            ->latest('created_at');

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
        }

        if (trim($this->searchQuery) !== '') {
            $search = '%' . trim($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
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

        return Order::with(['customer', 'items', 'assignedStaff'])->find($this->selectedOrderId);
    }

    /**
     * Mark customer pre-printed paper as received at shop counter.
     */
    public function markPaperReceived(int $orderId): void
    {
        $order = Order::with('items')->find($orderId);
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
        $order = Order::with(['items', 'printShop'])->find($orderId);
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
        $order = Order::with(['customer', 'items', 'assignedStaff'])->find($orderId);
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

        $order = Order::find($this->selectedOrderId);
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

        $order = Order::find($this->rejectOrderId);
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

        $item = InventoryItem::find($this->spoilageItemId);
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
}; ?>

<div class="min-h-screen w-full bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-between relative selection:bg-amber-500 selection:text-white">
    
    <!-- Ambient Background Glows -->
    <div class="absolute -top-32 left-1/4 size-[600px] rounded-full bg-amber-500/5 blur-[160px] pointer-events-none"></div>
    <div class="absolute top-1/3 -right-32 size-[500px] rounded-full bg-blue-500/5 blur-[160px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $this->shop;
        $allOrders = $this->orders;
        $totalActive = $allOrders->whereNotIn('order_status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])->count();
        $rushCount = $allOrders->where('is_rush', true)->whereNotIn('order_status', [Order::STATUS_COMPLETED])->count();
        $pendingPaper = $allOrders->filter(fn ($o) => $o->isPaperIntakePending())->count();
        $readyPickup = $allOrders->where('order_status', Order::STATUS_READY_FOR_PICKUP)->count();
    @endphp

    <!-- Top Navigation Header (Consistent with Inventory Hub & Thesis Binding) -->
    <header class="w-full bg-stone-900/90 border-b border-stone-800/80 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between">
        <!-- Brand & Hub Identity -->
        <div class="flex items-center gap-3.5">
            <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-amber-700 text-stone-950 font-black shadow-lg shadow-amber-500/20">
                <flux:icon name="queue-list" class="size-5 text-stone-950" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm sm:text-base font-extrabold text-white tracking-tight">
                        Production Operations Hub
                    </h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Shop Floor Live
                    </span>
                </div>
                <p class="text-[11px] text-stone-400">{{ $shop ? $shop->name : 'Printify' }} &bull; Digital Job Queue, Machine Scheduling & BOM Consumption</p>
            </div>
        </div>

        <!-- Quick Actions & User Navigation -->
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button 
                wire:click="openSpoilageModal"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-stone-950 hover:bg-red-500/10 text-stone-300 hover:text-red-400 border border-stone-800 hover:border-red-500/30 transition-all flex items-center gap-1.5 shadow-sm"
            >
                <flux:icon name="trash" class="size-3.5 text-red-400" />
                <span class="hidden sm:inline">Report Spoilage</span>
            </button>

            @if ($user?->isOwner())
                <a 
                    href="{{ route('owner.dashboard') }}" 
                    wire:navigate
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-stone-950 hover:bg-stone-800 text-stone-300 border border-stone-800 transition-all flex items-center gap-1.5"
                >
                    <flux:icon name="arrow-left" class="size-3.5" />
                    <span>App Launcher</span>
                </a>
            @endif

            <!-- Profile Settings Link -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2 bg-stone-950 border border-stone-800 hover:border-amber-500/50 hover:bg-stone-900 rounded-xl py-1 px-2.5 transition-all cursor-pointer shadow-sm"
                title="Account Profile Settings"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-amber-500/40" />
                @else
                    <span class="size-6 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 text-[10px] font-black flex items-center justify-center">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <div class="leading-tight hidden md:block text-left">
                    <p class="text-xs font-bold text-stone-200 group-hover:text-amber-400 transition-colors">{{ $user?->name }}</p>
                    <p class="text-[9px] text-stone-500 uppercase font-semibold">{{ $user?->isOwner() ? 'Owner' : 'Floor Staff' }}</p>
                </div>
            </a>

            <!-- Log Out Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                @csrf
                <button 
                    type="submit" 
                    title="Log Out"
                    class="p-2 sm:px-3 sm:py-1.5 rounded-xl text-xs font-bold bg-stone-950 hover:bg-red-500/10 text-red-400 hover:text-red-300 border border-stone-800 hover:border-red-500/30 transition-all flex items-center gap-1.5 shadow-sm"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5 text-red-400" />
                    <span class="hidden sm:inline">Log Out</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Operations Hub Body -->
    <main class="flex-1 w-full px-4 sm:px-8 py-6 space-y-6 max-w-full">
        
        <!-- Live Floor KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex items-center justify-between backdrop-blur-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Active Jobs in Floor</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-white mt-0.5">{{ $totalActive }}</h3>
                </div>
                <div class="size-11 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-lg">
                    🖨️
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex items-center justify-between backdrop-blur-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-amber-400 animate-ping"></span>
                        Rush Jobs Active
                    </p>
                    <h3 class="text-2xl sm:text-3xl font-black text-amber-400 mt-0.5">{{ $rushCount }}</h3>
                </div>
                <div class="size-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg">
                    ⚡
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex items-center justify-between backdrop-blur-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-400">Paper Drop-offs Needed</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-purple-400 mt-0.5">{{ $pendingPaper }}</h3>
                </div>
                <div class="size-11 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-lg">
                    📦
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex items-center justify-between backdrop-blur-sm">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Ready for Pickup</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-emerald-400 mt-0.5">{{ $readyPickup }}</h3>
                </div>
                <div class="size-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-lg">
                    ✅
                </div>
            </div>
        </div>

        <!-- Search, Filters & View Controls Bar -->
        <div class="p-3.5 rounded-2xl bg-stone-900/80 border border-stone-800/90 flex flex-col md:flex-row items-center justify-between gap-3.5 backdrop-blur-sm">
            <div class="relative w-full md:w-96">
                <flux:icon name="magnifying-glass" class="size-4 text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input 
                    wire:model.live.debounce.200ms="searchQuery" 
                    type="text" 
                    placeholder="Search Order #, Customer Name, Title..."
                    class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-stone-500 outline-none transition-colors"
                />
            </div>

            <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                @php
                    $filters = [
                        'all' => 'All Jobs',
                        'rush_only' => '⚡ Rush Only',
                        'cover_only' => '📦 Cover Only',
                        'full_package' => '📄 Full Package',
                        'hardbound' => '📕 Hardbound',
                        'softbound' => '📘 Softbound',
                    ];
                @endphp

                @foreach ($filters as $key => $label)
                    <button 
                        wire:click="$set('selectedFilter', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $selectedFilter === $key ? 'bg-amber-500 text-stone-950 shadow-md shadow-amber-500/20 font-black' : 'bg-stone-950 text-stone-400 hover:text-white border border-stone-800' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                <div class="h-5 w-px bg-stone-800 mx-1 hidden sm:block"></div>

                <div class="flex items-center bg-stone-950 p-0.5 rounded-xl border border-stone-800 shrink-0">
                    <button 
                        wire:click="$set('selectedView', 'kanban')" 
                        class="p-1.5 rounded-lg transition-colors {{ $selectedView === 'kanban' ? 'bg-stone-800 text-amber-400' : 'text-stone-500 hover:text-stone-300' }}"
                        title="Kanban Board View"
                    >
                        <flux:icon name="squares-2x2" class="size-4" />
                    </button>
                    <button 
                        wire:click="$set('selectedView', 'list')" 
                        class="p-1.5 rounded-lg transition-colors {{ $selectedView === 'list' ? 'bg-stone-800 text-amber-400' : 'text-stone-500 hover:text-stone-300' }}"
                        title="Tabular List View"
                    >
                        <flux:icon name="queue-list" class="size-4" />
                    </button>
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
                        'color' => 'border-blue-500/30 text-blue-400',
                        'headerBg' => 'bg-blue-500/10',
                        'nextAction' => 'Start Printing',
                        'nextIcon' => 'printer',
                    ],
                    Order::STAGE_PRINTING => [
                        'title' => '2. Printing Pages',
                        'icon' => 'printer',
                        'color' => 'border-amber-500/30 text-amber-400',
                        'headerBg' => 'bg-amber-500/10',
                        'nextAction' => 'Move to Binding',
                        'nextIcon' => 'book-open',
                    ],
                    Order::STAGE_BINDING => [
                        'title' => '3. Cover & Foil Stamping',
                        'icon' => 'book-open',
                        'color' => 'border-purple-500/30 text-purple-400',
                        'headerBg' => 'bg-purple-500/10',
                        'nextAction' => 'Send to QC',
                        'nextIcon' => 'magnifying-glass',
                    ],
                    Order::STAGE_QUALITY_CHECK => [
                        'title' => '4. Quality Inspection (QC)',
                        'icon' => 'check-badge',
                        'color' => 'border-cyan-500/30 text-cyan-400',
                        'headerBg' => 'bg-cyan-500/10',
                        'nextAction' => 'Pass & Ready',
                        'nextIcon' => 'check',
                    ],
                    Order::STAGE_READY_FOR_PICKUP => [
                        'title' => '5. Ready for Pickup',
                        'icon' => 'gift',
                        'color' => 'border-emerald-500/30 text-emerald-400',
                        'headerBg' => 'bg-emerald-500/10',
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

                    <div class="bg-stone-900/70 border border-stone-800 rounded-2xl flex flex-col overflow-hidden shadow-md backdrop-blur-sm">
                        <!-- Column Header -->
                        <div class="p-3.5 border-b border-stone-800 flex items-center justify-between {{ $stageMeta['headerBg'] }}">
                            <div class="flex items-center gap-2">
                                <span class="font-black text-xs text-white tracking-tight">{{ $stageMeta['title'] }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $stageMeta['color'] }} bg-stone-950 border">
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

                                <div class="p-3.5 rounded-xl bg-stone-950 border {{ $order->is_rush ? 'border-amber-500/60 shadow-lg shadow-amber-500/10 ring-1 ring-amber-500/30' : 'border-stone-800/90 hover:border-stone-700' }} transition-all space-y-3">
                                    
                                    <!-- Card Top Row: Order Number & Rush Indicator -->
                                    <div class="flex items-center justify-between gap-1.5">
                                        <span class="font-mono text-xs font-black text-white tracking-wider">
                                            {{ $order->order_number }}
                                        </span>
                                        
                                        @if ($order->is_rush)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center gap-1 animate-pulse">
                                                ⚡ RUSH
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Service Tag & Customer Name -->
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if ($isCoverOnly)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-purple-500/15 text-purple-300 border border-purple-500/30">
                                                    📦 Cover Only
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                                    📄 Full Package
                                                </span>
                                            @endif

                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-stone-800 text-stone-300">
                                                {{ ucfirst($item?->binding_type ?? 'Hardbound') }}
                                            </span>
                                            <span class="text-[10px] font-semibold text-stone-400">
                                                x{{ $item?->copies_count ?? 1 }} copy
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-stone-100 mt-1.5 truncate">
                                            {{ $order->customer->name ?? 'Customer' }}
                                        </p>
                                    </div>

                                    <!-- Physical Paper Intake Banner (For Cover-Only) -->
                                    @if ($isCoverOnly)
                                        @if ($isPendingPaper)
                                            <div class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/30 space-y-1.5">
                                                <p class="text-[10px] font-bold text-amber-400 flex items-center gap-1">
                                                    <span class="size-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                                    Paper Drop-off Needed
                                                </p>
                                                <button 
                                                    wire:click="markPaperReceived({{ $order->id }})"
                                                    class="w-full py-1 rounded-md text-[11px] font-black bg-amber-500 hover:bg-amber-400 text-stone-950 transition-colors flex items-center justify-center gap-1 shadow-sm"
                                                >
                                                    <flux:icon name="check" class="size-3" />
                                                    <span>Mark Paper Received</span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="px-2 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-[10px] font-bold text-emerald-400 flex items-center gap-1.5">
                                                <flux:icon name="check-circle" class="size-3 text-emerald-400" />
                                                <span>Paper In-Shop & Received</span>
                                            </div>
                                        @endif
                                    @endif

                                    <!-- Page Breakdown & Spine Width Badge -->
                                    <div class="grid grid-cols-2 gap-1.5 py-1 text-[11px] border-y border-stone-800/80">
                                        <div>
                                            <span class="text-stone-500 text-[10px] block">Pages (B/W + Col)</span>
                                            <span class="font-bold text-stone-300">
                                                {{ $item?->bw_pages_count ?? 0 }} / {{ $item?->color_pages_count ?? 0 }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-stone-500 text-[10px] block">Spine Width</span>
                                            <span class="font-bold text-amber-400 font-mono">
                                                ~{{ $item?->estimated_spine_thickness_mm ?? '15.0' }} mm
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Cover & Foil Color Chips -->
                                    @if ($item?->cover_color || $item?->foil_color)
                                        <div class="flex items-center gap-1.5 text-[10px] flex-wrap">
                                            @if ($item?->cover_color)
                                                <span class="px-1.5 py-0.5 rounded bg-stone-900 border border-stone-800 text-stone-300">
                                                    Cover: {{ $item->cover_color }}
                                                </span>
                                            @endif
                                            @if ($item?->foil_color)
                                                <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-300">
                                                    Foil: {{ $item->foil_color }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1.5 pt-1">
                                        <button 
                                            wire:click="viewJobTicket({{ $order->id }})"
                                            class="flex-1 py-1.5 rounded-lg text-xs font-bold bg-stone-900 hover:bg-stone-800 text-stone-300 border border-stone-800 hover:border-stone-700 transition-all flex items-center justify-center gap-1"
                                        >
                                            <flux:icon name="document-text" class="size-3 text-stone-400" />
                                            <span>Ticket</span>
                                        </button>

                                        <!-- QC Rejection Step Back (Only in QC stage) -->
                                        @if ($stageKey === Order::STAGE_QUALITY_CHECK)
                                            <button 
                                                wire:click="openRejectModal({{ $order->id }})"
                                                class="p-1.5 rounded-lg text-xs font-bold bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 transition-all"
                                                title="Step Back / QC Reject"
                                            >
                                                <flux:icon name="arrow-uturn-left" class="size-3.5" />
                                            </button>
                                        @endif

                                        <!-- Forward Advance Button -->
                                        <button 
                                            wire:click="advanceOrder({{ $order->id }})"
                                            class="flex-1 py-1.5 rounded-lg text-xs font-black bg-amber-500 hover:bg-amber-400 text-stone-950 transition-all flex items-center justify-center gap-1 shadow-sm shadow-amber-500/10"
                                        >
                                            <span>{{ $stageMeta['nextAction'] }}</span>
                                            <flux:icon name="arrow-right" class="size-3 text-stone-950" />
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="py-16 text-center text-stone-600">
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
            <div class="bg-stone-900/80 border border-stone-800 rounded-2xl overflow-hidden shadow-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-stone-300">
                        <thead class="bg-stone-950/90 border-b border-stone-800 text-[11px] font-bold text-stone-400 uppercase tracking-wider">
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
                        <tbody class="divide-y divide-stone-800/60 font-medium">
                            @forelse ($allOrders as $order)
                                @php
                                    $item = $order->items->first();
                                    $isCoverOnly = $item?->isCoverOnly() ?? false;
                                @endphp
                                <tr class="hover:bg-stone-900 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-black text-white text-xs">{{ $order->order_number }}</span>
                                            @if ($order->is_rush)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                                    RUSH
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-stone-500">{{ $order->created_at->format('M d, H:i') }}</span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-white">{{ $order->customer->name ?? 'Customer' }}</p>
                                        <p class="text-[10px] text-stone-500">{{ $order->customer->email ?? '' }}</p>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $isCoverOnly ? 'bg-purple-500/15 text-purple-300' : 'bg-blue-500/15 text-blue-300' }}">
                                                {{ $isCoverOnly ? 'Cover Only' : 'Full Package' }}
                                            </span>
                                            <span class="text-stone-400">({{ ucfirst($item?->binding_type ?? 'Hardbound') }})</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-stone-200">{{ $item?->total_pages_count ?? 0 }} pages</p>
                                        <p class="text-[10px] text-amber-400 font-mono">Spine: ~{{ $item?->estimated_spine_thickness_mm ?? '15.0' }}mm</p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black {{ $order->statusBadgeColor() }} border">
                                            {{ $order->stageLabel() }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        @if ($isCoverOnly)
                                            @if ($order->isPaperIntakePending())
                                                <button 
                                                    wire:click="markPaperReceived({{ $order->id }})"
                                                    class="px-2.5 py-1 rounded-md text-[10px] font-black bg-amber-500 text-stone-950 hover:bg-amber-400 transition-colors"
                                                >
                                                    Mark Paper Received
                                                </button>
                                            @else
                                                <span class="text-[10px] font-bold text-emerald-400 flex items-center gap-1">
                                                    <flux:icon name="check-circle" class="size-3" />
                                                    Received
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-stone-500">PDF Uploaded</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right space-x-1.5">
                                        <button 
                                            wire:click="viewJobTicket({{ $order->id }})"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-stone-950 hover:bg-stone-800 text-stone-300 border border-stone-800 transition-colors"
                                        >
                                            Ticket
                                        </button>
                                        <button 
                                            wire:click="advanceOrder({{ $order->id }})"
                                            class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-amber-500 hover:bg-amber-400 text-stone-950 transition-colors"
                                        >
                                            Advance
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-12 text-center text-stone-500">No orders matching your criteria</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>

    <!-- Footer Bar -->
    <footer class="w-full bg-stone-900/60 border-t border-stone-800/80 px-4 sm:px-8 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-500 gap-2">
        <span>&copy; {{ date('Y') }} {{ $shop ? $shop->name : 'Printify' }} &bull; Floor Operations System</span>
        <span class="text-stone-500 font-medium">
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

        <div class="fixed inset-0 z-50 bg-stone-950/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-stone-900 border border-stone-800 rounded-3xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                
                <!-- Ticket Header -->
                <div class="p-5 border-b border-stone-800 bg-stone-950/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center font-black text-lg">
                            🎫
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-black text-white">Digital Job Ticket: {{ $ticketOrder->order_number }}</h3>
                                @if ($ticketOrder->is_rush)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        ⚡ RUSH ORDER
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-stone-400">Customer: {{ $ticketOrder->customer->name }} ({{ $ticketOrder->customer->email }})</p>
                        </div>
                    </div>

                    <button 
                        wire:click="$set('showJobTicketModal', false)"
                        class="p-2 rounded-xl text-stone-400 hover:text-white hover:bg-stone-800 transition-colors"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <!-- Ticket Scrollable Body -->
                <div class="p-6 overflow-y-auto space-y-6 text-xs text-stone-300">
                    
                    <!-- Specifications Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-stone-950 border border-stone-800">
                            <span class="text-stone-500 text-[10px] uppercase font-bold block">Fulfillment Mode</span>
                            <span class="font-black text-white mt-0.5 block">
                                {{ $ticketItem?->isCoverOnly() ? '📦 Cover Only' : '📄 Full Package' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-stone-950 border border-stone-800">
                            <span class="text-stone-500 text-[10px] uppercase font-bold block">Binding & Copies</span>
                            <span class="font-black text-amber-400 mt-0.5 block">
                                {{ ucfirst($ticketItem?->binding_type ?? 'Hardbound') }} ({{ $ticketItem?->copies_count ?? 1 }}x)
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-stone-950 border border-stone-800">
                            <span class="text-stone-500 text-[10px] uppercase font-bold block">Pages (B/W vs Color)</span>
                            <span class="font-black text-white mt-0.5 block">
                                {{ $ticketItem?->bw_pages_count ?? 0 }} B/W + {{ $ticketItem?->color_pages_count ?? 0 }} Col
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-stone-950 border border-stone-800">
                            <span class="text-stone-500 text-[10px] uppercase font-bold block">Calculated Spine</span>
                            <span class="font-black text-emerald-400 font-mono mt-0.5 block">
                                ~{{ $ticketItem?->estimated_spine_thickness_mm ?? '15.0' }} mm
                            </span>
                        </div>
                    </div>

                    <!-- Hot Foil Stamping Specifications Card -->
                    <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 space-y-3">
                        <div class="flex items-center justify-between border-b border-stone-800/80 pb-2">
                            <h4 class="font-black text-amber-400 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                <flux:icon name="sparkles" class="size-3.5 text-amber-400" />
                                Hot Foil Stamping & Cover Metadata
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-stone-800 text-stone-300">
                                    Cover: {{ $ticketItem?->cover_color ?? 'Maroon' }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    Foil: {{ $ticketItem?->foil_color ?? 'Gold' }}
                                </span>
                            </div>
                        </div>

                        @if (count($customFields) > 0)
                            <div class="grid grid-cols-1 gap-2.5">
                                @foreach ($customFields as $fieldKey => $fieldValue)
                                    <div class="p-3 rounded-xl bg-stone-900/70 border border-stone-800/80 flex items-start justify-between gap-3">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-stone-500 block">
                                                {{ ucwords(str_replace('_', ' ', $fieldKey)) }}
                                            </span>
                                            <p class="font-bold text-white text-xs mt-0.5 select-all">{{ $fieldValue }}</p>
                                        </div>
                                        <button 
                                            onclick="navigator.clipboard.writeText('{{ addslashes($fieldValue) }}')"
                                            class="p-1.5 rounded-lg text-stone-500 hover:text-amber-400 hover:bg-stone-800 transition-colors"
                                            title="Copy Text"
                                        >
                                            <flux:icon name="clipboard" class="size-3.5" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-stone-500 text-xs italic">No custom foil fields submitted.</p>
                        @endif
                    </div>

                    <!-- PDF Asset Download & Preview Link -->
                    @if ($ticketItem?->document_file_path)
                        <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <flux:icon name="document-arrow-down" class="size-8 text-blue-400 shrink-0" />
                                <div>
                                    <p class="font-bold text-white text-xs">{{ $ticketItem->document_original_name ?? 'Manuscript.pdf' }}</p>
                                    <p class="text-[10px] text-blue-300">Ready for printer rasterization & production</p>
                                </div>
                            </div>
                            <a 
                                href="{{ Storage::url($ticketItem->document_file_path) }}" 
                                target="_blank" 
                                class="px-4 py-2 rounded-xl text-xs font-black bg-blue-500 hover:bg-blue-400 text-stone-950 transition-colors flex items-center gap-1.5 shadow-sm"
                            >
                                <flux:icon name="arrow-down-tray" class="size-3.5" />
                                <span>Download PDF</span>
                            </a>
                        </div>
                    @endif

                    <!-- Machine Assignment & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-1.5">
                                Assigned Equipment / Workstation
                            </label>
                            <select 
                                wire:model="assignedMachine"
                                class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none"
                            >
                                <option value="Canon Laser ImageRUNNER Pro 1">Canon Laser ImageRUNNER Pro 1</option>
                                <option value="Epson Heavy Duty WorkForce 2">Epson Heavy Duty WorkForce 2</option>
                                <option value="Digital Hot Foil Press Alpha">Digital Hot Foil Press Alpha</option>
                                <option value="Fastback Thermal Binding Press">Fastback Thermal Binding Press</option>
                                <option value="Manual Stamping Jig Station">Manual Stamping Jig Station</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-1.5">
                                Operator & QA Notes
                            </label>
                            <textarea 
                                wire:model="staffNotes"
                                rows="2"
                                placeholder="Internal instructions, spine adjust notes..."
                                class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl px-3.5 py-2 text-xs text-white placeholder-stone-600 outline-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Ticket Footer -->
                <div class="p-4 border-t border-stone-800 bg-stone-950/80 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-stone-500">
                        Status: <strong class="text-amber-400">{{ $ticketOrder->stageLabel() }}</strong>
                    </span>
                    
                    <div class="flex items-center gap-2">
                        <button 
                            wire:click="$set('showJobTicketModal', false)"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-stone-800 hover:bg-stone-700 text-stone-300 transition-colors"
                        >
                            Close
                        </button>
                        <button 
                            wire:click="saveJobDetails"
                            class="px-5 py-2 rounded-xl text-xs font-black bg-amber-500 hover:bg-amber-400 text-stone-950 transition-colors shadow-sm shadow-amber-500/20"
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
        <div class="fixed inset-0 z-50 bg-stone-950/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-stone-900 border border-stone-800 rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center gap-3 text-red-400">
                    <flux:icon name="exclamation-triangle" class="size-6 shrink-0" />
                    <h3 class="text-base font-black text-white">Quality Inspection Rejection</h3>
                </div>

                <p class="text-xs text-stone-400">
                    Specify the defect detected. The order will step back to the Binding stage for correction.
                </p>

                <div>
                    <label class="block text-[11px] font-bold text-stone-400 mb-1">Reason for Rejection</label>
                    <textarea 
                        wire:model="rejectReason"
                        rows="3"
                        placeholder="e.g. Misaligned gold foil text, crooked chipboard margin, missing colored page..."
                        class="w-full bg-stone-950 border border-stone-800 focus:border-red-500 rounded-xl p-3 text-xs text-white placeholder-stone-600 outline-none"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        wire:click="$set('showRejectModal', false)"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-stone-800 hover:bg-stone-700 text-stone-300 transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="submitQcRejection"
                        class="px-5 py-2 rounded-xl text-xs font-black bg-red-500 hover:bg-red-400 text-stone-950 transition-colors"
                    >
                        Step Back Job
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Material Spoilage Logging Modal -->
    @if ($showSpoilageModal)
        <div class="fixed inset-0 z-50 bg-stone-950/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-stone-900 border border-stone-800 rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center gap-3 text-amber-400">
                    <flux:icon name="trash" class="size-6 shrink-0 text-red-400" />
                    <div>
                        <h3 class="text-base font-black text-white">Report Material Spoilage</h3>
                        <p class="text-xs text-stone-400">Deduct damaged or wasted materials from inventory</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-stone-400 mb-1">Select Spoiled Material</label>
                        <select 
                            wire:model="spoilageItemId"
                            class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-white outline-none"
                        >
                            @foreach ($this->inventoryItems as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }} (In Stock: {{ $inv->stock_qty }} {{ $inv->unit }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-stone-400 mb-1">Quantity Wasted</label>
                        <input 
                            wire:model="spoilageQty"
                            type="number" 
                            step="0.1" 
                            min="0.1" 
                            class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-white outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-stone-400 mb-1">Reason / Notes</label>
                        <textarea 
                            wire:model="spoilageReason"
                            rows="2"
                            placeholder="e.g. Printer paper jam on fuser unit, crooked foil stamp alignment..."
                            class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl p-3 text-xs text-white placeholder-stone-600 outline-none"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        wire:click="$set('showSpoilageModal', false)"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-stone-800 hover:bg-stone-700 text-stone-300 transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="submitSpoilage"
                        class="px-5 py-2 rounded-xl text-xs font-black bg-red-500 hover:bg-red-400 text-stone-950 transition-colors"
                    >
                        Confirm Spoilage Deduction
                    </button>
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
