<?php

use App\Models\Order;
use App\Models\PrintShop;
use App\Services\PrintServiceCatalog;
use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $active_tab = 'services'; // 'services' or 'orders'
    public ?int $viewing_order_id = null;

    public function viewOrderDetails(int $id): void
    {
        $this->viewing_order_id = $id;
    }

    public function closeOrderModal(): void
    {
        $this->viewing_order_id = null;
    }
}; ?>

<div class="min-h-screen w-full bg-stone-950 text-stone-100 font-sans antialiased relative selection:bg-amber-500 selection:text-white pb-24">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = PrintShop::first();
        $orders = $user ? $user->orders()->with(['items', 'printShop'])->get() : collect();
        $activeOrdersCount = $orders->whereNotIn('order_status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])->count();
        $catalog = PrintServiceCatalog::all();
        $selectedOrder = $this->viewing_order_id ? Order::with(['items', 'printShop'])->find($this->viewing_order_id) : null;
    @endphp

    <!-- Top Navigation Header -->
    <header class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between border-b border-stone-800/80 relative z-20">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20">
                <flux:icon name="shopping-bag" class="size-6 text-stone-950" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-extrabold text-white tracking-tight">{{ $shop ? $shop->name : 'Printify' }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">Customer Portal</span>
                </div>
                <p class="text-[11px] text-stone-400">Order Online & Track Real-Time Print Production</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Profile Settings Link -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2.5 bg-stone-900 border border-stone-800 hover:border-amber-500/50 hover:bg-stone-800/80 rounded-full py-1 px-3 transition-all cursor-pointer shadow-md"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-amber-500/50" />
                @else
                    <span class="size-6 rounded-full bg-amber-500 text-stone-950 text-xs font-extrabold flex items-center justify-center">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <span class="text-xs font-bold text-stone-200 group-hover:text-amber-300 hidden sm:inline">{{ $user?->name }}</span>
                <flux:icon name="cog-6-tooth" class="size-3.5 text-stone-400 group-hover:text-amber-400 transition-all" />
            </a>

            <!-- Log Out Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                @csrf
                <button
                    type="submit"
                    title="Log Out"
                    class="group flex items-center gap-1.5 bg-stone-900 border border-stone-800 hover:border-red-500/50 hover:bg-red-500/10 text-red-400 hover:text-red-300 rounded-full py-1 px-3 transition-all cursor-pointer shadow-md"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5 text-red-400" />
                    <span class="text-xs font-bold hidden sm:inline">Log Out</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 pt-6 space-y-8 relative z-20">
        <!-- Hero Header & Tab Switcher -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-800/80 pb-4">
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight">Printing Services & Job Queue</h2>
                <p class="text-xs text-stone-400">Order thesis hardbounds, documents, and merchandise with instant online price quotations</p>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center p-1 rounded-2xl bg-stone-900 border border-stone-800 shrink-0">
                <button
                    wire:click="$set('active_tab', 'services')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $active_tab === 'services' ? 'bg-amber-500 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-400 hover:text-white' }}"
                >
                    <flux:icon name="squares-2x2" class="size-4" />
                    <span>Order Services</span>
                </button>
                <button
                    wire:click="$set('active_tab', 'orders')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $active_tab === 'orders' ? 'bg-amber-500 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-400 hover:text-white' }}"
                >
                    <flux:icon name="clock" class="size-4" />
                    <span>My Orders</span>
                    @if ($activeOrdersCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $active_tab === 'orders' ? 'bg-stone-950 text-amber-400' : 'bg-amber-500 text-stone-950' }}">
                            {{ $activeOrdersCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        <!-- TAB 1: AVAILABLE PRINTING SERVICES CATALOG -->
        @if ($active_tab === 'services')
            <div class="space-y-6 animate-fadeIn">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- 1. Hardbound & Softbound Thesis Binding (ACTIVE MVP) -->
                    <div class="p-6 rounded-3xl bg-gradient-to-b from-stone-900 to-stone-950 border-2 border-amber-500/40 hover:border-amber-500 transition-all duration-300 space-y-5 shadow-2xl flex flex-col justify-between group">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="size-12 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform">
                                    <flux:icon name="book-open" class="size-6 text-stone-950" />
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">ONLINE</span>
                            </div>

                            <div>
                                <h3 class="text-base font-black text-white group-hover:text-amber-400 transition-colors">Hardbound & Softbound Thesis Binding</h3>
                                <p class="text-xs text-stone-400 mt-1 leading-relaxed">Custom hot foil stamping, heavy duty leatherette covers, and instant manuscript page calculation.</p>
                            </div>

                            <div class="p-3 rounded-2xl bg-stone-950/80 border border-stone-800 text-xs flex items-center justify-between">
                                <span class="text-stone-400">Starting from:</span>
                                <strong class="text-amber-400 font-extrabold">₱350.00 <span class="text-[10px] font-normal text-stone-500">/ book</span></strong>
                            </div>
                        </div>

                        <a
                            href="{{ route('customer.order-thesis') }}"
                            wire:navigate
                            class="w-full py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-black uppercase tracking-wider text-center shadow-lg shadow-amber-500/20 block transition-all"
                        >
                            Order Thesis Binding &rarr;
                        </a>
                    </div>

                    <!-- 2. Document Printing -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800/90 hover:border-blue-500/50 space-y-5 shadow-xl flex flex-col justify-between group transition-all">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="size-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                                    <flux:icon name="document-text" class="size-6 text-white" />
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">ONLINE</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-white group-hover:text-blue-400 transition-colors">Document Printing & Ring Binding</h3>
                                <p class="text-xs text-stone-400 mt-1 leading-relaxed">High-speed monochrome & color PDF documents, duplex printing, sliding folders, and ring binding.</p>
                            </div>
                            <div class="p-3 rounded-2xl bg-stone-950/80 border border-stone-800 text-xs flex items-center justify-between">
                                <span class="text-stone-400">Starting from:</span>
                                <strong class="text-blue-400 font-extrabold">₱1.50 <span class="text-[10px] font-normal text-stone-500">/ page</span></strong>
                            </div>
                        </div>
                        <a
                            href="{{ route('customer.order-document') }}"
                            wire:navigate
                            class="w-full py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-wider text-center shadow-lg shadow-blue-600/20 block transition-all"
                        >
                            Order Document Printing &rarr;
                        </a>
                    </div>

                    <!-- 3. Tarpaulin Printing -->
                    <div class="p-6 rounded-3xl bg-stone-900/60 border border-stone-800 space-y-5 shadow-xl flex flex-col justify-between opacity-80 hover:opacity-100 transition-opacity">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="size-12 rounded-2xl bg-gradient-to-br from-purple-500 to-fuchsia-600 text-white flex items-center justify-center shadow-lg">
                                    <flux:icon name="photo" class="size-6" />
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-stone-800 text-stone-400">Available In Shop</span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-white">Tarpaulin Banner Printing</h3>
                                <p class="text-xs text-stone-400 mt-1 leading-relaxed">High-resolution outdoor tarpaulin banners with reinforced eyelets.</p>
                            </div>
                            <div class="p-3 rounded-2xl bg-stone-950 border border-stone-800 text-xs flex items-center justify-between">
                                <span class="text-stone-400">Price rate:</span>
                                <strong class="text-white">₱18.00 <span class="text-[10px] font-normal text-stone-500">/ sq.ft</span></strong>
                            </div>
                        </div>
                        <button disabled class="w-full py-3 rounded-2xl bg-stone-800 text-stone-500 text-xs font-bold text-center cursor-not-allowed">
                            Order via Counter
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: MY ORDERS & REAL-TIME PROGRESS TRACKER -->
        @if ($active_tab === 'orders')
            <div class="space-y-6 animate-fadeIn">
                @if ($orders->isEmpty())
                    <div class="p-12 text-center text-stone-500 border border-dashed border-stone-800 rounded-3xl space-y-3 bg-stone-900/40">
                        <flux:icon name="shopping-bag" class="size-12 mx-auto text-stone-600" />
                        <div class="text-sm font-bold text-stone-300">You haven't placed any printing orders yet</div>
                        <p class="text-xs text-stone-500">Order thesis hardbounds online to track real-time progress right from your screen.</p>
                        <a href="{{ route('customer.order-thesis') }}" wire:navigate class="inline-block px-5 py-2.5 rounded-xl bg-amber-500 text-stone-950 text-xs font-black mt-2">
                            Place Your First Order &rarr;
                        </a>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($orders as $order)
                            @php
                                $stage = $order->currentStageIndex();
                            @endphp
                            <div class="p-6 sm:p-8 rounded-3xl bg-stone-900/90 border border-stone-800 space-y-6 shadow-2xl">
                                <!-- Order Header Info -->
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-stone-800 pb-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono font-black text-amber-400 text-sm sm:text-base">{{ $order->order_number }}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $order->statusBadgeColor() }}">
                                                {{ $order->order_status === \App\Models\Order::STATUS_COMPLETED || $order->production_stage === \App\Models\Order::STAGE_COMPLETED ? 'Completed / Received' : ucfirst(str_replace('_', ' ', $order->order_status)) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $order->paymentStatusBadgeColor() }}">
                                                {{ $order->paymentStatusLabel() }}
                                            </span>
                                            @php
                                                $firstItem = $order->items->first();
                                            @endphp
                                            @if ($order->service_key === 'document_printing' || ($firstItem && $firstItem->isDocumentPrinting()))
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                                    📄 Document Print
                                                </span>
                                            @elseif ($firstItem && $firstItem->isCoverOnly())
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                    📦 Cover Only
                                                </span>
                                                @if (! $firstItem->is_paper_received)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 animate-pulse">
                                                        Paper Drop-off Needed
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        Paper In Shop
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-stone-400 mt-1">
                                            Placed on {{ $order->created_at->format('M d, Y • h:i A') }} &bull; Promised Ready: <strong class="text-stone-200">{{ $order->target_completion_date ? $order->target_completion_date->format('M d, Y') : 'N/A' }}</strong>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <span class="text-[10px] text-stone-500 block">{{ $order->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID ? 'Total Paid' : 'Amount Due' }}</span>
                                            <span class="text-lg font-black text-white">₱{{ number_format($order->total_amount, 2) }}</span>
                                        </div>
                                        <button
                                            wire:click="viewOrderDetails({{ $order->id }})"
                                            class="px-3.5 py-2 rounded-xl bg-stone-800 hover:bg-stone-700 text-stone-200 text-xs font-bold transition-all cursor-pointer"
                                        >
                                            View Receipt
                                        </button>
                                    </div>
                                </div>

                                <!-- 5-Stage Visual Progress Stepper -->
                                <div class="space-y-2 pt-2">
                                    <div class="text-xs font-bold text-stone-300">Live Production Progress</div>
                                    <div class="grid grid-cols-5 gap-1 sm:gap-4 relative pt-2">
                                        <!-- Step 1 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-black mx-auto {{ $stage >= 1 ? 'bg-emerald-500 text-stone-950 ring-2 sm:ring-4 ring-emerald-500/20' : 'bg-stone-800 text-stone-500' }}">1</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 1 ? 'text-white' : 'text-stone-500' }} block leading-tight">Order Placed</span>
                                        </div>

                                        <!-- Step 2 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-black mx-auto {{ $stage >= 2 ? 'bg-emerald-500 text-stone-950 ring-2 sm:ring-4 ring-emerald-500/20' : 'bg-stone-800 text-stone-500' }}">2</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 2 ? 'text-white' : 'text-stone-500' }} block leading-tight">
                                                @if ($order->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID)
                                                    Paid & Queued
                                                @elseif ($order->payment_status === \App\Models\Order::PAYMENT_UNPAID)
                                                    Queued (Counter Pay)
                                                @else
                                                    Queued (Reviewing)
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Step 3 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-black mx-auto {{ $stage >= 3 ? 'bg-amber-500 text-stone-950 ring-2 sm:ring-4 ring-amber-500/20 animate-pulse' : 'bg-stone-800 text-stone-500' }}">3</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 3 ? 'text-amber-400' : 'text-stone-500' }} block leading-tight">Production</span>
                                        </div>

                                        <!-- Step 4 -->
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-black mx-auto {{ $stage >= 4 ? 'bg-emerald-500 text-stone-950' : 'bg-stone-800 text-stone-500' }}">4</span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 4 ? 'text-white' : 'text-stone-500' }} block leading-tight">Quality Check</span>
                                        </div>

                                        <!-- Step 5 -->
                                        @php
                                            $isOrderCompleted = $order->order_status === \App\Models\Order::STATUS_COMPLETED || $order->production_stage === \App\Models\Order::STAGE_COMPLETED;
                                        @endphp
                                        <div class="text-center space-y-1">
                                            <span class="size-6 sm:size-7 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-black mx-auto {{ $stage >= 5 ? ($isOrderCompleted ? 'bg-emerald-500 text-stone-950 shadow-lg shadow-emerald-500/40' : 'bg-amber-500 text-stone-950 ring-2 sm:ring-4 ring-amber-500/20 animate-pulse') : 'bg-stone-800 text-stone-500' }}">
                                                {{ $isOrderCompleted ? '✓' : '5' }}
                                            </span>
                                            <span class="text-[8px] sm:text-[10px] font-bold {{ $stage >= 5 ? ($isOrderCompleted ? 'text-emerald-400 font-extrabold' : 'text-amber-400 font-extrabold') : 'text-stone-500' }} block leading-tight">
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
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-lg rounded-3xl border border-stone-800 bg-stone-900 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                    <div>
                        <span class="text-[10px] font-bold text-amber-400 uppercase">Official Order Receipt</span>
                        <h3 class="text-lg font-black text-white font-mono">{{ $selectedOrder->order_number }}</h3>
                    </div>
                    <button wire:click="closeOrderModal" class="text-stone-400 hover:text-white text-lg">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Shop & Customer Details -->
                    <div class="p-3.5 rounded-2xl bg-stone-950 border border-stone-800 space-y-1">
                        <div class="flex justify-between">
                            <span class="text-stone-500">Print Shop:</span>
                            <strong class="text-white">{{ $selectedOrder->printShop?->name }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500">Customer:</span>
                            <strong class="text-white">{{ $user?->name }} ({{ $user?->email }})</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500">Payment Status:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $selectedOrder->paymentStatusBadgeColor() }}">
                                {{ $selectedOrder->paymentStatusLabel() }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500">Payment Reference:</span>
                            <strong class="text-amber-400 font-mono">{{ $selectedOrder->payment_reference_no ?? 'N/A' }}</strong>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-2">
                        <span class="font-bold text-stone-300 block">Item Specifications</span>
                        @foreach ($selectedOrder->items as $item)
                            <div class="p-3.5 rounded-2xl bg-stone-950 border border-stone-800 space-y-2">
                                <div class="flex justify-between font-bold text-white">
                                    <span>
                                        @if ($selectedOrder->service_key === 'document_printing' || $item->isDocumentPrinting())
                                            📄 Document Printing ({{ $item->getFinishingLabel() }})
                                        @else
                                            {{ ucfirst($item->binding_type) }}
                                            @if ($item->isCoverOnly())
                                                <span class="text-amber-400 text-[10px] font-semibold">(Cover & Binding Only)</span>
                                            @else
                                                Thesis
                                            @endif
                                        @endif
                                        ({{ $item->copies_count }} {{ $item->copies_count > 1 ? 'copies' : 'copy' }})
                                    </span>
                                    <span>₱{{ number_format($item->total_price, 2) }}</span>
                                </div>
                                <div class="text-[11px] text-stone-400 space-y-0.5">
                                    @if ($selectedOrder->service_key === 'document_printing' || $item->isDocumentPrinting())
                                        <div>Pages: {{ $item->bw_pages_count }} B&W + {{ $item->color_pages_count }} Color &bull; {{ $item->paper_size }} ({{ $item->isDuplex() ? 'Duplex' : 'Simplex' }})</div>
                                        <div>Paper Feed: <strong class="text-blue-400">{{ $item->getPhysicalSheetsCount() }} sheets</strong> per copy</div>
                                        @if ($item->cover_color && $item->getFinishingType() === 'ring_bind')
                                            <div>Back Cover Board: {{ $item->cover_color }}</div>
                                        @endif
                                    @elseif ($item->isCoverOnly())
                                        <div>Pre-Printed Pages: {{ $item->total_pages_count }} pages &bull; Est. Spine: ~{{ $item->estimated_spine_thickness_mm ?? '10.0' }} mm</div>
                                        <div>Paper Status: <span class="{{ $item->is_paper_received ? 'text-emerald-400' : 'text-amber-400' }} font-bold">{{ $item->is_paper_received ? 'Received at Shop' : 'Pending Customer Drop-off' }}</span></div>
                                        <div>Cover: {{ $item->cover_color }} &bull; Foil: {{ $item->foil_color ?? 'None' }}</div>
                                    @else
                                        <div>Pages: {{ $item->bw_pages_count }} B&W + {{ $item->color_pages_count }} Color ({{ $item->paper_size }})</div>
                                        <div>Cover: {{ $item->cover_color }} &bull; Foil: {{ $item->foil_color ?? 'None' }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Total Amount / Paid -->
                    <div class="flex justify-between items-center p-4 rounded-2xl bg-stone-950 border border-amber-500/30 text-sm font-extrabold text-white">
                        <span>{{ $selectedOrder->payment_status === \App\Models\Order::PAYMENT_VERIFIED_PAID ? 'Grand Total Paid:' : 'Total Amount Due:' }}</span>
                        <span class="text-lg text-amber-400">₱{{ number_format($selectedOrder->total_amount, 2) }}</span>
                    </div>
                </div>

                <div class="pt-2">
                    <button wire:click="closeOrderModal" class="w-full py-2.5 rounded-xl bg-stone-800 hover:bg-stone-700 text-stone-200 text-xs font-bold cursor-pointer">
                        Close Receipt
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
