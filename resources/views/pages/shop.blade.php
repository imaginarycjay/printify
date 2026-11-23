<?php

use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public int $shopId;

    public function mount(int $shop): void
    {
        $this->shopId = $shop;
    }

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

@php
    $shop = App\Models\PrintShop::where('approval_status', 'approved')->where('is_setup_completed', true)
        ->with(['services', 'thesisBindingConfig'])->findOrFail($this->shopId);
    $documentConfig = App\Models\DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();
@endphp

<div class="min-h-screen bg-slate-100 text-slate-900 dark:bg-[#0b1120] dark:text-slate-100">
    <x-platform-header title="{{ $shop->name }}" subtitle="Shop directory &amp; services" :back-url="route('shops.index')" back-label="Find Shops" />
    <main class="mx-auto w-full max-w-6xl space-y-8 px-4 py-8 sm:px-6">
        <x-platform-feedback />
        <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400"><flux:icon name="building-storefront" class="size-4" /> Print shop</span>
                @php
                    $shopWorkload = $shop->workloadStatus();
                    $shopWorkloadColor = match ($shopWorkload) {
                        'Busy' => 'amber',
                        'At capacity' => 'orange',
                        default => 'emerald',
                    };
                @endphp
                <flux:badge :color="$shopWorkloadColor" size="sm" icon="signal">{{ $shopWorkload }}</flux:badge>
            </div>
            <h1 class="break-words text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $shop->name }}</h1>
            <p class="max-w-2xl whitespace-pre-line text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $shop->getAttribute('description') ?: 'Choose a service below to customize your printing order.' }}</p>
            <div class="flex flex-wrap gap-x-6 gap-y-3 border-t border-slate-200 pt-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
                <span class="flex items-start gap-2"><flux:icon name="map-pin" class="size-4 shrink-0" /> {{ $shop->getAttribute('address') ?: 'Location not provided' }}</span>
                @if ($shop->getAttribute('contact'))
                    <span class="flex items-start gap-2"><flux:icon name="phone" class="size-4 shrink-0" /> {{ $shop->getAttribute('contact') }}</span>
                @endif
                @if ($shop->operating_hours)
                    <span class="flex items-start gap-2"><flux:icon name="clock" class="size-4 shrink-0" /> {{ $shop->operating_hours }}</span>
                @endif
            </div>
        </section>
        <section wire:poll.30s.visible class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><h2 class="text-xl font-extrabold">What would you like to print?</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Prices and availability below belong to {{ $shop->name }}.</p></div>
                @if ((! auth()->check() || auth()->user()->isCustomer()) && $shop->services->where('is_active', true)->whereIn('service_key', ['thesis_binding', 'document_printing'])->isNotEmpty())
                    <a href="{{ route('customer.shop.checkout', $shop->id) }}" data-theme-keep class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white shadow-xs transition-colors hover:bg-slate-800 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-500">
                        <flux:icon name="shopping-cart" class="size-4" />
                        Multi-Item Cart &amp; Checkout
                    </a>
                @endif
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                @forelse ($shop->services->where('is_active', true)->whereIn('service_key', ['thesis_binding', 'document_printing']) as $offering)
                    @php
                        $isThesis = $offering->service_key === 'thesis_binding';
                        $config = $isThesis ? $shop->thesisBindingConfig : $documentConfig;
                    @endphp
                    <article wire:key="offering-{{ $offering->id }}" class="flex flex-col gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex size-12 items-center justify-center rounded-2xl border {{ $isThesis ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300' : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300' }}"><flux:icon :name="$isThesis ? 'book-open' : 'document-text'" class="size-6" /></span>
                            <x-service-status :offering="$offering" />
                        </div>
                        <div class="flex-1 space-y-2">
                            <h3 class="text-lg font-extrabold">{{ $isThesis ? 'Thesis binding' : 'Document printing' }}</h3>
                            <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $isThesis ? 'Customize your binding, cover, foil and manuscript specifications.' : 'Choose your paper, color mode, copies and finishing options.' }}</p>
                            @if ($config)
                                <p class="pt-2 text-sm text-slate-500 dark:text-slate-400">{{ $isThesis ? 'Hardbound from' : 'A4 black & white from' }} <strong class="text-lg tabular-nums text-slate-900 dark:text-slate-100">₱{{ number_format((float) ($isThesis ? $config->hardbound_base_price : $config->getAttribute('page_price_bw_a4')), 2) }}</strong> {{ $isThesis ? '/ book' : '/ page' }}</p>
                            @endif
                        </div>
                        @if ($offering->isPaused())
                            <div class="rounded-2xl border border-slate-200 bg-slate-100 p-4 text-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="font-bold">Temporarily unavailable</p>
                                <p class="mt-1 break-words text-slate-600 dark:text-slate-400">{{ $offering->getAttribute('pause_reason') }}</p>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Scheduled to resume {{ $offering->getAttribute('paused_until')->format('M j, Y · g:i A') }} ({{ config('app.timezone') }}).</p>
                            </div>
                        @elseif (! auth()->check() || auth()->user()->isCustomer())
                            <a href="{{ route($isThesis ? 'customer.shop.order-thesis' : 'customer.shop.order-document', $shop->id) }}" data-theme-keep class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold text-white transition-colors {{ $isThesis ? 'bg-indigo-600 hover:bg-indigo-700 dark:hover:bg-indigo-500' : 'bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500' }}">Configure order <flux:icon name="arrow-right" class="size-4" /></a>
                        @else
                            <p class="text-sm text-slate-500 dark:text-slate-400">Orders are placed using a customer account.</p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-3xl border border-dashed border-slate-300 p-8 text-sm text-slate-500 md:col-span-2 dark:border-slate-700">This shop is still preparing its online services. Contact the shop for assistance.</p>
                @endforelse
            </div>
            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Final prices depend on your specifications. Workload reflects active jobs recorded in PressQueue, not an estimated completion time. Busy and at-capacity services may still accept orders unless paused.</p>
        </section>
    </main>
</div>
