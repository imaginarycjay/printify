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

<div class="min-h-screen bg-stone-100 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
    <x-platform-header />
    <main class="mx-auto w-full max-w-6xl space-y-8 px-4 py-8 sm:px-6">
        <x-platform-feedback />
        @php
            $shop = App\Models\PrintShop::where('approval_status', 'approved')->where('is_setup_completed', true)
                ->with(['services', 'thesisBindingConfig'])->findOrFail($this->shopId);
            $documentConfig = App\Models\DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();
        @endphp
        <a href="{{ route('shops.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-stone-500 hover:text-amber-700 dark:text-stone-400 dark:hover:text-amber-400"><flux:icon name="arrow-left" class="size-4" /> Back to shops</a>
        <section class="space-y-4 rounded-3xl border border-stone-200 bg-white p-6 sm:p-8 dark:border-stone-800 dark:bg-stone-900/80">
            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"><flux:icon name="building-storefront" class="size-4" /> Print shop</span>
            <h1 class="break-words text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $shop->name }}</h1>
            <p class="max-w-2xl whitespace-pre-line text-sm leading-relaxed text-stone-600 dark:text-stone-400">{{ $shop->getAttribute('description') ?: 'Choose a service below to customize your printing order.' }}</p>
            <div class="flex flex-wrap gap-x-6 gap-y-3 border-t border-stone-200 pt-4 text-sm text-stone-600 dark:border-stone-800 dark:text-stone-400">
                <span class="flex items-start gap-2"><flux:icon name="map-pin" class="size-4 shrink-0" /> {{ $shop->getAttribute('address') ?: 'Location not provided' }}</span>
                @if ($shop->getAttribute('contact'))
                    <span class="flex items-start gap-2"><flux:icon name="phone" class="size-4 shrink-0" /> {{ $shop->getAttribute('contact') }}</span>
                @endif
            </div>
        </section>
        <section wire:poll.30s.visible class="space-y-5">
            <div><h2 class="text-xl font-extrabold">What would you like to print?</h2><p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Prices and availability below belong to {{ $shop->name }}.</p></div>
            <div class="grid gap-5 md:grid-cols-2">
                @forelse ($shop->services->where('is_active', true)->whereIn('service_key', ['thesis_binding', 'document_printing']) as $offering)
                    @php
                        $isThesis = $offering->service_key === 'thesis_binding';
                        $config = $isThesis ? $shop->thesisBindingConfig : $documentConfig;
                    @endphp
                    <article wire:key="offering-{{ $offering->id }}" class="flex flex-col gap-5 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900/80">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex size-12 items-center justify-center rounded-2xl {{ $isThesis ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400' : 'bg-blue-500/10 text-blue-700 dark:text-blue-400' }}"><flux:icon :name="$isThesis ? 'book-open' : 'document-text'" class="size-6" /></span>
                            <x-service-status :offering="$offering" />
                        </div>
                        <div class="flex-1 space-y-2">
                            <h3 class="text-lg font-extrabold">{{ $isThesis ? 'Thesis binding' : 'Document printing' }}</h3>
                            <p class="text-sm leading-relaxed text-stone-500 dark:text-stone-400">{{ $isThesis ? 'Customize your binding, cover, foil and manuscript specifications.' : 'Choose your paper, color mode, copies and finishing options.' }}</p>
                            @if ($config)
                                <p class="pt-2 text-sm text-stone-500 dark:text-stone-400">{{ $isThesis ? 'Hardbound from' : 'A4 black & white from' }} <strong class="text-lg text-stone-900 dark:text-stone-100">₱{{ number_format((float) ($isThesis ? $config->hardbound_base_price : $config->getAttribute('page_price_bw_a4')), 2) }}</strong> {{ $isThesis ? '/ book' : '/ page' }}</p>
                            @endif
                        </div>
                        @if ($offering->isPaused())
                            <div class="rounded-2xl border border-stone-200 bg-stone-100 p-4 text-sm dark:border-stone-700 dark:bg-stone-800">
                                <p class="font-bold">Temporarily unavailable</p>
                                <p class="mt-1 break-words text-stone-600 dark:text-stone-400">{{ $offering->getAttribute('pause_reason') }}</p>
                                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Scheduled to resume {{ $offering->getAttribute('paused_until')->format('M j, Y · g:i A') }} ({{ config('app.timezone') }}).</p>
                            </div>
                        @elseif (! auth()->check() || auth()->user()->isCustomer())
                            <a href="{{ route($isThesis ? 'customer.shop.order-thesis' : 'customer.shop.order-document', $shop->id) }}" data-theme-keep class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold transition-colors {{ $isThesis ? 'bg-amber-500 text-stone-950 hover:bg-amber-400' : 'bg-blue-600 text-white hover:bg-blue-500' }}">Configure order <flux:icon name="arrow-right" class="size-4" /></a>
                        @else
                            <p class="text-sm text-stone-500 dark:text-stone-400">Orders are placed using a customer account.</p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-3xl border border-dashed border-stone-300 p-8 text-sm text-stone-500 md:col-span-2 dark:border-stone-700">This shop is still preparing its online services. Contact the shop for assistance.</p>
                @endforelse
            </div>
            <p class="text-xs leading-relaxed text-stone-500 dark:text-stone-400">Final prices depend on your specifications. Workload reflects active jobs recorded in Printify, not an estimated completion time. Busy and at-capacity services may still accept orders unless paused.</p>
        </section>
    </main>
</div>
