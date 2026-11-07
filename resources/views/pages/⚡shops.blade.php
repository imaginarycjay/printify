<?php

use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public string $service = '';

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

<div class="min-h-screen bg-stone-100 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
    <x-platform-header />
    <main class="mx-auto w-full max-w-6xl space-y-8 px-4 py-8 sm:px-6 sm:py-12">
        <x-platform-feedback />
        @php
            $shops = App\Models\PrintShop::where('approval_status', 'approved')->where('is_setup_completed', true)
                ->when($this->service !== '', fn ($q) => $q->whereHas('services', fn ($q) => $q->where('service_key', $this->service)->where('is_active', true)))
                ->with(['services' => fn ($q) => $q->where('is_active', true)->whereIn('service_key', ['thesis_binding', 'document_printing'])])
                ->orderBy('name')->get();
        @endphp
        <div class="max-w-2xl space-y-3">
            <span class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-700 dark:text-amber-400"><flux:icon name="building-storefront" class="size-4" /> Your next print starts here</span>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Find your print shop</h1>
            <p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">Compare services and current workload, choose a shop, then configure your order with that shop's prices.</p>
        </div>
        <nav aria-label="Filter shops by service" class="flex flex-wrap gap-2">
            @foreach (['' => 'All services', 'thesis_binding' => 'Thesis binding', 'document_printing' => 'Document printing'] as $key => $label)
                <a href="{{ route('shops.index', $key === '' ? [] : ['service' => $key]) }}" wire:navigate @if($this->service === $key) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-bold transition-colors {{ $this->service === $key ? 'border-amber-500 bg-amber-500 text-stone-950' : 'border-stone-200 bg-white text-stone-600 hover:border-amber-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div wire:poll.30s.visible class="space-y-4">
            <p class="text-sm text-stone-500 dark:text-stone-400">{{ $shops->count() }} {{ Str::plural('shop', $shops->count()) }} available</p>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($shops as $shop)
                    <article wire:key="shop-{{ $shop->id }}" class="flex flex-col gap-5 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900/80">
                        <div class="flex items-start gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-700 dark:text-amber-400"><flux:icon name="building-storefront" class="size-6" /></span>
                            <div class="min-w-0 space-y-1">
                                <h2 class="break-words text-lg font-extrabold">{{ $shop->name }}</h2>
                                <p class="text-sm text-stone-500 dark:text-stone-400">{{ $shop->getAttribute('address') ?: 'Contact the shop for its location' }}</p>
                            </div>
                        </div>
                        <div class="flex-1 space-y-3 border-t border-stone-200 pt-4 dark:border-stone-800">
                            @forelse ($shop->services as $offering)
                                <div wire:key="service-{{ $offering->id }}" class="space-y-1.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-semibold">{{ ucwords(str_replace('_', ' ', $offering->service_key)) }}</span>
                                        <x-service-status :offering="$offering" />
                                    </div>
                                    @if ($offering->isPaused())
                                        <p class="text-xs leading-relaxed text-stone-500 dark:text-stone-400">{{ $offering->getAttribute('pause_reason') }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-stone-500">Online services are being configured.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('shops.show', $shop->id) }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-sm font-bold text-stone-950 transition-colors hover:bg-amber-400">View shop & services <flux:icon name="arrow-right" class="size-4" /></a>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-stone-300 p-10 text-center md:col-span-2 lg:col-span-3 dark:border-stone-700">
                        <flux:icon name="building-storefront" class="mx-auto mb-3 size-10 text-stone-400" />
                        <h2 class="font-bold">No shops match this service yet</h2>
                        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Try another service or check back when more shops join Printify.</p>
                        <a href="{{ route('shops.index') }}" wire:navigate class="mt-4 inline-flex min-h-11 items-center font-bold text-amber-700 dark:text-amber-400">Browse all shops &rarr;</a>
                    </div>
                @endforelse
            </div>
        </div>
        <p class="flex items-start gap-2 text-xs leading-relaxed text-stone-500 dark:text-stone-400"><flux:icon name="information-circle" class="size-4 shrink-0" /> Workload updates every 30 seconds from active jobs recorded in Printify. It is not a promised completion time. A busy shop may still accept orders.</p>
    </main>
</div>
