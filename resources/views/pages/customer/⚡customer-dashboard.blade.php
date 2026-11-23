<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintShop;
use App\Models\SpecRevision;
use App\Services\PaymentService;
use App\Services\PrintServiceCatalog;
use App\Services\ProductionQueueService;
use App\Services\SpecRevisionService;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $active_tab = 'services'; // 'services' or 'orders'
    public ?int $viewing_order_id = null;

    #[Locked]
    public ?int $paying_order_id = null;

    public string $payment_option = Order::PAYMENT_OPTION_FULL_100;
    public string $payment_method = 'gcash';
    public float $payment_amount = 0.0;
    public string $payment_reference_no = '';
    public mixed $payment_proof_file = null;
    public ?string $payment_feedback = null;

    #[Locked]
    public ?int $reuploading_item_id = null;
    public mixed $reupload_file = null;

    #[Locked]
    public ?int $revising_item_id = null;
    public int $rev_bw_pages = 0;
    public int $rev_color_pages = 0;
    public int $rev_total_pages = 1;
    public int $rev_copies_count = 1;
    public string $rev_binding_type = 'hardbound';
    public string $rev_cover_color = 'Maroon';
    public string $rev_print_sides = 'simplex';
    public string $rev_notes = '';

    public function viewOrderDetails(int $id): void
    {
        $this->viewing_order_id = $id;
    }

    public function closeOrderModal(): void
    {
        $this->viewing_order_id = null;
    }

    public function openPaymentModal(int $orderId): void
    {
        $this->resetValidation();
        $this->payment_feedback = null;
        $order = Order::where('customer_id', auth()->id())->findOrFail($orderId);

        $this->paying_order_id = $order->id;
        $remaining = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));

        if ((float) $order->amount_paid <= 0.0 && $order->payment_option === Order::PAYMENT_OPTION_DOWNPAYMENT_50) {
            $this->payment_option = Order::PAYMENT_OPTION_DOWNPAYMENT_50;
            $this->payment_amount = $order->minimumUpfrontAmount();
        } else {
            $this->payment_option = Order::PAYMENT_OPTION_FULL_100;
            $this->payment_amount = $remaining;
        }

        $this->payment_method = 'gcash';
        $this->payment_reference_no = '';
        $this->payment_proof_file = null;
    }

    public function selectPaymentOption(string $option): void
    {
        if (! in_array($option, [Order::PAYMENT_OPTION_DOWNPAYMENT_50, Order::PAYMENT_OPTION_FULL_100], true)) {
            return;
        }

        $this->payment_option = $option;

        if ($this->paying_order_id) {
            $order = Order::where('customer_id', auth()->id())->find($this->paying_order_id);
            if ($order) {
                $remaining = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
                $this->payment_amount = ($option === Order::PAYMENT_OPTION_DOWNPAYMENT_50 && (float) $order->amount_paid <= 0.0)
                    ? $order->minimumUpfrontAmount()
                    : $remaining;
            }
        }
    }

    public function closePaymentModal(): void
    {
        $this->paying_order_id = null;
        $this->payment_proof_file = null;
        $this->resetValidation();
    }

    public function submitPaymentProof(): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if (! $user || ! $this->paying_order_id) {
            return;
        }

        $order = Order::where('customer_id', $user->id)->findOrFail($this->paying_order_id);
        $uploadedFile = $this->payment_proof_file instanceof UploadedFile ? $this->payment_proof_file : null;

        app(PaymentService::class)->submitProof(
            order: $order,
            customer: $user,
            paymentOption: $this->payment_option,
            paymentMethod: $this->payment_method,
            amount: (float) $this->payment_amount,
            referenceNo: $this->payment_reference_no,
            proofFile: $uploadedFile,
        );

        $this->payment_feedback = "Payment proof submitted for Order {$order->order_number}. Awaiting shop verification.";
        $this->closePaymentModal();
    }

    public function openReuploadModal(int $itemId): void
    {
        $this->resetValidation();
        $item = OrderItem::whereHas('order', fn ($q) => $q->where('customer_id', auth()->id()))->findOrFail($itemId);
        $this->reuploading_item_id = $item->id;
        $this->reupload_file = null;
    }

    public function closeReuploadModal(): void
    {
        $this->reuploading_item_id = null;
        $this->reupload_file = null;
        $this->resetValidation();
    }

    public function submitArtworkReupload(): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if (! $user || ! $this->reuploading_item_id) {
            return;
        }

        $item = OrderItem::whereHas('order', fn ($q) => $q->where('customer_id', $user->id))->findOrFail($this->reuploading_item_id);
        $file = $this->reupload_file instanceof UploadedFile ? $this->reupload_file : null;
        if (! $file) {
            $this->addError('artwork_file', 'Please select a corrected PDF artwork file to upload.');

            return;
        }

        app(ProductionQueueService::class)->reuploadArtwork($item, $user, $file);
        $this->payment_feedback = 'Corrected artwork uploaded and returned to prepress queue.';
        $this->closeReuploadModal();
    }

    public function openSpecRevisionModal(int $itemId): void
    {
        $this->resetValidation();
        $item = OrderItem::with('order')->whereHas('order', fn ($q) => $q->where('customer_id', auth()->id()))->findOrFail($itemId);
        abort_unless($item->canReviseSpecifications(), 422, 'Specifications are locked once printing starts.');

        $this->revising_item_id = $item->id;
        $this->rev_bw_pages = (int) $item->bw_pages_count;
        $this->rev_color_pages = (int) $item->color_pages_count;
        $this->rev_total_pages = max(1, (int) $item->total_pages_count);
        $this->rev_copies_count = max(1, (int) $item->copies_count);
        $this->rev_binding_type = $item->binding_type ?: 'hardbound';
        $this->rev_cover_color = $item->cover_color ?: 'Maroon';
        $this->rev_print_sides = $item->getPrintSides();
        $this->rev_notes = '';
    }

    public function closeSpecRevisionModal(): void
    {
        $this->revising_item_id = null;
        $this->rev_notes = '';
        $this->resetValidation();
    }

    public function submitSpecRevision(): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if (! $user || ! $this->revising_item_id) {
            return;
        }

        $this->validate([
            'rev_copies_count' => ['required', 'integer', 'min:1', 'max:500'],
            'rev_bw_pages' => ['required', 'integer', 'min:0', 'max:2000'],
            'rev_color_pages' => ['required', 'integer', 'min:0', 'max:2000'],
            'rev_total_pages' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $item = OrderItem::with('order')->whereHas('order', fn ($q) => $q->where('customer_id', $user->id))->findOrFail($this->revising_item_id);

        $newSpecs = $item->isThesisBinding()
            ? [
                'binding_type' => $item->isCoverOnly() ? 'hardbound' : $this->rev_binding_type,
                'bw_pages' => $this->rev_bw_pages,
                'color_pages' => $this->rev_color_pages,
                'cover_color' => $this->rev_cover_color,
                'print_sides' => $this->rev_print_sides,
                'copies_count' => $this->rev_copies_count,
            ]
            : [
                'total_pages' => $this->rev_total_pages,
                'print_sides' => $this->rev_print_sides,
                'copies_count' => $this->rev_copies_count,
            ];

        app(SpecRevisionService::class)->requestRevision(
            item: $item,
            actor: $user,
            newSpecs: $newSpecs,
            notes: trim($this->rev_notes) !== '' ? trim($this->rev_notes) : null,
        );

        $this->payment_feedback = 'Specification revision requested. Item is temporarily placed on hold awaiting shop approval.';
        $this->closeSpecRevisionModal();
    }
}; ?>

