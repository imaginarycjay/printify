<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\PrintShop;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\SalesAnalyticsService;
use App\Services\SpecRevisionService;
use App\Support\Navigation;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Sales & Financial Analytics Hub')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    #[Url(as: 'tab', except: 'overview')]
    public string $active_tab = 'overview'; // overview, payments, reports

    public function mount(): void
    {
        if (! in_array($this->active_tab, ['overview', 'payments', 'reports'], true)) {
            $this->active_tab = 'overview';
        }
    }
    public string $report_type = 'sales'; // inventory, sales, schedule
    public string $selected_period = 'this_month'; // today, 7d, this_month, ytd, all_time
    public string $selected_service = 'all'; // all, thesis_binding
    public string $selected_fulfillment = 'all'; // all, full_package, cover_only
    public string $search_query = '';

    #[Locked]
    public ?int $rejecting_payment_id = null;
    public string $rejection_reason = '';

    #[Locked]
    public ?int $settling_order_id = null;
    public float $counter_amount = 0.0;
    public string $counter_method = 'cash_counter';
    public string $counter_reference_no = '';

    public function getShopProperty(): ?PrintShop
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->isOwner() && $user->printShop) {
            return $user->printShop;
        }

        return $user->operationalShop();
    }

    /**
     * Download transaction ledger CSV.
     */
    public function downloadCsv(SalesAnalyticsService $analyticsService): StreamedResponse
    {
        $shop = $this->shop;
        if (! $shop) {
            abort(404);
        }

        $csvData = $analyticsService->generateCsvExport($shop, $this->selected_period, $this->selected_service);
        $filename = 'sales-ledger-' . ($shop->slug ?? 'shop') . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Verify payment for an order and instantly reflect in metrics.
     */
    public function verifyPayment(int $orderId): void
    {
        $shop = $this->shop;
        $user = auth()->user();
        if (! $shop || ! $user) {
            return;
        }

        $order = Order::where('print_shop_id', $shop->id)->find($orderId);
        if (! $order) {
            return;
        }

        $pendingPayment = $order->payments()->where('status', Payment::STATUS_PENDING)->latest()->first();
        if ($pendingPayment) {
            app(PaymentService::class)->verifyPayment($pendingPayment, $user);
        } else {
            $remaining = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
            if ($remaining > 0.0) {
                app(PaymentService::class)->recordOfflineCounterPayment($order, $user, $remaining);
            }
        }

        \Flux\Flux::toast(
            text: "Payment for Order {$order->order_number} verified and confirmed.",
            heading: 'Payment Confirmed',
            variant: 'success'
        );
    }

    public function approvePayment(int $paymentId): void
    {
        $shop = $this->shop;
        $user = auth()->user();
        if (! $shop || ! $user) {
            return;
        }

        $payment = Payment::where('print_shop_id', $shop->id)->findOrFail($paymentId);
        app(PaymentService::class)->verifyPayment($payment, $user);

        \Flux\Flux::toast(
            text: "Verified ₱".number_format((float) $payment->amount, 2)." (Ref: {$payment->reference_no}).",
            heading: 'Payment Approved',
            variant: 'success'
        );
    }

    public function openRejectModal(int $paymentId): void
    {
        $shop = $this->shop;
        if (! $shop) {
            return;
        }

        Payment::where('print_shop_id', $shop->id)->findOrFail($paymentId);
        $this->rejecting_payment_id = $paymentId;
        $this->rejection_reason = '';
        $this->resetValidation();
    }

    public function closeRejectModal(): void
    {
        $this->rejecting_payment_id = null;
        $this->rejection_reason = '';
        $this->resetValidation();
    }

    public function confirmRejectPayment(): void
    {
        $this->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $shop = $this->shop;
        $user = auth()->user();
        if (! $shop || ! $user || ! $this->rejecting_payment_id) {
            return;
        }

        $payment = Payment::where('print_shop_id', $shop->id)->findOrFail($this->rejecting_payment_id);
        app(PaymentService::class)->rejectPayment($payment, $user, $this->rejection_reason);

        $this->closeRejectModal();

        \Flux\Flux::toast(
            text: 'Payment proof rejected and customer notified to resubmit.',
            heading: 'Payment Proof Rejected',
            variant: 'warning'
        );
    }

    public function openCounterSettlementModal(int $orderId): void
    {
        $shop = $this->shop;
        if (! $shop) {
            return;
        }

        $order = Order::where('print_shop_id', $shop->id)->findOrFail($orderId);
        $this->settling_order_id = $order->id;
        $this->counter_amount = max(0.0, round((float) $order->total_amount - (float) $order->amount_paid, 2));
        $this->counter_method = 'cash_counter';
        $this->counter_reference_no = '';
        $this->resetValidation();
    }

    public function closeCounterSettlementModal(): void
    {
        $this->settling_order_id = null;
        $this->counter_amount = 0.0;
        $this->counter_reference_no = '';
        $this->resetValidation();
    }

    public function recordCounterSettlement(): void
    {
        $this->validate([
            'counter_amount' => ['required', 'numeric', 'min:0.01'],
            'counter_method' => ['required', 'in:cash_counter,gcash,maya,bank_transfer'],
        ]);

        $shop = $this->shop;
        $user = auth()->user();
        if (! $shop || ! $user || ! $this->settling_order_id) {
            return;
        }

        $order = Order::where('print_shop_id', $shop->id)->findOrFail($this->settling_order_id);
        app(PaymentService::class)->recordOfflineCounterPayment(
            order: $order,
            actor: $user,
            amount: (float) $this->counter_amount,
            paymentMethod: $this->counter_method,
            referenceNo: trim($this->counter_reference_no) !== '' ? trim($this->counter_reference_no) : null,
        );

        $this->closeCounterSettlementModal();

        \Flux\Flux::toast(
            text: "Counter settlement recorded for Order {$order->order_number}.",
            heading: 'Counter Payment Recorded',
            variant: 'success'
        );
    }

    public function settleManualRefund(int $orderId): void
    {
        $shop = $this->shop;
        $user = auth()->user();
        if (! $shop || ! $user) {
            return;
        }

        $order = Order::where('print_shop_id', $shop->id)->findOrFail($orderId);
        app(SpecRevisionService::class)->settleManualRefund($order, $user);

        \Flux\Flux::toast(
            text: "Manual refund settled for Order {$order->order_number}.",
            heading: 'Refund Settled',
            variant: 'success'
        );
    }
}; ?>

