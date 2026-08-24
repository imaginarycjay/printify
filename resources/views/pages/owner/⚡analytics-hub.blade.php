<?php

use App\Models\Order;
use App\Models\PrintShop;
use App\Services\SalesAnalyticsService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Sales & Financial Analytics Hub')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $selected_period = 'this_month'; // today, 7d, this_month, ytd, all_time
    public string $selected_service = 'all'; // all, thesis_binding
    public string $selected_fulfillment = 'all'; // all, full_package, cover_only
    public string $search_query = '';

    public function getShopProperty(): ?PrintShop
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->isOwner() && $user->printShop) {
            return $user->printShop;
        }

        return PrintShop::first();
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
        $order = Order::find($orderId);
        if ($order) {
            $order->update([
                'payment_status' => Order::PAYMENT_VERIFIED_PAID,
                'payment_verified_at' => now(),
                'payment_verified_by' => auth()->id(),
            ]);

            \Flux\Flux::toast(
                text: "Payment of ₱" . number_format($order->total_amount, 2) . " for Order {$order->order_number} verified and confirmed.",
                heading: 'Payment Confirmed',
                variant: 'success'
            );
        }
    }
}; ?>

<div class="min-h-screen w-full bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-between relative selection:bg-amber-500 selection:text-white">
    
    <!-- Ambient Background Glows -->
    <div class="absolute -top-32 left-1/3 size-[600px] rounded-full bg-amber-500/5 blur-[160px] pointer-events-none"></div>
    <div class="absolute top-1/2 -right-32 size-[500px] rounded-full bg-emerald-500/5 blur-[160px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $this->shop;
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
            ->with(['customer', 'items'])
            ->latest('created_at');

        if ($selected_service !== 'all') {
            $ordersQuery->where('service_key', $selected_service);
        }

        if ($selected_fulfillment !== 'all') {
            $ordersQuery->whereHas('items', fn ($q) => $q->where('fulfillment_type', $selected_fulfillment));
        }

        if (trim($search_query) !== '') {
            $search = '%' . trim($search_query) . '%';
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)->orWhere('email', 'like', $search));
            });
        }

        $recentOrders = $ordersQuery->take(15)->get();
    @endphp

    <!-- Top Navigation Header -->
    <header class="w-full bg-stone-900/90 border-b border-stone-800/80 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 py-3.5 flex items-center justify-between">
        <!-- Brand & Hub Identity -->
        <div class="flex items-center gap-3.5">
            <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-yellow-600 text-stone-950 font-black shadow-lg shadow-amber-500/20">
                <flux:icon name="chart-bar-square" class="size-5 text-stone-950" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm sm:text-base font-extrabold text-white tracking-tight">
                        Sales & Financial Analytics Hub
                    </h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Cashflow
                    </span>
                </div>
                <p class="text-[11px] text-stone-400">{{ $shop ? $shop->name : 'Printify' }} &bull; Gross Sales, BOM Net Profit Margins & Cashflow Intelligence</p>
            </div>
        </div>

        <!-- Quick Actions & Back to Launcher -->
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button 
                wire:click="downloadCsv"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-stone-950 hover:bg-emerald-500/10 text-stone-300 hover:text-emerald-400 border border-stone-800 hover:border-emerald-500/30 transition-all flex items-center gap-1.5 shadow-sm"
            >
                <flux:icon name="arrow-down-tray" class="size-3.5 text-emerald-400" />
                <span class="hidden sm:inline">Export CSV Ledger</span>
            </button>

            <a 
                href="{{ route('owner.dashboard') }}" 
                wire:navigate
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-stone-950 hover:bg-stone-800 text-stone-300 border border-stone-800 transition-all flex items-center gap-1.5"
            >
                <flux:icon name="arrow-left" class="size-3.5" />
                <span>App Launcher</span>
            </a>

            <!-- Owner Profile Badge -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2 bg-stone-950 border border-stone-800 hover:border-amber-500/50 hover:bg-stone-900 rounded-xl py-1 px-2.5 transition-all cursor-pointer shadow-sm"
                title="Account Settings"
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
                    <p class="text-[9px] text-stone-500 uppercase font-semibold">Shop Owner</p>
                </div>
            </a>
        </div>
    </header>

    <!-- Main Analytics Hub Body -->
    <main class="flex-1 w-full px-4 sm:px-8 py-6 space-y-6 max-w-full">
        
        <!-- Filter Controls Bar (Timeframe, Service, and Mode Drilldown) -->
        <div class="p-4 rounded-2xl bg-stone-900/80 border border-stone-800/90 flex flex-col lg:flex-row items-center justify-between gap-4 backdrop-blur-sm shadow-md">
            <!-- Timeframe Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto w-full lg:w-auto pb-1 lg:pb-0">
                <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider mr-1 hidden sm:inline">Period:</span>
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
                        class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $selected_period === $key ? 'bg-amber-500 text-stone-950 shadow-md shadow-amber-500/20 font-black' : 'bg-stone-950 text-stone-400 hover:text-white border border-stone-800' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Service & Fulfillment Drilldown Filters -->
            <div class="flex items-center gap-2 overflow-x-auto w-full lg:w-auto justify-start lg:justify-end">
                <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider mr-1 hidden sm:inline">Service:</span>
                <button 
                    wire:click="$set('selected_service', 'all')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $selected_service === 'all' ? 'bg-stone-800 text-white border border-stone-700 font-black' : 'bg-stone-950 text-stone-400 hover:text-white border border-stone-800' }}"
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
                            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $selected_service === $sKey ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black' : 'bg-stone-950 text-stone-400 hover:text-white border border-stone-800' }}"
                        >
                            @if ($sKey === 'thesis_binding')
                                📚 Thesis Binding
                            @elseif ($sKey === 'document_printing')
                                📄 Document Printing
                            @else
                                {{ $sConfig['name'] }}
                            @endif
                        </button>
                    @endif
                @endforeach

                <div class="h-5 w-px bg-stone-800 mx-1 hidden sm:block"></div>

                <!-- Fulfillment Mode Filter -->
                <button 
                    wire:click="$set('selected_fulfillment', 'all')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all {{ $selected_fulfillment === 'all' ? 'text-stone-200 bg-stone-800' : 'text-stone-500 hover:text-stone-300' }}"
                >
                    All Modes
                </button>
                <button 
                    wire:click="$set('selected_fulfillment', 'full_package')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all {{ $selected_fulfillment === 'full_package' ? 'text-blue-300 bg-blue-500/20 border border-blue-500/30' : 'text-stone-500 hover:text-stone-300' }}"
                >
                    📄 Full Package
                </button>
                <button 
                    wire:click="$set('selected_fulfillment', 'cover_only')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all {{ $selected_fulfillment === 'cover_only' ? 'text-purple-300 bg-purple-500/20 border border-purple-500/30' : 'text-stone-500 hover:text-stone-300' }}"
                >
                    📦 Cover Only
                </button>
            </div>
        </div>

        <!-- 4 Executive Financial KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Gross Sales Revenue -->
            <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex flex-col justify-between backdrop-blur-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Gross Sales Revenue</span>
                    <span class="size-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-black">
                        ₱
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-emerald-400 tracking-tight">
                        ₱{{ number_format($metrics['gross_revenue'], 2) }}
                    </h3>
                    <p class="text-[11px] text-stone-500 mt-1">
                        From <strong class="text-stone-300">{{ $metrics['paid_orders_count'] }} paid orders</strong> ({{ $metrics['all_orders_count'] }} total orders)
                    </p>
                </div>
            </div>

            <!-- 2. Estimated Net Profit & Margin % -->
            <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex flex-col justify-between backdrop-blur-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                        <flux:icon name="chart-pie" class="size-3.5 text-amber-400" />
                        Net Gross Profit
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        {{ $metrics['profit_margin_pct'] }}% Margin
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        ₱{{ number_format($metrics['net_profit'], 2) }}
                    </h3>
                    <p class="text-[11px] text-stone-500 mt-1">
                        Est. Material Cost: <span class="text-stone-300">₱{{ number_format($metrics['raw_material_cost'], 2) }}</span>
                    </p>
                </div>
            </div>

            <!-- 3. Average Order Value (AOV) -->
            <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex flex-col justify-between backdrop-blur-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400">Avg. Order Value (AOV)</span>
                    <span class="size-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-black">
                        🏷️
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        ₱{{ number_format($metrics['average_order_value'], 2) }}
                    </h3>
                    <p class="text-[11px] text-stone-500 mt-1">
                        Rush Fees Included: <span class="text-amber-400 font-bold">₱{{ number_format($metrics['rush_revenue'], 2) }}</span>
                    </p>
                </div>
            </div>

            <!-- 4. Material Spoilage & Waste Loss -->
            <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800/90 shadow-sm flex flex-col justify-between backdrop-blur-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-red-400 flex items-center gap-1">
                        <flux:icon name="trash" class="size-3.5 text-red-400" />
                        Spoilage / Waste Loss
                    </span>
                    <span class="size-9 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center font-black">
                        ⚠️
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl sm:text-3xl font-black text-red-400 tracking-tight">
                        -₱{{ number_format($metrics['spoilage_loss'], 2) }}
                    </h3>
                    <p class="text-[11px] text-stone-500 mt-1">
                        Pending Payment Verification: <strong class="text-amber-400">{{ $metrics['pending_payments_count'] }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Middle Section: Interactive SVG Timeline Chart + Product Mix Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left 2 Cols: Revenue & Order Volume Timeline Chart -->
            <div class="lg:col-span-2 p-6 rounded-3xl bg-stone-900/80 border border-stone-800/90 space-y-5 backdrop-blur-sm shadow-md">
                <div class="flex items-center justify-between border-b border-stone-800/80 pb-3">
                    <div>
                        <h4 class="text-base font-black text-white tracking-tight flex items-center gap-2">
                            <flux:icon name="chart-bar" class="size-4 text-amber-500" />
                            Cashflow & Sales Velocity Timeline
                        </h4>
                        <p class="text-xs text-stone-400">Daily gross revenue peaks and order intake volume</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-stone-500 block">Period Total</span>
                        <strong class="text-sm font-black text-emerald-400 font-mono">
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
                        <div class="h-56 w-full flex items-end justify-between gap-1.5 sm:gap-3 pt-6 pb-2 px-2 bg-stone-950/60 rounded-2xl border border-stone-800/60 relative overflow-hidden">
                            <!-- Background Grid Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between p-3 pointer-events-none opacity-20">
                                <div class="border-b border-stone-700 w-full"></div>
                                <div class="border-b border-stone-700 w-full"></div>
                                <div class="border-b border-stone-700 w-full"></div>
                            </div>

                            @foreach ($timeline['revenue'] as $idx => $rev)
                                @php
                                    $heightPct = min(100, max(6, ($rev / $maxVal) * 100));
                                    $ordersCount = $timeline['orders'][$idx] ?? 0;
                                    $label = $timeline['labels'][$idx] ?? '';
                                @endphp
                                <div class="flex-1 flex flex-col items-center justify-end h-full group relative z-10">
                                    <!-- Tooltip on Hover -->
                                    <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-stone-900 border border-stone-700 text-white text-[10px] font-bold py-1 px-2 rounded-lg shadow-xl pointer-events-none whitespace-nowrap z-30">
                                        <span>{{ $label }}: <strong>₱{{ number_format($rev, 2) }}</strong> ({{ $ordersCount }} orders)</span>
                                    </div>

                                    <!-- Bar Container -->
                                    <div 
                                        style="height: {{ $heightPct }}%;" 
                                        class="w-full max-w-[32px] rounded-t-lg bg-gradient-to-t {{ $rev > 0 ? 'from-amber-600/60 via-amber-500 to-yellow-400 group-hover:from-amber-500 group-hover:to-amber-300' : 'from-stone-800 to-stone-700' }} transition-all duration-300 flex items-center justify-center"
                                    ></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- X-Axis Labels -->
                        <div class="flex items-center justify-between text-[10px] font-bold text-stone-500 px-2">
                            <span>{{ $timeline['labels'][0] ?? 'Start' }}</span>
                            @if (count($timeline['labels']) > 2)
                                <span>{{ $timeline['labels'][(int)(count($timeline['labels'])/2)] ?? 'Mid' }}</span>
                            @endif
                            <span>{{ end($timeline['labels']) ?: 'End' }}</span>
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center text-stone-600">
                        <flux:icon name="chart-bar" class="size-8 mx-auto mb-2 opacity-40" />
                        <p class="text-xs">No transactions in selected period</p>
                    </div>
                @endif
            </div>

            <!-- Right 1 Col: Product Mix & Cover Color Popularity -->
            <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800/90 space-y-5 backdrop-blur-sm shadow-md">
                <div class="border-b border-stone-800/80 pb-3">
                    <h4 class="text-base font-black text-white tracking-tight flex items-center gap-2">
                        <flux:icon name="squares-2x2" class="size-4 text-purple-400" />
                        Product Mix & Fulfillment
                    </h4>
                    <p class="text-xs text-stone-400">Order breakdown by type and options</p>
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
                            <span class="text-stone-300">Fulfillment Mode</span>
                            <span class="text-stone-400 font-mono">{{ $productMix['full_package_count'] }} Full / {{ $productMix['cover_only_count'] }} Cover-Only</span>
                        </div>
                        <div class="h-3 w-full bg-stone-950 rounded-full overflow-hidden flex border border-stone-800">
                            <div style="width: {{ $fullPkgPct }}%;" class="bg-blue-500 h-full" title="Full Package"></div>
                            <div style="width: {{ $coverOnlyPct }}%;" class="bg-purple-500 h-full" title="Cover Only"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-stone-400 mt-1">
                            <span class="text-blue-400 font-semibold">&bull; Full Package ({{ $fullPkgPct }}%)</span>
                            <span class="text-purple-400 font-semibold">&bull; Cover Only ({{ $coverOnlyPct }}%)</span>
                        </div>
                    </div>

                    <!-- Hardbound vs Softbound -->
                    @php
                        $totBinding = max(1, $productMix['hardbound_count'] + $productMix['softbound_count']);
                        $hardboundPct = round(($productMix['hardbound_count'] / $totBinding) * 100);
                        $softboundPct = 100 - $hardboundPct;
                    @endphp
                    <div class="pt-2 border-t border-stone-800/80">
                        <div class="flex items-center justify-between font-bold mb-1.5">
                            <span class="text-stone-300">Binding Type</span>
                            <span class="text-stone-400 font-mono">{{ $productMix['hardbound_count'] }} Hard / {{ $productMix['softbound_count'] }} Soft</span>
                        </div>
                        <div class="h-3 w-full bg-stone-950 rounded-full overflow-hidden flex border border-stone-800">
                            <div style="width: {{ $hardboundPct }}%;" class="bg-amber-500 h-full" title="Hardbound"></div>
                            <div style="width: {{ $softboundPct }}%;" class="bg-cyan-500 h-full" title="Softbound"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-stone-400 mt-1">
                            <span class="text-amber-400 font-semibold">&bull; Hardbound ({{ $hardboundPct }}%)</span>
                            <span class="text-cyan-400 font-semibold">&bull; Softbound ({{ $softboundPct }}%)</span>
                        </div>
                    </div>

                    <!-- Top Leatherette Cover Colors Leaderboard -->
                    <div class="pt-3 border-t border-stone-800/80 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400 block">
                            Popular Leatherette Cover Colors
                        </span>
                        @forelse ($productMix['top_colors'] as $col)
                            <div class="flex items-center justify-between py-1 border-b border-stone-800/50">
                                <span class="font-bold text-stone-200">{{ $col['color'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-stone-400 font-mono text-[11px]">{{ $col['count'] }} books</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-stone-800 text-amber-400">
                                        {{ $col['percentage'] }}%
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-stone-500 text-xs italic">No color orders logged yet</p>
                        @endforelse
                    </div>

                    <!-- Service Distribution Breakdown -->
                    @if (! empty($productMix['service_breakdown']) && count($productMix['service_breakdown']) > 1)
                        <div class="pt-3 border-t border-stone-800/80 space-y-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-400 block">
                                Service Distribution
                            </span>
                            @foreach ($productMix['service_breakdown'] as $srv)
                                <div class="flex items-center justify-between py-1 border-b border-stone-800/50">
                                    <span class="font-bold text-stone-200">{{ $srv['name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-stone-400 font-mono text-[11px]">{{ $srv['count'] }} orders</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-stone-800 text-blue-400">
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
        <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800/90 space-y-4 backdrop-blur-sm shadow-md">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-stone-800/80 pb-4">
                <div>
                    <h4 class="text-base font-black text-white tracking-tight flex items-center gap-2">
                        <flux:icon name="document-text" class="size-4 text-emerald-400" />
                        Financial Transaction Ledger
                    </h4>
                    <p class="text-xs text-stone-400">Real-time breakdown of paid customer sales orders and margins</p>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <flux:icon name="magnifying-glass" class="size-3.5 text-stone-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input 
                            wire:model.live.debounce.200ms="search_query"
                            type="text" 
                            placeholder="Search Order # or Customer..."
                            class="w-full bg-stone-950 border border-stone-800 focus:border-amber-500 rounded-xl pl-9 pr-3 py-1.5 text-xs text-white placeholder-stone-500 outline-none transition-colors"
                        />
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-stone-300">
                    <thead class="bg-stone-950/80 border-b border-stone-800 text-[11px] font-bold text-stone-400 uppercase tracking-wider">
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
                    <tbody class="divide-y divide-stone-800/60 font-medium">
                        @forelse ($recentOrders as $order)
                            @php
                                $item = $order->items->first();
                                $isCoverOnly = $item?->isCoverOnly() ?? false;
                            @endphp
                            <tr class="hover:bg-stone-900 transition-colors">
                                <td class="p-3.5 font-mono font-black text-white">
                                    {{ $order->order_number }}
                                    @if ($order->is_rush)
                                        <span class="px-1 py-0.2 rounded text-[9px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 ml-1">
                                            RUSH
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-stone-400">
                                    {{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}
                                </td>
                                <td class="p-3.5">
                                    <p class="font-bold text-white">{{ $order->customer->name ?? 'Customer' }}</p>
                                    <p class="text-[10px] text-stone-500">{{ $order->customer->email ?? '' }}</p>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $isCoverOnly ? 'bg-purple-500/15 text-purple-300' : 'bg-blue-500/15 text-blue-300' }}">
                                            {{ $isCoverOnly ? 'Cover Only' : 'Full Package' }}
                                        </span>
                                        <span class="text-stone-400 text-[11px]">({{ ucfirst($item?->binding_type ?? 'Hardbound') }})</span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <span class="font-bold text-stone-200">x{{ $item?->copies_count ?? 1 }} copy</span>
                                    <span class="text-[10px] text-stone-500 block">{{ $item?->total_pages_count ?? 0 }} pages</span>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ in_array($order->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified']) ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                                            {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                                        </span>
                                        @if (! in_array($order->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified', Order::PAYMENT_REJECTED]))
                                            <button 
                                                wire:click="verifyPayment({{ $order->id }})"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-500 hover:bg-emerald-400 text-stone-950 transition-colors shadow-sm cursor-pointer"
                                                title="Confirm & Verify Payment"
                                            >
                                                ✓ Verify
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3.5 text-right font-mono font-black text-emerald-400 text-sm">
                                    ₱{{ number_format($order->total_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-stone-500">
                                    No sales transactions matching your filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer Bar -->
    <footer class="w-full bg-stone-900/60 border-t border-stone-800/80 px-4 sm:px-8 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-500 gap-2">
        <span>&copy; {{ date('Y') }} {{ $shop ? $shop->name : 'Printify' }} &bull; Sales & Financial Analytics Hub</span>
        <span class="text-stone-500 font-medium">
            Financial Ledger & Bullseye BOM Net Profit Calculation
        </span>
    </footer>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