<div class="min-h-screen w-full pq-page relative pb-24">
    @php
        $user = auth()->user();
        $shop = null;
        $orders = $user ? $user->orders()->with(['items.printer', 'items.specRevisions', 'printShop', 'payments'])->get() : collect();
        $activeOrdersCount = $orders->whereNotIn('order_status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])->count();
        $catalog = PrintServiceCatalog::all();
        $selectedOrder = $this->viewing_order_id ? Order::where('customer_id', auth()->id())->with(['items.printer', 'items.specRevisions', 'printShop', 'payments'])->find($this->viewing_order_id) : null;
        $payingOrder = $this->paying_order_id ? Order::where('customer_id', auth()->id())->find($this->paying_order_id) : null;
        $reuploadingItem = $this->reuploading_item_id ? OrderItem::with('order')->whereHas('order', fn ($q) => $q->where('customer_id', auth()->id()))->find($this->reuploading_item_id) : null;
        $revisingItem = $this->revising_item_id ? OrderItem::with('order')->whereHas('order', fn ($q) => $q->where('customer_id', auth()->id()))->find($this->revising_item_id) : null;
    @endphp

    <!-- Top Navigation Header -->
    <header class="w-full pq-topbar sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-500 text-stone-950 font-bold shadow-sm shrink-0">
                    <flux:icon name="shopping-bag" class="size-5" />
                </span>
                <div class="min-w-0">
                    <h1 class="text-base font-bold text-stone-900 dark:text-white tracking-tight truncate">{{ $shop ? $shop->name : 'PressQueue' }}</h1>
                    <p class="text-[11px] text-stone-600 dark:text-stone-400 truncate">Order Online &amp; Track Real-Time Print Production</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('shops.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-stone-300 dark:border-stone-800 bg-white dark:bg-stone-900 px-3 text-xs font-bold text-stone-700 dark:text-stone-300 hover:border-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                    <flux:icon name="building-storefront" class="size-4" />
                    <span>Find shops</span>
                </a>
                <livewire:notification-bell />
                <x-theme-toggle />

                <!-- Profile Settings Link -->
                <a
                    href="{{ route('profile.edit') }}"
                    wire:navigate
                    class="group flex items-center gap-2 bg-stone-50 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-amber-400 dark:hover:border-amber-500/50 rounded-full py-1 px-3 transition-colors cursor-pointer"
                >
                    @if ($user?->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-amber-500/50" />
                    @else
                        <span class="size-6 rounded-full bg-amber-500 text-stone-950 text-xs font-extrabold flex items-center justify-center">
                            {{ $user?->initials() }}
                        </span>
                    @endif
                    <span class="text-xs font-bold text-stone-800 dark:text-stone-200 group-hover:text-amber-700 dark:group-hover:text-amber-300 hidden sm:inline">{{ $user?->name }}</span>
                    <flux:icon name="cog-6-tooth" class="size-3.5 text-stone-500 dark:text-stone-400 group-hover:text-amber-600 dark:group-hover:text-amber-400" />
                </a>

                <!-- Log Out Button -->
                <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                    @csrf
                    <button
                        type="submit"
                        title="Log Out"
                        class="group flex items-center gap-1.5 bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-800 hover:border-red-300 dark:hover:border-red-500/50 hover:bg-red-50 dark:hover:bg-red-500/10 text-red-700 dark:text-red-400 rounded-full py-1.5 px-3 transition-colors cursor-pointer"
                    >
                        <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                        <span class="text-xs font-bold hidden sm:inline">Log Out</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 pt-6 space-y-8 relative z-20">
        <!-- Hero Header & Tab Switcher -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-4">
            <div>
                <h2 class="text-2xl font-bold text-stone-900 dark:text-white tracking-tight">Find a shop. Track your prints.</h2>
                <p class="text-sm text-stone-600 dark:text-stone-400">Choose a service to compare shops. Each shop sets its own prices and availability.</p>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center p-1 rounded-2xl bg-stone-100 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shrink-0">
                <button
                    wire:click="$set('active_tab', 'services')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'services' ? 'bg-amber-500 text-stone-950 shadow-sm font-black' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white' }}"
                >
                    <flux:icon name="squares-2x2" class="size-4" />
                    <span>Find shops</span>
                </button>
                <button
                    wire:click="$set('active_tab', 'orders')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'orders' ? 'bg-amber-500 text-stone-950 shadow-sm font-black' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white' }}"
                >
                    <flux:icon name="clock" class="size-4" />
                    <span>My Orders</span>
                    @if ($activeOrdersCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black tabular-nums {{ $active_tab === 'orders' ? 'bg-stone-950 text-amber-400' : 'bg-amber-500 text-stone-950' }}">
                            {{ $activeOrdersCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        <!-- TAB 1: AVAILABLE PRINTING SERVICES CATALOG -->
        @if ($active_tab === 'services')
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 1. Hardbound & Softbound Thesis Binding (ACTIVE MVP) -->
                    <div class="pq-card !border-amber-300 dark:!border-amber-500/40 hover:!border-amber-500 p-6 transition-colors space-y-5 flex flex-col justify-between group">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="size-12 rounded-xl bg-amber-500 text-stone-950 flex items-center justify-center shadow-sm">
                                    <flux:icon name="book-open" class="size-6" />
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-warning">Thesis Binding</span>
                            </div>

                            <div>
                                <h3 class="text-base font-bold text-stone-900 dark:text-white group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">Hardbound &amp; Softbound Thesis Binding</h3>
                                <p class="text-xs text-stone-600 dark:text-stone-400 mt-1 leading-relaxed">Custom hot foil stamping, heavy duty leatherette covers, and instant manuscript page calculation.</p>
                            </div>

                            <p class="pq-well p-3 text-xs text-stone-600 dark:text-stone-400">View shop rates and binding workload before you order.</p>
                        </div>

                        <a
                            href="{{ route('customer.order-thesis') }}"
                            wire:navigate
                            class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold uppercase tracking-wider text-center shadow-sm block transition-colors"
                        >
                            Find Thesis Binding Shops &rarr;
                        </a>
                    </div>

                    <!-- 2. Document Printing -->
                    <div class="pq-card hover:!border-blue-500/60 p-6 space-y-5 flex flex-col justify-between group transition-colors">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="size-12 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                                    <flux:icon name="document-text" class="size-6" />
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-info">Document Printing</span>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-stone-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">Document Printing &amp; Ring Binding</h3>
                                <p class="text-xs text-stone-600 dark:text-stone-400 mt-1 leading-relaxed">High-speed monochrome &amp; color PDF documents, duplex printing, sliding folders, and ring binding.</p>
                            </div>
                            <p class="pq-well p-3 text-xs text-stone-600 dark:text-stone-400">View shop rates and printing workload before you order.</p>
                        </div>
                        <a
                            href="{{ route('customer.order-document') }}"
                            wire:navigate
                            class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider text-center shadow-sm block transition-colors"
                        >
                            Find Document Printing Shops &rarr;
                        </a>
                    </div>

                </div>
            </div>
        @endif

        <!-- TAB 2: MY ORDERS & REAL-TIME PROGRESS TRACKER -->
        @if ($active_tab === 'orders')
            <div class="space-y-6">
                @if ($payment_feedback)
                    <div class="p-4 rounded-2xl pq-badge-success text-xs font-bold">
                        {{ $payment_feedback }}
                    </div>
                @endif

                @if ($orders->isEmpty())
                    <div class="pq-card p-12 text-center border-dashed space-y-3">
                        <flux:icon name="shopping-bag" class="size-12 mx-auto text-stone-400 dark:text-stone-600" />
                        <div class="text-sm font-bold text-stone-800 dark:text-stone-300">You haven't placed any printing orders yet</div>
                        <p class="text-xs text-stone-500 dark:text-stone-400">Order thesis hardbounds online to track real-time progress right from your screen.</p>
                        <a href="{{ route('customer.order-thesis') }}" wire:navigate class="inline-block px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold mt-2">
                            Place Your First Order &rarr;
                        </a>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($orders as $order)
                            @php
                                $stage = $order->currentStageIndex();
                                $remainingBalance = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
                                $latestRejectedPayment = $order->payments->firstWhere('status', Payment::STATUS_REJECTED);
                                $hasPendingProof = $order->payments->contains('status', Payment::STATUS_PENDING);
                                $canSubmitPayment = $order->order_status !== Order::STATUS_CANCELLED
                                    && $remainingBalance > 0.0
                                    && ($order->payment_status !== Order::PAYMENT_VERIFIED_PAID)
                                    && (! $hasPendingProof || $order->payment_status === Order::PAYMENT_REJECTED);
                            @endphp
                            <div wire:key="customer-order-{{ $order->id }}" class="pq-card p-6 sm:p-8 space-y-6">
                                <!-- Order Header Info -->
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono font-bold text-amber-700 dark:text-amber-400 text-sm sm:text-base">{{ $order->order_number }}</span>
                                            @if ($order->tracking_code)
                                                <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-stone-950 border border-stone-300 dark:border-stone-700 font-mono text-[10px] font-bold text-stone-800 dark:text-stone-200">{{ $order->tracking_code }}</span>
                                            @endif
                                            <span class="text-sm font-semibold text-stone-800 dark:text-stone-300">{{ $order->printShop?->name }}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $order->statusBadgeColor() }}">
                                                {{ $order->order_status === \App\Models\Order::STATUS_COMPLETED || $order->production_stage === \App\Models\Order::STAGE_COMPLETED ? 'Completed / Received' : ucfirst(str_replace('_', ' ', $order->order_status)) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $order->paymentStatusBadgeColor() }}">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                            @php
                                                $firstItem = $order->items->first();
                                            @endphp
                                            @if ($order->isMultiService())
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30">
                                                    Multi-Service Order
                                                </span>
                                            @elseif ($order->service_key === 'document_printing' || ($firstItem && $firstItem->isDocumentPrinting()))
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-info inline-flex items-center gap-1">
                                                    <flux:icon name="document-text" class="size-3" />
                                                    <span>Document Print</span>
                                                </span>
                                            @elseif ($firstItem && $firstItem->isCoverOnly())
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-warning inline-flex items-center gap-1">
                                                    <flux:icon name="archive-box" class="size-3" />
                                                    <span>Cover Only</span>
                                                </span>
                                                @if (! $firstItem->is_paper_received)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-danger">
                                                        Paper Drop-off Needed
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-success">
                                                        Paper In Shop
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-stone-600 dark:text-stone-400 mt-1 tabular-nums">
                                            Placed on {{ $order->created_at->format('M d, Y • h:i A') }} &bull; Promised Ready: <strong class="text-stone-800 dark:text-stone-200">{{ $order->target_completion_date ? $order->target_completion_date->format('M d, Y') : 'N/A' }}</strong>
                                            @if ((float) $order->amount_paid > 0)
                                                &bull; Paid: <strong class="text-emerald-700 dark:text-emerald-400">₱{{ number_format((float) $order->amount_paid, 2) }}</strong>
                                                &bull; Balance Due: <strong class="text-amber-700 dark:text-amber-300">₱{{ number_format($remainingBalance, 2) }}</strong>
                                            @endif
                                        </p>
                                        @if ($order->payment_status === Order::PAYMENT_REJECTED && $latestRejectedPayment?->rejection_reason)
                                            <p class="text-[11px] text-red-700 dark:text-red-300 font-semibold mt-1">
                                                Payment Proof Rejected: {{ $latestRejectedPayment->rejection_reason }}
                                            </p>
                                        @endif
                                        @if ($order->refund_status === Order::REFUND_MANUAL_PENDING && (float) $order->refund_due > 0)
                                            <p class="text-[11px] text-rose-700 dark:text-rose-300 font-semibold mt-1 tabular-nums">
                                                Manual Refund Pending: ₱{{ number_format((float) $order->refund_due, 2) }} to be settled by shop.
                                            </p>
                                        @elseif ($order->refund_status === Order::REFUND_SETTLED)
                                            <p class="text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold mt-1">
                                                Manual Refund Settled
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                        <div class="text-right mr-1">
                                            <span class="text-[10px] text-stone-500 dark:text-stone-400 block">{{ $order->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID ? 'Total Paid' : 'Order Total' }}</span>
                                            <span class="text-lg font-black text-stone-900 dark:text-white tabular-nums">₱{{ number_format($order->total_amount, 2) }}</span>
                                        </div>
                                        @if ($canSubmitPayment)
                                            <button
                                                wire:click="openPaymentModal({{ $order->id }})"
                                                class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold transition-colors whitespace-nowrap cursor-pointer shadow-sm"
                                            >
                                                {{ $order->payment_status === Order::PAYMENT_REJECTED ? 'Retry Payment Proof' : ($order->payment_status === Order::PAYMENT_PARTIALLY_PAID ? 'Pay Balance' : 'Submit Payment Proof') }}
                                            </button>
                                        @endif
                                        <button
                                            wire:click="viewOrderDetails({{ $order->id }})"
                                            class="px-3.5 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-stone-800 dark:text-stone-200 text-xs font-bold transition-colors whitespace-nowrap cursor-pointer"
                                        >
                                            View Receipt
                                        </button>
                                        <a
                                            href="{{ route('orders.receipt', ['order' => $order, 'format' => 'pdf']) }}"
                                            class="px-3 py-2 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 hover:border-amber-500 text-amber-700 dark:text-amber-400 text-xs font-bold transition-colors whitespace-nowrap"
                                        >
                                            PDF
                                        </a>
                                    </div>
                                </div>

                                <!-- Split Item Status & Pickup Tracker -->
                                @if ($order->items->isNotEmpty())
                                    <div class="space-y-2 border-t border-stone-200 dark:border-stone-800 pt-3">
                                        @foreach ($order->items as $custItem)
                                            <div wire:key="cust-item-{{ $custItem->id }}" class="pq-well flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-3.5 py-2.5 text-xs">
                                                <div class="space-y-0.5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-bold text-stone-900 dark:text-white">{{ $custItem->isThesisBinding() ? 'Thesis Binding' : 'Document Printing' }} (x{{ $custItem->copies_count }})</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-neutral">
                                                            {{ $custItem->stageLabel() }}
                                                        </span>
                                                        @if ($custItem->printer)
                                                            <span class="text-[10px] text-stone-500 dark:text-stone-400">Printer: {{ $custItem->printer->name }}</span>
                                                        @endif
                                                        @if ($custItem->shelf_bin_code && $custItem->isReadyForPickup())
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-success">
                                                                Ready at Shelf/Bin: {{ $custItem->shelf_bin_code }}
                                                            </span>
                                                        @endif
                                                        @if ($custItem->is_spec_locked)
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30">
                                                                Specs Locked
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if ($custItem->prepress_status === OrderItem::PREPRESS_REJECTED)
                                                        <p class="text-[11px] text-red-700 dark:text-red-300 font-semibold">
                                                            Artwork Rejected: {{ $custItem->prepress_rejection_reason ?: 'Please upload a corrected PDF.' }}
                                                        </p>
                                                    @endif
                                                    @if ($custItem->specRevisions->where('status', SpecRevision::STATUS_PENDING)->isNotEmpty())
                                                        <p class="text-[11px] text-amber-700 dark:text-amber-300 font-semibold">
                                                            Specification revision pending shop approval (Item on hold).
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                                    @if ($custItem->prepress_status === OrderItem::PREPRESS_REJECTED && ! $custItem->hasStartedPrinting())
                                                        <button type="button" wire:click="openReuploadModal({{ $custItem->id }})" class="px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold cursor-pointer">
                                                            Re-upload PDF
                                                        </button>
                                                    @endif
                                                    @if ($custItem->canReviseSpecifications() && $custItem->specRevisions->where('status', SpecRevision::STATUS_PENDING)->isEmpty())
                                                        <button type="button" wire:click="openSpecRevisionModal({{ $custItem->id }})" class="px-2.5 py-1 rounded-lg bg-white dark:bg-stone-800 hover:bg-stone-100 dark:hover:bg-stone-700 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-200 text-[11px] font-bold cursor-pointer">
                                                            Request Revision
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- 5-Stage Visual Progress Stepper -->
                                <div class="space-y-2 pt-2">
                                    <div class="text-xs font-bold text-stone-700 dark:text-stone-300">Live Production Progress</div>
                                    <div class="grid grid-cols-5 gap-1 sm:gap-4 relative pt-2">
                                        <!-- Step 1 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold mx-auto {{ $stage >= 1 ? 'bg-emerald-600 text-white ring-2 sm:ring-4 ring-emerald-500/20' : 'bg-stone-200 dark:bg-stone-800 text-stone-500' }}">1</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 1 ? 'text-stone-900 dark:text-white' : 'text-stone-500' }} block leading-tight">Order Placed</span>
                                        </div>

                                        <!-- Step 2 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold mx-auto {{ $stage >= 2 ? 'bg-emerald-600 text-white ring-2 sm:ring-4 ring-emerald-500/20' : 'bg-stone-200 dark:bg-stone-800 text-stone-500' }}">2</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 2 ? 'text-stone-900 dark:text-white' : 'text-stone-500' }} block leading-tight">
                                                @if ($order->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID)
                                                    Paid &amp; Queued
                                                @elseif ($order->payment_status === \App\Models\Order::PAYMENT_UNPAID)
                                                    Queued (Counter Pay)
                                                @else
                                                    Queued (Reviewing)
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Step 3 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold mx-auto {{ $stage >= 3 ? 'bg-amber-500 text-stone-950 ring-2 sm:ring-4 ring-amber-500/20' : 'bg-stone-200 dark:bg-stone-800 text-stone-500' }}">3</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 3 ? 'text-amber-700 dark:text-amber-400' : 'text-stone-500' }} block leading-tight">Production</span>
                                        </div>

                                        <!-- Step 4 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold mx-auto {{ $stage >= 4 ? 'bg-emerald-600 text-white' : 'bg-stone-200 dark:bg-stone-800 text-stone-500' }}">4</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 4 ? 'text-stone-900 dark:text-white' : 'text-stone-500' }} block leading-tight">Quality Check</span>
                                        </div>

                                        <!-- Step 5 -->
                                        @php
                                            $isOrderCompleted = $order->order_status === \App\Models\Order::STATUS_COMPLETED || $order->production_stage === \App\Models\Order::STAGE_COMPLETED;
                                        @endphp
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold mx-auto {{ $stage >= 5 ? ($isOrderCompleted ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-stone-950 ring-2 sm:ring-4 ring-amber-500/20') : 'bg-stone-200 dark:bg-stone-800 text-stone-500' }}">
                                                @if ($isOrderCompleted)
                                                    <flux:icon name="check" class="size-3.5" />
                                                @else
                                                    5
                                                @endif
                                            </span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 5 ? ($isOrderCompleted ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-amber-700 dark:text-amber-400 font-bold') : 'text-stone-500' }} block leading-tight">
                                                {{ $isOrderCompleted ? 'Completed & Received' : 'Ready for Pickup' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </main>

    <!-- ORDER RECEIPT MODAL -->
    @if ($selectedOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 sm:p-8 shadow-xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-4">
                    <div>
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Official Order Receipt</span>
                        <h3 class="text-lg font-bold text-stone-900 dark:text-white font-mono">{{ $selectedOrder->order_number }}</h3>
                    </div>
                    <button wire:click="closeOrderModal" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close receipt modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Shop & Customer Details -->
                    <div class="pq-well p-3.5 space-y-1">
                        <div class="flex justify-between">
                            <span class="text-stone-500 dark:text-stone-400">Print Shop:</span>
                            <strong class="text-stone-900 dark:text-white">{{ $selectedOrder->printShop?->name }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500 dark:text-stone-400">Customer:</span>
                            <strong class="text-stone-900 dark:text-white">{{ $user?->name }} ({{ $user?->email }})</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500 dark:text-stone-400">Payment Status:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $selectedOrder->paymentStatusBadgeColor() }}">
                                {{ $selectedOrder->paymentStatusLabel() }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500 dark:text-stone-400">Payment Reference:</span>
                            <strong class="text-amber-700 dark:text-amber-400 font-mono">{{ $selectedOrder->payment_reference_no ?? 'N/A' }}</strong>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-2">
                        <span class="font-bold text-stone-700 dark:text-stone-300 block">Item Specifications</span>
                        @foreach ($selectedOrder->items as $item)
                            <div class="pq-well p-3.5 space-y-2">
                                <div class="flex justify-between font-bold text-stone-900 dark:text-white">
                                    <span>
                                        @if ($selectedOrder->service_key === 'document_printing' || $item->isDocumentPrinting())
                                            Document Printing ({{ $item->getFinishingLabel() }})
                                        @else
                                            {{ ucfirst($item->binding_type) }}
                                            @if ($item->isCoverOnly())
                                                <span class="text-amber-700 dark:text-amber-400 text-[10px] font-semibold">(Cover &amp; Binding Only)</span>
                                            @else
                                                Thesis
                                            @endif
                                        @endif
                                        ({{ $item->copies_count }} {{ $item->copies_count > 1 ? 'copies' : 'copy' }})
                                    </span>
                                    <span class="tabular-nums">₱{{ number_format($item->total_price, 2) }}</span>
                                </div>
                                <div class="text-[11px] text-stone-600 dark:text-stone-400 space-y-0.5 tabular-nums">
                                    @if ($selectedOrder->service_key === 'document_printing' || $item->isDocumentPrinting())
                                        <div>Pages: {{ $item->bw_pages_count }} B&amp;W + {{ $item->color_pages_count }} Color &bull; {{ $item->paper_size }} ({{ $item->isDuplex() ? 'Duplex' : 'Simplex' }})</div>
                                        <div>Paper Feed: <strong class="text-sky-700 dark:text-blue-400">{{ $item->getPhysicalSheetsCount() }} sheets</strong> per copy</div>
                                        @if ($item->cover_color && $item->getFinishingType() === 'ring_bind')
                                            <div>Back Cover Board: {{ $item->cover_color }}</div>
                                        @endif
                                    @elseif ($item->isCoverOnly())
                                        <div>Pre-Printed Pages: {{ $item->total_pages_count }} pages &bull; Est. Spine: ~{{ $item->estimated_spine_thickness_mm ?? '10.0' }} mm</div>
                                        <div>Paper Status: <span class="{{ $item->is_paper_received ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }} font-bold">{{ $item->is_paper_received ? 'Received at Shop' : 'Pending Customer Drop-off' }}</span></div>
                                        <div>Cover: {{ $item->cover_color }} &bull; Foil: {{ $item->foil_color ?? 'None' }}</div>
                                    @else
                                        <div>Pages: {{ $item->bw_pages_count }} B&amp;W + {{ $item->color_pages_count }} Color ({{ $item->paper_size }})</div>
                                        <div>Cover: {{ $item->cover_color }} &bull; Foil: {{ $item->foil_color ?? 'None' }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Total Amount / Paid -->
                    <div class="pq-well !border-amber-300 dark:!border-amber-500/30 p-4 space-y-1.5 text-xs tabular-nums">
                        <div class="flex justify-between text-stone-600 dark:text-stone-400">
                            <span>Order Total:</span>
                            <strong class="text-stone-900 dark:text-white">₱{{ number_format($selectedOrder->total_amount, 2) }}</strong>
                        </div>
                        <div class="flex justify-between text-stone-600 dark:text-stone-400">
                            <span>Verified Amount Paid:</span>
                            <strong class="text-emerald-700 dark:text-emerald-400">₱{{ number_format((float) $selectedOrder->amount_paid, 2) }}</strong>
                        </div>
                        <div class="flex justify-between items-center text-sm font-bold text-stone-900 dark:text-white pt-1.5 border-t border-stone-200 dark:border-stone-800">
                            <span>Remaining Balance Due:</span>
                            <span class="text-lg text-amber-700 dark:text-amber-400">₱{{ number_format(max(0.0, round((float) $selectedOrder->total_amount - (float) $selectedOrder->amount_paid, 2)), 2) }}</span>
                        </div>
                    </div>

                    @if ($selectedOrder->payments->isNotEmpty())
                        <div class="space-y-1.5">
                            <span class="font-bold text-stone-700 dark:text-stone-300 block">Payment History</span>
                            @foreach ($selectedOrder->payments as $payment)
                                <div wire:key="receipt-payment-{{ $payment->id }}" class="pq-well flex items-center justify-between p-2.5 text-[11px] tabular-nums">
                                    <div>
                                        <span class="font-mono font-bold text-stone-900 dark:text-white">{{ $payment->reference_no ?? 'Counter' }}</span>
                                        <span class="text-stone-500 dark:text-stone-400 ml-1">({{ ucwords(str_replace('_', ' ', $payment->status)) }})</span>
                                    </div>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format((float) $payment->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pt-2 flex flex-wrap gap-2">
                    <a href="{{ route('orders.receipt', $selectedOrder) }}" target="_blank" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold text-center shadow-sm">
                        Printable Official Receipt
                    </a>
                    <a href="{{ route('orders.receipt', ['order' => $selectedOrder, 'format' => 'pdf']) }}" class="px-4 py-2.5 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-amber-700 dark:text-amber-400 text-xs font-bold text-center">
                        Download PDF
                    </a>
                    <button wire:click="closeOrderModal" class="w-full py-2.5 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200 text-xs font-bold cursor-pointer">
                        Close Receipt
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- PAYMENT PROOF SUBMISSION / RETRY MODAL -->
    @if ($payingOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Submit Payment Proof</span>
                        <h3 class="text-base font-bold text-stone-900 dark:text-white font-mono">{{ $payingOrder->order_number }}</h3>
                    </div>
                    <button type="button" wire:click="closePaymentModal" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close payment modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                @if ((float) $payingOrder->amount_paid <= 0.0)
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="selectPaymentOption('{{ Order::PAYMENT_OPTION_DOWNPAYMENT_50 }}')"
                            class="p-3 rounded-xl border text-left text-xs cursor-pointer {{ $payment_option === Order::PAYMENT_OPTION_DOWNPAYMENT_50 ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10 text-stone-900 dark:text-white' : 'border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950 text-stone-600 dark:text-stone-400' }}"
                        >
                            <div class="font-bold">50% Downpayment</div>
                            <div class="text-[10px] tabular-nums">Min ₱{{ number_format($payingOrder->minimumUpfrontAmount(), 2) }}</div>
                        </button>
                        <button
                            type="button"
                            wire:click="selectPaymentOption('{{ Order::PAYMENT_OPTION_FULL_100 }}')"
                            class="p-3 rounded-xl border text-left text-xs cursor-pointer {{ $payment_option === Order::PAYMENT_OPTION_FULL_100 ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 text-stone-900 dark:text-white' : 'border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950 text-stone-600 dark:text-stone-400' }}"
                        >
                            <div class="font-bold">100% Full</div>
                            <div class="text-[10px] tabular-nums">₱{{ number_format((float) $payingOrder->total_amount, 2) }}</div>
                        </button>
                    </div>
                @endif

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Method</label>
                            <select wire:model="payment_method" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white">
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Amount (PHP)</label>
                            <input type="number" step="0.01" wire:model="payment_amount" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white tabular-nums" />
                            @error('amount') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Reference Number</label>
                        <input type="text" wire:model="payment_reference_no" placeholder="e.g. GCASH-12345678" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white placeholder-stone-400 font-mono" />
                        @error('reference_no') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Receipt Screenshot / PDF (Optional)</label>
                        <input type="file" wire:model="payment_proof_file" class="block w-full text-xs text-stone-600 dark:text-stone-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-stone-200 dark:file:bg-stone-800 file:text-stone-800 dark:file:text-stone-200 file:font-bold cursor-pointer" />
                        @error('proof_file') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="closePaymentModal" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitPaymentProof" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white cursor-pointer">
                        Submit Proof
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- PREPRESS ARTWORK RE-UPLOAD MODAL -->
    @if ($reuploadingItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">Prepress Artwork Replacement</span>
                        <h3 class="text-base font-bold text-stone-900 dark:text-white font-mono">{{ $reuploadingItem->order?->order_number }}</h3>
                    </div>
                    <button type="button" wire:click="closeReuploadModal" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close re-upload modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
                @if ($reuploadingItem->prepress_rejection_reason)
                    <div class="p-3 rounded-xl pq-badge-danger text-xs">
                        Reason: {{ $reuploadingItem->prepress_rejection_reason }}
                    </div>
                @endif
                <div class="space-y-2 text-xs">
                    <label class="block font-bold text-stone-700 dark:text-stone-300">Upload Corrected PDF File</label>
                    <input type="file" wire:model="reupload_file" class="block w-full text-xs text-stone-600 dark:text-stone-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-stone-200 dark:file:bg-stone-800 file:text-stone-800 dark:file:text-stone-200 file:font-bold cursor-pointer" />
                    @error('artwork_file') <p class="text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeReuploadModal" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="submitArtworkReupload" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-xs font-bold text-stone-950 cursor-pointer">Upload Corrected PDF</button>
                </div>
            </div>
        </div>
    @endif

    <!-- SPECIFICATION REVISION REQUEST MODAL -->
    @if ($revisingItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                    <div>
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Request Pre-Print Spec Revision</span>
                        <h3 class="text-base font-bold text-stone-900 dark:text-white font-mono">{{ $revisingItem->order?->order_number }}</h3>
                    </div>
                    <button type="button" wire:click="closeSpecRevisionModal" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close spec revision modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <div class="space-y-3 text-xs">
                    @if ($revisingItem->isThesisBinding())
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">B&amp;W Pages</label>
                                <input type="number" min="0" wire:model="rev_bw_pages" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white tabular-nums" />
                            </div>
                            <div>
                                <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Color Pages</label>
                                <input type="number" min="0" wire:model="rev_color_pages" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white tabular-nums" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Binding Style</label>
                                <select wire:model="rev_binding_type" @disabled($revisingItem->isCoverOnly()) class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white">
                                    <option value="hardbound">Hardbound</option>
                                    <option value="softbound">Softbound</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Cover Color</label>
                                <input type="text" wire:model="rev_cover_color" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white" />
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Total Pages</label>
                            <input type="number" min="1" wire:model="rev_total_pages" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white tabular-nums" />
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Print Sides</label>
                            <select wire:model="rev_print_sides" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white">
                                <option value="simplex">Simplex</option>
                                <option value="duplex">Duplex</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Copies</label>
                            <input type="number" min="1" wire:model="rev_copies_count" class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white tabular-nums" />
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 dark:text-stone-300 mb-1">Revision Note</label>
                        <textarea wire:model="rev_notes" rows="2" placeholder="Optional note explaining change..." class="w-full rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 px-3 py-2 text-xs text-stone-900 dark:text-white placeholder-stone-400"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeSpecRevisionModal" class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="submitSpecRevision" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-xs font-bold text-stone-950 cursor-pointer">Submit Revision Request</button>
                </div>
            </div>
        </div>
    @endif
</div>