<div class="min-h-screen w-full pq-page flex flex-col justify-between relative">
    @php
        $user = auth()->user();
        $shop = $this->shop;
        $analyticsBack = Navigation::backTarget($user, 'owner.analytics-hub', ['tab' => $active_tab]) ?? [
            'url' => route('owner.dashboard'),
            'label' => 'App Launcher',
        ];
        $analyticsService = app(SalesAnalyticsService::class);
        
        $metrics = $shop ? $analyticsService->getMetrics($shop, $selected_period, $selected_service, $selected_fulfillment) : [
            'gross_revenue' => 0.0,
            'paid_orders_count' => 0,
            'all_orders_count' => 0,
            'average_order_value' => 0.0,
            'raw_material_cost' => 0.0,
            'spoilage_loss' => 0.0,
            'net_profit' => 0.0,
            'profit_margin_pct' => 0.0,
            'rush_revenue' => 0.0,
            'pending_payments_count' => 0,
        ];

        $timeline = $shop ? $analyticsService->getTimelineData($shop, $selected_period, $selected_service) : [
            'labels' => [],
            'revenue' => [],
            'orders' => [],
            'max_revenue' => 1000.0,
            'total_revenue' => 0.0,
            'total_orders' => 0,
        ];

        $productMix = $shop ? $analyticsService->getProductMix($shop, $selected_period) : [
            'full_package_count' => 0,
            'cover_only_count' => 0,
            'hardbound_count' => 0,
            'softbound_count' => 0,
            'rush_count' => 0,
            'regular_count' => 0,
            'top_colors' => [],
        ];

        // Recent Orders query
        $ordersQuery = Order::where('print_shop_id', $shop?->id ?? 0)
            ->with(['customer', 'items', 'payments'])
            ->latest('created_at');

        if ($selected_service !== 'all') {
            $ordersQuery->where(function ($q) use ($selected_service): void {
                $q->where('service_key', $selected_service)
                    ->orWhereHas('items', fn ($iq) => $iq->where('service_key', $selected_service));
            });
        }

        if ($selected_fulfillment !== 'all') {
            $ordersQuery->whereHas('items', fn ($q) => $q->where('fulfillment_type', $selected_fulfillment));
        }

        if (trim($search_query) !== '') {
            $search = '%' . trim($search_query) . '%';
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
                    ->orWhere('tracking_code', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)->orWhere('email', 'like', $search));
            });
        }

        $recentOrders = $ordersQuery->take(15)->get();

        $pendingProofs = $shop
            ? Payment::where('print_shop_id', $shop->id)
                ->where('status', Payment::STATUS_PENDING)
                ->with(['order.customer', 'submittedBy'])
                ->orderBy('submitted_at')
                ->orderBy('created_at')
                ->get()
            : collect();

        $balanceDueOrders = $shop
            ? Order::where('print_shop_id', $shop->id)
                ->where('order_status', '!=', Order::STATUS_CANCELLED)
                ->where('balance_due', '>', 0)
                ->with(['customer', 'items', 'payments'])
                ->latest('created_at')
                ->take(25)
                ->get()
            : collect();

        $settlingOrder = $settling_order_id && $shop
            ? Order::where('print_shop_id', $shop->id)->find($settling_order_id)
            : null;

        $manualRefundOrders = $shop
            ? Order::where('print_shop_id', $shop->id)
                ->where('refund_status', Order::REFUND_MANUAL_PENDING)
                ->where('refund_due', '>', 0)
                ->with(['customer', 'items'])
                ->latest('updated_at')
                ->get()
            : collect();

        $reportService = app(ReportService::class);
        $reportData = ($shop && $active_tab === 'reports')
            ? match ($report_type) {
                'inventory' => $reportService->buildInventoryReport($shop, $selected_period),
                'schedule' => $reportService->buildProductionScheduleReport($shop),
                default => $reportService->buildSalesReport($shop, $selected_period),
            }
            : null;
    @endphp

    <!-- Top Navigation Header -->
    <header class="w-full pq-topbar sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-3">
        <!-- Brand & Hub Identity -->
        <div class="flex items-center gap-3.5 min-w-0">
            <x-app-back-link :href="$analyticsBack['url']" :label="$analyticsBack['label']" :navigate="true" />
            <span class="hidden sm:flex size-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300 shrink-0">
                <flux:icon name="chart-bar-square" class="size-5" />
            </span>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight truncate">
                    Sales & Financial Analytics Hub
                </h1>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 truncate">{{ $shop ? $shop->name : 'PressQueue' }} &bull; Live Cashflow &amp; BOM Net Profit Margins</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
            <livewire:notification-bell />
            <x-theme-toggle />

            <button 
                wire:click="downloadCsv"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-slate-950 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 text-slate-700 dark:text-slate-300 hover:text-emerald-700 dark:hover:text-emerald-400 border border-slate-300 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-500/30 transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
                <flux:icon name="arrow-down-tray" class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                <span class="hidden sm:inline">Export CSV Ledger</span>
            </button>

            <!-- Owner Profile Badge -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:border-blue-600 dark:hover:border-blue-400 rounded-xl py-1 px-2.5 transition-colors cursor-pointer"
                title="Account Settings"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-blue-600/40" />
                @else
                    <span class="size-6 rounded-full bg-slate-900 text-white dark:bg-blue-600 text-[10px] font-black flex items-center justify-center">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <div class="leading-tight hidden md:block text-left">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">{{ $user?->name }}</p>
                    <p class="text-[9px] text-slate-500 uppercase font-semibold">Shop Owner</p>
                </div>
            </a>
        </div>
    </header>

    <!-- Main Analytics Hub Body -->
    <main class="flex-1 w-full px-4 sm:px-8 py-6 space-y-6 max-w-full">
        <!-- Hub Navigation Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <button
                wire:click="$set('active_tab', 'overview')"
                class="px-4 py-2 rounded-xl text-xs transition-colors cursor-pointer {{ $active_tab === 'overview' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-300 dark:border-slate-800 font-bold' }}"
            >
                Analytics Overview
            </button>
            <button
                wire:click="$set('active_tab', 'payments')"
                class="px-4 py-2 rounded-xl text-xs transition-colors cursor-pointer flex items-center gap-2 {{ $active_tab === 'payments' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-300 dark:border-slate-800 font-bold' }}"
            >
                <span>Payments &amp; Audit Queue</span>
                @if ($pendingProofs->isNotEmpty())
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black tabular-nums {{ $active_tab === 'payments' ? 'bg-white text-blue-700' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' }}">
                        {{ $pendingProofs->count() }}
                    </span>
                @endif
            </button>
            <button
                wire:click="$set('active_tab', 'reports')"
                class="px-4 py-2 rounded-xl text-xs transition-colors cursor-pointer flex items-center gap-2 {{ $active_tab === 'reports' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-300 dark:border-slate-800 font-bold' }}"
            >
                <flux:icon name="document-chart-bar" class="size-3.5" />
                <span>Standardized Reports</span>
            </button>
        </div>

        @if ($active_tab === 'overview')
        <!-- Filter Controls Bar (Timeframe, Service, and Mode Drilldown) -->
        <div class="pq-card p-4 flex flex-col lg:flex-row items-center justify-between gap-4">
            <!-- Timeframe Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto w-full lg:w-auto pb-1 lg:pb-0">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mr-1 hidden sm:inline">Period:</span>
                @php
                    $periods = [
                        'today' => 'Today',
                        '7d' => 'Last 7 Days',
                        'this_month' => 'This Month (30D)',
                        'ytd' => 'Year-to-Date',
                        'all_time' => 'All Time',
                    ];
                @endphp
                @foreach ($periods as $key => $label)
                    <button 
                        wire:click="$set('selected_period', '{{ $key }}')"
                        class="px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer {{ $selected_period === $key ? 'bg-blue-600 text-white shadow-xs font-black' : 'bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800 font-bold' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Service & Fulfillment Drilldown Filters -->
            <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto justify-start lg:justify-end">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mr-1 hidden sm:inline">Service:</span>
                <button 
                    wire:click="$set('selected_service', 'all')"
                    class="px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer {{ $selected_service === 'all' ? 'bg-slate-900 dark:bg-slate-800 text-white border border-slate-900 dark:border-slate-700 font-bold' : 'bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800 font-semibold' }}"
                >
                    All Services
                </button>

                @php
                    $catalogServices = \App\Services\PrintServiceCatalog::customerServices();
                @endphp
                @foreach ($catalogServices as $sKey => $sConfig)
                    @if (! $shop || $shop->hasService($sKey))
                        <button 
                            wire:click="$set('selected_service', '{{ $sKey }}')"
                            class="px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-colors cursor-pointer inline-flex items-center gap-1.5 {{ $selected_service === $sKey ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-bold' : 'bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800 font-semibold' }}"
                        >
                            @if ($sKey === 'thesis_binding')
                                <flux:icon name="book-open" class="size-3.5" />
                                <span>Thesis Binding</span>
                            @elseif ($sKey === 'document_printing')
                                <flux:icon name="document-text" class="size-3.5" />
                                <span>Document Printing</span>
                            @else
                                {{ $sConfig['name'] }}
                            @endif
                        </button>
                    @endif
                @endforeach

                <div class="h-5 w-px bg-slate-200 dark:bg-slate-800 mx-1 hidden sm:block"></div>

                <!-- Fulfillment Mode Filter -->
                <button 
                    wire:click="$set('selected_fulfillment', 'all')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors cursor-pointer {{ $selected_fulfillment === 'all' ? 'text-slate-900 dark:text-slate-100 bg-slate-200 dark:bg-slate-800' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}"
                >
                    All Modes
                </button>
                <button 
                    wire:click="$set('selected_fulfillment', 'full_package')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors cursor-pointer inline-flex items-center gap-1 {{ $selected_fulfillment === 'full_package' ? 'pq-badge-info' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}"
                >
                    <flux:icon name="document-duplicate" class="size-3" />
                    <span>Full Package</span>
                </button>
                <button 
                    wire:click="$set('selected_fulfillment', 'cover_only')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors cursor-pointer inline-flex items-center gap-1 {{ $selected_fulfillment === 'cover_only' ? 'bg-indigo-50 text-indigo-800 border border-indigo-200 dark:text-indigo-300 dark:bg-indigo-950/60 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}"
                >
                    <flux:icon name="book-open" class="size-3" />
                    <span>Cover Only</span>
                </button>
            </div>
        </div>

        <!-- 4 Executive Financial KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Gross Sales Revenue -->
            <div class="pq-card p-5 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Gross Sales Revenue</span>
                    <span class="size-9 rounded-xl pq-badge-success flex items-center justify-center font-black">
                        ₱
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-emerald-700 dark:text-emerald-400 tracking-tight tabular-nums">
                        ₱{{ number_format($metrics['gross_revenue'], 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">
                        From <strong class="text-slate-800 dark:text-slate-200">{{ $metrics['paid_orders_count'] }} paid orders</strong> ({{ $metrics['all_orders_count'] }} total orders)
                    </p>
                </div>
            </div>

            <!-- 2. Estimated Net Profit & Margin % -->
            <div class="pq-card p-5 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                        <flux:icon name="chart-pie" class="size-3.5" />
                        Net Gross Profit
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold pq-badge-brand tabular-nums">
                        {{ $metrics['profit_margin_pct'] }}% Margin
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight tabular-nums">
                        ₱{{ number_format($metrics['net_profit'], 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">
                        Est. Material Cost: <span class="text-slate-800 dark:text-slate-300 font-semibold">₱{{ number_format($metrics['raw_material_cost'], 2) }}</span>
                    </p>
                </div>
            </div>

            <!-- 3. Average Order Value (AOV) -->
            <div class="pq-card p-5 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Avg. Order Value (AOV)</span>
                    <span class="size-9 rounded-xl pq-badge-info flex items-center justify-center">
                        <flux:icon name="tag" class="size-4" />
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight tabular-nums">
                        ₱{{ number_format($metrics['average_order_value'], 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">
                        Rush Fees Included: <span class="text-amber-700 dark:text-amber-400 font-bold">₱{{ number_format($metrics['rush_revenue'], 2) }}</span>
                    </p>
                </div>
            </div>

            <!-- 4. Material Spoilage & Waste Loss -->
            <div class="pq-card p-5 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 flex items-center gap-1">
                        <flux:icon name="trash" class="size-3.5" />
                        Spoilage / Waste Loss
                    </span>
                    <span class="size-9 rounded-xl pq-badge-danger flex items-center justify-center">
                        <flux:icon name="exclamation-triangle" class="size-4" />
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-rose-700 dark:text-rose-400 tracking-tight tabular-nums">
                        -₱{{ number_format($metrics['spoilage_loss'], 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">
                        Pending Payment Verification: <strong class="text-amber-700 dark:text-amber-400">{{ $metrics['pending_payments_count'] }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Middle Section: Interactive SVG Timeline Chart + Product Mix Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left 2 Cols: Revenue & Order Volume Timeline Chart -->
            <div class="lg:col-span-2 pq-card p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                            <flux:icon name="chart-bar" class="size-4 text-blue-600 dark:text-blue-400" />
                            Cashflow & Sales Velocity Timeline
                        </h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Daily gross revenue peaks and order intake volume</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block">Period Total</span>
                        <strong class="text-sm font-black text-emerald-700 dark:text-emerald-400 font-mono tabular-nums">
                            ₱{{ number_format($timeline['total_revenue'], 2) }}
                        </strong>
                    </div>
                </div>

                <!-- SVG Interactive Timeline Graphic -->
                @php
                    $pointsCount = count($timeline['revenue']);
                    $maxVal = max(100.0, $timeline['max_revenue']);
                @endphp

                @if ($pointsCount > 0)
                    <div class="space-y-4">
                        <!-- Bar & Line Hybrid Chart -->
                        <div class="h-56 w-full flex items-end justify-between gap-1.5 sm:gap-3 pt-6 pb-2 px-2 pq-well relative overflow-hidden">
                            <!-- Background Grid Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between p-3 pointer-events-none opacity-40 dark:opacity-20">
                                <div class="border-b border-slate-300 dark:border-slate-700 w-full"></div>
                                <div class="border-b border-slate-300 dark:border-slate-700 w-full"></div>
                                <div class="border-b border-slate-300 dark:border-slate-700 w-full"></div>
                            </div>

                            @foreach ($timeline['revenue'] as $idx => $rev)
                                @php
                                    $heightPct = min(100, max(6, ($rev / $maxVal) * 100));
                                    $ordersCount = $timeline['orders'][$idx] ?? 0;
                                    $label = $timeline['labels'][$idx] ?? '';
                                @endphp
                                <div class="flex-1 flex flex-col items-center justify-end h-full group relative z-10">
                                    <!-- Tooltip on Hover -->
                                    <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 border border-slate-700 text-white text-[10px] font-bold py-1 px-2 rounded-lg shadow-xl pointer-events-none whitespace-nowrap z-30 tabular-nums">
                                        <span>{{ $label }}: <strong>₱{{ number_format($rev, 2) }}</strong> ({{ $ordersCount }} orders)</span>
                                    </div>

                                    <!-- Bar Container -->
                                    <div 
                                        style="height: {{ $heightPct }}%;" 
                                        class="w-full max-w-[32px] rounded-t-lg {{ $rev > 0 ? 'bg-blue-600 group-hover:bg-blue-700 dark:bg-blue-500 dark:group-hover:bg-blue-400' : 'bg-slate-200 dark:bg-slate-800' }} transition-colors duration-200 flex items-center justify-center"
                                    ></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- X-Axis Labels -->
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 dark:text-slate-400 px-2">
                            <span>{{ $timeline['labels'][0] ?? 'Start' }}</span>
                            @if (count($timeline['labels']) > 2)
                                <span>{{ $timeline['labels'][(int)(count($timeline['labels'])/2)] ?? 'Mid' }}</span>
                            @endif
                            <span>{{ end($timeline['labels']) ?: 'End' }}</span>
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center text-slate-500 dark:text-slate-400">
                        <flux:icon name="chart-bar" class="size-8 mx-auto mb-2 opacity-40" />
                        <p class="text-xs">No transactions in selected period</p>
                    </div>
                @endif
            </div>

            <!-- Right 1 Col: Product Mix & Cover Color Popularity -->
            <div class="pq-card p-6 space-y-5">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h4 class="text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <flux:icon name="squares-2x2" class="size-4 text-indigo-600 dark:text-indigo-400" />
                        Product Mix & Fulfillment
                    </h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Order breakdown by type and options</p>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Fulfillment Mode: Full Package vs Cover-Only -->
                    @php
                        $totFulfillment = max(1, $productMix['full_package_count'] + $productMix['cover_only_count']);
                        $fullPkgPct = round(($productMix['full_package_count'] / $totFulfillment) * 100);
                        $coverOnlyPct = 100 - $fullPkgPct;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between font-bold mb-1.5">
                            <span class="text-slate-800 dark:text-slate-300">Fulfillment Mode</span>
                            <span class="text-slate-600 dark:text-slate-400 font-mono tabular-nums">{{ $productMix['full_package_count'] }} Full / {{ $productMix['cover_only_count'] }} Cover-Only</span>
                        </div>
                        <div class="h-3 w-full bg-slate-100 dark:bg-slate-950 rounded-full overflow-hidden flex border border-slate-200 dark:border-slate-800">
                            <div style="width: {{ $fullPkgPct }}%;" class="bg-blue-600 dark:bg-blue-500 h-full" title="Full Package"></div>
                            <div style="width: {{ $coverOnlyPct }}%;" class="bg-indigo-600 dark:bg-indigo-500 h-full" title="Cover Only"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] mt-1 tabular-nums">
                            <span class="text-blue-700 dark:text-blue-400 font-semibold">&bull; Full Package ({{ $fullPkgPct }}%)</span>
                            <span class="text-indigo-700 dark:text-indigo-400 font-semibold">&bull; Cover Only ({{ $coverOnlyPct }}%)</span>
                        </div>
                    </div>

                    <!-- Hardbound vs Softbound -->
                    @php
                        $totBinding = max(1, $productMix['hardbound_count'] + $productMix['softbound_count']);
                        $hardboundPct = round(($productMix['hardbound_count'] / $totBinding) * 100);
                        $softboundPct = 100 - $hardboundPct;
                    @endphp
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between font-bold mb-1.5">
                            <span class="text-slate-800 dark:text-slate-300">Binding Type</span>
                            <span class="text-slate-600 dark:text-slate-400 font-mono tabular-nums">{{ $productMix['hardbound_count'] }} Hard / {{ $productMix['softbound_count'] }} Soft</span>
                        </div>
                        <div class="h-3 w-full bg-slate-100 dark:bg-slate-950 rounded-full overflow-hidden flex border border-slate-200 dark:border-slate-800">
                            <div style="width: {{ $hardboundPct }}%;" class="bg-indigo-600 dark:bg-indigo-500 h-full" title="Hardbound"></div>
                            <div style="width: {{ $softboundPct }}%;" class="bg-sky-600 dark:bg-sky-500 h-full" title="Softbound"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] mt-1 tabular-nums">
                            <span class="text-indigo-700 dark:text-indigo-400 font-semibold">&bull; Hardbound ({{ $hardboundPct }}%)</span>
                            <span class="text-sky-700 dark:text-sky-400 font-semibold">&bull; Softbound ({{ $softboundPct }}%)</span>
                        </div>
                    </div>

                    <!-- Top Leatherette Cover Colors Leaderboard -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                            Popular Leatherette Cover Colors
                        </span>
                        @forelse ($productMix['top_colors'] as $col)
                            <div class="flex items-center justify-between py-1 border-b border-slate-200/70 dark:border-slate-800/50">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $col['color'] }}</span>
                                <div class="flex items-center gap-2 tabular-nums">
                                    <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px]">{{ $col['count'] }} books</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold pq-badge-brand">
                                        {{ $col['percentage'] }}%
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 dark:text-slate-400 text-xs italic">No color orders logged yet</p>
                        @endforelse
                    </div>

                    <!-- Service Distribution Breakdown -->
                    @if (! empty($productMix['service_breakdown']) && count($productMix['service_breakdown']) > 1)
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                                Service Distribution
                            </span>
                            @foreach ($productMix['service_breakdown'] as $srv)
                                <div class="flex items-center justify-between py-1 border-b border-slate-200/70 dark:border-slate-800/50">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $srv['name'] }}</span>
                                    <div class="flex items-center gap-2 tabular-nums">
                                        <span class="text-slate-600 dark:text-slate-400 font-mono text-[11px]">{{ $srv['count'] }} orders</span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold pq-badge-info">
                                            {{ $srv['percentage'] }}%
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Section: Searchable Financial Transaction Ledger Table -->
        <div class="pq-card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <flux:icon name="document-text" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        Financial Transaction Ledger
                    </h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Real-time breakdown of paid customer sales orders and margins</p>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <flux:icon name="magnifying-glass" class="size-3.5 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input 
                            wire:model.live.debounce.200ms="search_query"
                            type="text" 
                            placeholder="Search Order # or Customer..."
                            class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none transition-colors"
                        />
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="p-3.5">Order #</th>
                            <th class="p-3.5">Date</th>
                            <th class="p-3.5">Customer</th>
                            <th class="p-3.5">Service & Mode</th>
                            <th class="p-3.5">Copies & Pages</th>
                            <th class="p-3.5">Payment</th>
                            <th class="p-3.5 text-right">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 font-medium">
                        @forelse ($recentOrders as $order)
                            @php
                                $item = $order->items->first();
                                $isCoverOnly = $item?->isCoverOnly() ?? false;
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                                <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $order->order_number }}
                                    @if ($order->is_rush)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold pq-badge-warning ml-1">
                                            RUSH
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                    {{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $order->customer->name ?? 'Customer' }}</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $order->customer->email ?? '' }}</p>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isCoverOnly ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800' : 'pq-badge-info' }}">
                                            {{ $isCoverOnly ? 'Cover Only' : 'Full Package' }}
                                        </span>
                                        <span class="text-slate-600 dark:text-slate-400 text-[11px]">({{ ucfirst($item?->binding_type ?? 'Hardbound') }})</span>
                                    </div>
                                </td>
                                <td class="p-3.5 tabular-nums">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">x{{ $item?->copies_count ?? 1 }} copy</span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block">{{ $item?->total_pages_count ?? 0 }} pages</span>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ in_array($order->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified']) ? 'pq-badge-success' : 'pq-badge-warning' }}">
                                            {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                                        </span>
                                        @if (! in_array($order->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified', Order::PAYMENT_REJECTED]))
                                            <button 
                                                wire:click="verifyPayment({{ $order->id }})"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-xs cursor-pointer inline-flex items-center gap-1 whitespace-nowrap"
                                                title="Confirm & Verify Payment"
                                            >
                                                <flux:icon name="check" class="size-3" />
                                                <span>Verify</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold text-emerald-700 dark:text-emerald-400 text-sm space-y-1 tabular-nums">
                                    <div>₱{{ number_format($order->total_amount, 2) }}</div>
                                    <div class="flex items-center justify-end gap-2 text-[10px] font-sans font-bold">
                                        <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="text-blue-700 dark:text-blue-400 hover:underline">Receipt</a>
                                        <a href="{{ route('orders.receipt', ['order' => $order, 'format' => 'pdf']) }}" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-500 dark:text-slate-400">
                                    No sales transactions matching your filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if ($active_tab === 'payments')
            <div class="space-y-6">
                <!-- Pending Online Proofs Queue -->
                <div class="pq-card p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Pending Payment Proof Audit Queue</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Proofs pending over 2 hours are flagged as aging for priority review.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold pq-badge-warning tabular-nums">
                            {{ $pendingProofs->count() }} Pending
                        </span>
                    </div>

                    @if ($pendingProofs->isEmpty())
                        <div class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-300 dark:border-slate-800 rounded-2xl">
                            No pending online payment proofs requiring verification.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                    <tr>
                                        <th class="p-3">Order &amp; Tracking</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Type &amp; Method</th>
                                        <th class="p-3">Reference</th>
                                        <th class="p-3">Submitted</th>
                                        <th class="p-3 text-right">Amount</th>
                                        <th class="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @foreach ($pendingProofs as $proof)
                                        <tr wire:key="pending-proof-{{ $proof->id }}">
                                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                                {{ $proof->order?->order_number }}
                                                @if ($proof->order?->tracking_code)
                                                    <span class="block text-[10px] text-blue-700 dark:text-blue-400">{{ $proof->order->tracking_code }}</span>
                                                @endif
                                            </td>
                                            <td class="p-3">{{ $proof->order?->customer?->name ?? $proof->submittedBy?->name ?? 'Customer' }}</td>
                                            <td class="p-3">
                                                <span class="font-bold text-slate-900 dark:text-slate-200">{{ ucwords(str_replace('_', ' ', $proof->payment_type)) }}</span>
                                                <span class="block text-[10px] text-slate-500 dark:text-slate-400">{{ strtoupper($proof->payment_method) }}</span>
                                            </td>
                                            <td class="p-3 font-mono">
                                                <span class="text-slate-900 dark:text-slate-100 font-bold">{{ $proof->reference_no ?? '—' }}</span>
                                                @if ($proof->proof_path)
                                                    <a href="{{ route('orders.file', ['order' => $proof->order_id, 'kind' => 'payment', 'payment' => $proof->id]) }}" target="_blank" class="block text-[10px] text-blue-700 dark:text-blue-400 hover:underline mt-0.5">
                                                        Download Proof
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                                {{ ($proof->submitted_at ?? $proof->created_at)?->diffForHumans() }}
                                                @if ($proof->isAging(2))
                                                    <span class="mt-1 inline-flex px-2 py-0.5 rounded pq-badge-danger text-[10px] font-bold">
                                                        Aging (&gt;2h)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="p-3 text-right font-mono font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">₱{{ number_format((float) $proof->amount, 2) }}</td>
                                            <td class="p-3 text-right space-x-1.5 whitespace-nowrap">
                                                <button type="button" wire:click="approvePayment({{ $proof->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer">
                                                    Accept &amp; Queue
                                                </button>
                                                <button type="button" wire:click="openRejectModal({{ $proof->id }})" class="px-2.5 py-1 rounded-lg pq-badge-danger hover:bg-rose-100 dark:hover:bg-rose-500/30 text-[11px] font-bold cursor-pointer">
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Counter & Pickup Balance Settlement Table -->
                <div class="pq-card p-6 space-y-4">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Counter &amp; Pickup Balance Settlement</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Record cash or offline counter payments for unpaid orders or remaining 50% downpayment balances.</p>
                    </div>

                    @if ($balanceDueOrders->isEmpty())
                        <div class="p-6 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-300 dark:border-slate-800 rounded-2xl">
                            All active orders have zero balance due.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                    <tr>
                                        <th class="p-3">Order</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Payment Status</th>
                                        <th class="p-3 text-right">Total</th>
                                        <th class="p-3 text-right">Paid</th>
                                        <th class="p-3 text-right">Balance Due</th>
                                        <th class="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @foreach ($balanceDueOrders as $balOrder)
                                        <tr wire:key="balance-order-{{ $balOrder->id }}">
                                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                                {{ $balOrder->order_number }}
                                                @if ($balOrder->tracking_code)
                                                    <span class="block text-[10px] text-blue-700 dark:text-blue-400">{{ $balOrder->tracking_code }}</span>
                                                @endif
                                            </td>
                                            <td class="p-3">{{ $balOrder->customer?->name ?? 'Customer' }}</td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $balOrder->paymentStatusBadgeColor() }}">
                                                    {{ $balOrder->paymentStatusLabel() }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $balOrder->total_amount, 2) }}</td>
                                            <td class="p-3 text-right font-mono text-emerald-700 dark:text-emerald-400 tabular-nums">₱{{ number_format((float) $balOrder->amount_paid, 2) }}</td>
                                            <td class="p-3 text-right font-mono font-bold text-amber-700 dark:text-amber-300 tabular-nums">₱{{ number_format((float) $balOrder->balance_due, 2) }}</td>
                                            <td class="p-3 text-right space-x-1.5 whitespace-nowrap">
                                                <button type="button" wire:click="openCounterSettlementModal({{ $balOrder->id }})" class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold cursor-pointer dark:hover:bg-blue-500">
                                                    Record Counter Pay
                                                </button>
                                                <a href="{{ route('orders.receipt', $balOrder) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-[11px] font-bold">
                                                    Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($manualRefundOrders->isNotEmpty())
                    <div class="pq-card !border-rose-300 dark:!border-rose-500/30 p-6 space-y-4">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                            <h3 class="text-base font-bold text-rose-700 dark:text-rose-300">Pending Manual Refunds (Spec Downgrades)</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Refunds triggered when an already-paid item was downgraded prior to printing.</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                    <tr>
                                        <th class="p-3">Order</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3 text-right">Paid</th>
                                        <th class="p-3 text-right">Adjusted Total</th>
                                        <th class="p-3 text-right">Refund Due</th>
                                        <th class="p-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @foreach ($manualRefundOrders as $refundOrder)
                                        <tr wire:key="manual-refund-{{ $refundOrder->id }}">
                                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">{{ $refundOrder->order_number }}</td>
                                            <td class="p-3">{{ $refundOrder->customer?->name ?? 'Customer' }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $refundOrder->amount_paid, 2) }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $refundOrder->total_amount, 2) }}</td>
                                            <td class="p-3 text-right font-mono font-bold text-rose-700 dark:text-rose-300 tabular-nums">₱{{ number_format((float) $refundOrder->refund_due, 2) }}</td>
                                            <td class="p-3 text-right">
                                                <button type="button" wire:click="settleManualRefund({{ $refundOrder->id }})" class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold cursor-pointer">
                                                    Mark Refund Settled
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($active_tab === 'reports' && $shop && $reportData)
            @php($reportSummary = $reportData['summary'])
            <div class="space-y-6">
                <!-- Report Sub-Tabs, Period Filter & Standardized Exports -->
                <div class="pq-card p-4 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ([
                            'inventory' => 'Inventory & Stock',
                            'sales' => 'Sales & Revenue',
                            'schedule' => 'Live Production Schedule',
                        ] as $typeKey => $typeLabel)
                            <button
                                type="button"
                                wire:click="$set('report_type', '{{ $typeKey }}')"
                                class="px-3.5 py-2 rounded-xl text-xs transition-colors cursor-pointer {{ $report_type === $typeKey ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-300 dark:border-slate-800 font-bold' }}"
                            >
                                {{ $typeLabel }}
                            </button>
                        @endforeach

                        @if ($report_type !== 'schedule')
                            <select
                                wire:model.live="selected_period"
                                aria-label="Report period"
                                class="ml-0 sm:ml-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs font-bold text-slate-800 dark:text-slate-200"
                            >
                                <option value="today">Today</option>
                                <option value="7d">Last 7 Days</option>
                                <option value="this_month">This Month</option>
                                <option value="ytd">Year-to-Date</option>
                                <option value="all_time">All Time</option>
                            </select>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('owner.reports.show', ['type' => $report_type, 'period' => $selected_period]) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-800 transition-colors"
                        >
                            <flux:icon name="printer" class="size-3.5" />
                            <span>Print View</span>
                        </a>
                        <a
                            href="{{ route('owner.reports.show', ['type' => $report_type, 'period' => $selected_period, 'format' => 'pdf']) }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition-colors shadow-xs dark:hover:bg-blue-500"
                        >
                            <flux:icon name="document-arrow-down" class="size-3.5" />
                            <span>Download PDF</span>
                        </a>
                        <a
                            href="{{ route('owner.reports.show', ['type' => $report_type, 'period' => $selected_period, 'format' => 'csv']) }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold pq-badge-success hover:bg-emerald-100 dark:hover:bg-emerald-500/25 transition-colors"
                        >
                            <flux:icon name="arrow-down-tray" class="size-3.5" />
                            <span>Export CSV</span>
                        </a>
                    </div>
                </div>

                @if ($report_type === 'inventory')
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Total SKUs</p>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1 tabular-nums">{{ $reportSummary['total_skus'] }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Stock Valuation</p>
                            <p class="text-xl font-black text-blue-700 dark:text-blue-400 mt-1 tabular-nums">₱{{ number_format((float) $reportSummary['total_valuation'], 2) }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Low / Out of Stock</p>
                            <p class="text-xl font-black text-rose-700 dark:text-rose-400 mt-1 tabular-nums">{{ $reportSummary['low_stock_count'] }} / {{ $reportSummary['out_of_stock_count'] }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Spoilage &amp; Rework Loss</p>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1 tabular-nums">₱{{ number_format((float) $reportSummary['spoilage_loss_cost'], 2) }}</p>
                        </div>
                    </div>

                    <div class="pq-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Current Stock Ledger</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ count($reportData['items']) }} items</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/70 text-[10px] uppercase text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3">SKU</th>
                                        <th class="p-3">Item</th>
                                        <th class="p-3">Category</th>
                                        <th class="p-3 text-right">Physical</th>
                                        <th class="p-3 text-right">Reserved</th>
                                        <th class="p-3 text-right">Available</th>
                                        <th class="p-3 text-right">Valuation</th>
                                        <th class="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @forelse ($reportData['items'] as $invRow)
                                        <tr>
                                            <td class="p-3 font-mono text-slate-500 dark:text-slate-400">{{ $invRow['sku'] }}</td>
                                            <td class="p-3 font-bold text-slate-900 dark:text-white">{{ $invRow['name'] }}</td>
                                            <td class="p-3 text-slate-600 dark:text-slate-400">{{ $invRow['category'] }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">{{ $invRow['stock_qty'] }} {{ $invRow['unit'] }}</td>
                                            <td class="p-3 text-right font-mono text-amber-700 dark:text-amber-300 tabular-nums">{{ $invRow['reserved_qty'] }} {{ $invRow['unit'] }}</td>
                                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">{{ $invRow['available_qty'] }} {{ $invRow['unit'] }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $invRow['stock_value'], 2) }}</td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $invRow['status'] === 'Out of Stock' ? 'pq-badge-danger' : ($invRow['status'] === 'Low Stock' ? 'pq-badge-warning' : 'pq-badge-success') }}">
                                                    {{ $invRow['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="p-4 text-center text-slate-500 dark:text-slate-400">No inventory items recorded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pq-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Stock Movements &amp; Rework Audit</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ count($reportData['movements']) }} entries</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/70 text-[10px] uppercase text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3">Date</th>
                                        <th class="p-3">Item</th>
                                        <th class="p-3">Type</th>
                                        <th class="p-3 text-right">Qty</th>
                                        <th class="p-3 text-right">Before &rarr; After</th>
                                        <th class="p-3">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @forelse ($reportData['movements'] as $movRow)
                                        <tr>
                                            <td class="p-3 text-slate-500 dark:text-slate-400">{{ $movRow['created_at'] }}</td>
                                            <td class="p-3 font-bold text-slate-900 dark:text-white">{{ $movRow['item_name'] }}</td>
                                            <td class="p-3">{{ ucwords(str_replace('_', ' ', $movRow['movement_type'])) }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">{{ $movRow['quantity'] }} {{ $movRow['unit'] }}</td>
                                            <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-400 tabular-nums">{{ $movRow['previous_stock'] }} &rarr; {{ $movRow['resulting_stock'] }}</td>
                                            <td class="p-3 text-slate-600 dark:text-slate-400">{{ $movRow['reference_note'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="p-4 text-center text-slate-500 dark:text-slate-400">No stock movements during this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($report_type === 'sales')
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Gross Billed</p>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1 tabular-nums">₱{{ number_format((float) $reportSummary['gross_billed'], 2) }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">50% Downpayments / 100% Full</p>
                            <p class="text-sm font-black text-blue-700 dark:text-blue-400 mt-1 tabular-nums">₱{{ number_format((float) $reportSummary['downpayments_50'], 2) }} / ₱{{ number_format((float) $reportSummary['full_prepayments_100'], 2) }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Counter &amp; Net Cash Collected</p>
                            <p class="text-xl font-black text-emerald-700 dark:text-emerald-400 mt-1 tabular-nums">₱{{ number_format((float) $reportSummary['net_cash_collected'], 2) }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">Counter/Balance: ₱{{ number_format((float) $reportSummary['counter_balance_settlements'], 2) }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Receivables / Refunds Settled</p>
                            <p class="text-sm font-black text-rose-700 dark:text-rose-300 mt-1 tabular-nums">Due: ₱{{ number_format((float) $reportSummary['outstanding_receivables'], 2) }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">Refunded: ₱{{ number_format((float) $reportSummary['manual_refunds_settled'], 2) }}</p>
                        </div>
                    </div>

                    <div class="pq-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Verified Payment &amp; Refund Ledger</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ count($reportData['payments']) }} verified entries</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/70 text-[10px] uppercase text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3">Date</th>
                                        <th class="p-3">Order #</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Type</th>
                                        <th class="p-3">Reference</th>
                                        <th class="p-3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @forelse ($reportData['payments'] as $payRow)
                                        <tr>
                                            <td class="p-3 text-slate-500 dark:text-slate-400">{{ $payRow['verified_at'] }}</td>
                                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">{{ $payRow['order_number'] }}</td>
                                            <td class="p-3">{{ $payRow['customer_name'] }}</td>
                                            <td class="p-3">{{ ucwords(str_replace('_', ' ', $payRow['payment_type'])) }}</td>
                                            <td class="p-3 font-mono text-slate-500 dark:text-slate-400">{{ $payRow['reference_no'] }}</td>
                                            <td class="p-3 text-right font-mono font-bold tabular-nums {{ (float) $payRow['amount'] < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-400' }}">₱{{ number_format((float) $payRow['amount'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="p-4 text-center text-slate-500 dark:text-slate-400">No verified payments recorded in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pq-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Orders Reconciliation Summary</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ count($reportData['orders']) }} orders</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-50 dark:bg-slate-950/70 text-[10px] uppercase text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="p-3">Order #</th>
                                        <th class="p-3">Customer</th>
                                        <th class="p-3">Service</th>
                                        <th class="p-3">Payment Status</th>
                                        <th class="p-3 text-right">Total</th>
                                        <th class="p-3 text-right">Paid</th>
                                        <th class="p-3 text-right">Balance Due</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                    @forelse ($reportData['orders'] as $ordRow)
                                        <tr>
                                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">{{ $ordRow['order_number'] }}</td>
                                            <td class="p-3">{{ $ordRow['customer_name'] }}</td>
                                            <td class="p-3">{{ $ordRow['service_label'] }}</td>
                                            <td class="p-3 text-slate-700 dark:text-slate-300">{{ $ordRow['payment_status'] }}</td>
                                            <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $ordRow['total_amount'], 2) }}</td>
                                            <td class="p-3 text-right font-mono text-emerald-700 dark:text-emerald-400 tabular-nums">₱{{ number_format((float) $ordRow['amount_paid'], 2) }}</td>
                                            <td class="p-3 text-right font-mono text-amber-700 dark:text-amber-300 font-semibold tabular-nums">₱{{ number_format((float) $ordRow['balance_due'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="p-4 text-center text-slate-500 dark:text-slate-400">No orders recorded in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Shop Workload Status</p>
                            <p class="text-xl font-black text-blue-700 dark:text-blue-400 mt-1">{{ $reportSummary['workload_status'] }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Currently Printing</p>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1 tabular-nums">{{ $reportSummary['currently_printing_count'] }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">In Finishing / QC</p>
                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1 tabular-nums">{{ $reportSummary['in_finishing_count'] }}</p>
                        </div>
                        <div class="pq-card p-4">
                            <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">Ready for Pickup (Aging)</p>
                            <p class="text-xl font-black text-emerald-700 dark:text-emerald-400 mt-1 tabular-nums">{{ $reportSummary['ready_for_pickup_count'] }} ({{ $reportSummary['aging_pickup_count'] }})</p>
                        </div>
                    </div>

                    @foreach ([
                        'Currently Printing' => $reportData['currently_printing'],
                        'Already Printed / In Finishing & QC' => $reportData['in_finishing'],
                        'Ready for Pickup (Shelf / Bin & Aging)' => $reportData['ready_for_pickup'],
                    ] as $bucketHeading => $bucketItems)
                        <div class="pq-card overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ $bucketHeading }}</h3>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ count($bucketItems) }} jobs</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                    <thead class="bg-slate-50 dark:bg-slate-950/70 text-[10px] uppercase text-slate-500 dark:text-slate-400">
                                        <tr>
                                            <th class="p-3">Order #</th>
                                            <th class="p-3">Customer</th>
                                            <th class="p-3">Service &amp; Qty</th>
                                            <th class="p-3">Printer</th>
                                            <th class="p-3">Operator</th>
                                            <th class="p-3">Shelf / Bin</th>
                                            <th class="p-3 text-right">Balance Due</th>
                                            <th class="p-3">Status / Aging</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                        @forelse ($bucketItems as $jobRow)
                                            <tr>
                                                <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">{{ $jobRow['order_number'] }}</td>
                                                <td class="p-3">{{ $jobRow['customer_name'] }}</td>
                                                <td class="p-3">{{ $jobRow['service_label'] }} (x{{ $jobRow['copies_count'] }}, {{ $jobRow['paper_size'] }})</td>
                                                <td class="p-3 text-slate-700 dark:text-slate-300">{{ $jobRow['printer_name'] }}</td>
                                                <td class="p-3 text-slate-600 dark:text-slate-400">{{ $jobRow['staff_name'] }}</td>
                                                <td class="p-3 font-mono font-bold text-blue-700 dark:text-blue-400">{{ $jobRow['shelf_bin_code'] }}</td>
                                                <td class="p-3 text-right font-mono tabular-nums">₱{{ number_format((float) $jobRow['balance_due'], 2) }}</td>
                                                <td class="p-3">
                                                    @if ($jobRow['is_aging'])
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-danger">Aging ({{ $jobRow['aging_days'] }}d)</span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-neutral">{{ $jobRow['stage_label'] }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="p-4 text-center text-slate-500 dark:text-slate-400">No jobs currently in this bucket.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </main>

    <!-- Reject Payment Modal -->
    @if ($rejecting_payment_id)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Reject Payment Proof</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400">Provide a clear reason so the customer can submit an accurate payment reference.</p>
                <div>
                    <textarea wire:model="rejection_reason" rows="3" placeholder="e.g. Reference number does not match shop GCash history..." class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-rose-500"></textarea>
                    @error('rejection_reason') <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeRejectModal" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="confirmRejectPayment" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-bold text-white cursor-pointer">Confirm Reject</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Counter Settlement Modal -->
    @if ($settlingOrder)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-4 shadow-xl">
                <div>
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Record Counter / Offline Payment</span>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">{{ $settlingOrder->order_number }}</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 tabular-nums">Remaining balance: ₱{{ number_format((float) $settlingOrder->balance_due, 2) }}</p>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Amount Collected (PHP)</label>
                        <input type="number" step="0.01" wire:model="counter_amount" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                        @error('counter_amount') <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Method</label>
                        <select wire:model="counter_method" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                            <option value="cash_counter">Cash at Counter</option>
                            <option value="gcash">Counter GCash</option>
                            <option value="maya">Counter Maya</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reference / OR Note (Optional)</label>
                        <input type="text" wire:model="counter_reference_no" placeholder="Optional counter receipt ref..." class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 font-mono" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeCounterSettlementModal" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Cancel</button>
                    <button type="button" wire:click="recordCounterSettlement" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white cursor-pointer">Record Payment</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer Bar -->
    <footer class="w-full bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 px-4 sm:px-8 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-2">
        <span>&copy; {{ date('Y') }} {{ $shop ? $shop->name : 'PressQueue' }} &bull; Sales & Financial Analytics Hub</span>
        <span class="font-medium">
            Financial Ledger & Bullseye BOM Net Profit Calculation
        </span>
    </footer>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
