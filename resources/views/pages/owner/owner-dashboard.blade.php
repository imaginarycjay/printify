<?php

use App\Models\PrintShop;
use App\Models\ShopService;
use App\Services\PrintServiceCatalog;
use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $search = '';

    public bool $show_more_services_modal = false;

    public function toggleServiceStatus(string $serviceKey): void
    {
        if (PrintServiceCatalog::isPreinstalled($serviceKey)) {
            $this->dispatch('toast', message: 'Pre-installed system apps cannot be removed.');
            return;
        }

        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var PrintShop $shop */
        $shop = $user->printShop;

        $existing = ShopService::where('print_shop_id', $shop->id)
            ->where('service_key', $serviceKey)
            ->first();

        if ($existing) {
            $existing->update(['is_active' => ! $existing->is_active]);
        } else {
            ShopService::create([
                'print_shop_id' => $shop->id,
                'service_key' => $serviceKey,
                'is_active' => true,
            ]);
        }

        $this->dispatch('toast', message: 'Services updated successfully.');
    }

    public function openMoreServicesModal(): void
    {
        $this->show_more_services_modal = true;
    }

    public function closeMoreServicesModal(): void
    {
        $this->show_more_services_modal = false;
    }
}; ?>

<div class="pq-page flex flex-col justify-between p-4 sm:px-8 sm:py-5">
    @php
        $user = auth()->user();
        $shop = $user?->printShop;
        $catalog = PrintServiceCatalog::all();

        // Installed services (always includes pre-installed core apps)
        $dbActiveKeys = $shop ? $shop->services()->where('is_active', true)->pluck('service_key')->toArray() : [];
        $activeServiceKeys = array_values(array_unique(array_merge(PrintServiceCatalog::preinstalledKeys(), $dbActiveKeys)));

        // Filter by search query
        $installedApps = collect($catalog)->filter(function ($item) use ($activeServiceKeys) {
            return in_array($item['key'], $activeServiceKeys, true);
        })->filter(function ($item) {
            if (empty($this->search)) {
                return true;
            }

            return str_contains(strtolower($item['name']), strtolower($this->search))
                || str_contains(strtolower($item['description']), strtolower($this->search));
        });
    @endphp

    <!-- App Launcher Top Navigation Bar -->
    <header class="w-full max-w-6xl mx-auto flex flex-wrap gap-4 items-center justify-between py-3 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <span data-theme-keep class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-800">
                <x-app-logo-icon class="size-6" />
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="truncate text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">{{ $shop ? $shop->name : config('app.name', 'PressQueue') }}</h1>
                    <span class="pq-badge-brand">Owner</span>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400">Shop workspace &amp; applications</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($shop)
                <a href="{{ route('owner.platform') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-blue-600 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-400 dark:hover:text-white transition-colors">
                    <flux:icon name="cog-6-tooth" class="size-4" />
                    Shop Settings
                </a>
            @endif
            <a href="{{ route('owner.wizard') }}" wire:navigate class="hidden sm:inline-flex min-h-10 items-center gap-1.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:border-blue-600 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-400 dark:hover:text-white transition-colors">
                <flux:icon name="adjustments-horizontal" class="size-3.5 text-slate-500 dark:text-slate-400" />
                Re-run Setup Wizard
            </a>
            <livewire:notification-bell />
            <x-theme-toggle />

            <!-- Profile Settings Link -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-600 dark:hover:border-blue-400 rounded-full py-1 px-3 transition-colors cursor-pointer"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-blue-600/40" />
                @else
                    <span data-theme-keep class="size-6 rounded-full bg-slate-900 text-white dark:bg-blue-600 text-xs font-extrabold flex items-center justify-center">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-400 hidden sm:inline">{{ $user?->name }}</span>
                <flux:icon name="cog-6-tooth" class="size-3.5 text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" />
            </a>

            <!-- Log Out Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                @csrf
                <button
                    type="submit"
                    title="Log Out"
                    class="inline-flex min-h-10 items-center gap-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-rose-300 hover:bg-rose-50 text-rose-700 dark:hover:bg-rose-950/60 dark:text-rose-300 rounded-xl px-3 transition-colors cursor-pointer"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                    <span class="text-xs font-bold hidden sm:inline">Log Out</span>
                </button>
            </form>
        </div>
    </header>

    @if ($shop && $shop->getAttribute('approval_status') !== 'approved')
        <aside class="mx-auto mt-4 flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-800 dark:bg-amber-950/50">
            <p class="text-amber-900 dark:text-amber-200"><strong>{{ match ($shop->getAttribute('approval_status')) { 'rejected' => 'Application needs changes.', 'suspended' => 'Shop suspended.', default => 'Shop approval pending.' } }}</strong> Your shop is hidden from customers until approved.</p>
            <a href="{{ route('owner.platform') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 font-bold text-amber-800 hover:underline dark:text-amber-300">Review shop profile <flux:icon name="arrow-right" class="size-4" /></a>
        </aside>
    @endif

    <!-- Main App Launcher Body -->
    <main class="w-full max-w-5xl mx-auto my-auto py-8 sm:py-10 space-y-8">
        <!-- Main App Launcher Title & Stats -->
        <div class="text-center space-y-3">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">App Launcher</h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                {{ count($activeServiceKeys) }} active printing applications available in your shop
            </p>

            <!-- Quick Financial Analytics Strip -->
            @php
                $todaySales = \App\Models\Order::where('print_shop_id', $shop->id ?? 0)
                    ->whereDate('created_at', \Illuminate\Support\Carbon::today())
                    ->whereNotIn('payment_status', [\App\Models\Order::PAYMENT_REJECTED, 'cancelled'])
                    ->sum('total_amount');
                $pendingOrders = \App\Models\Order::where('print_shop_id', $shop->id ?? 0)
                    ->whereNotIn('order_status', [\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_CANCELLED])
                    ->count();
            @endphp
            <div class="pt-1">
                <a
                    href="{{ route('owner.analytics-hub') }}"
                    wire:navigate
                    class="inline-flex flex-wrap items-center justify-center gap-3 px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-600 dark:hover:border-blue-400 transition-colors text-xs text-slate-700 dark:text-slate-300 shadow-xs group"
                >
                    <span class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-bold tabular-nums">
                        <span class="size-2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                        Today: ₱{{ number_format($todaySales, 2) }}
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                    <span class="text-slate-600 dark:text-slate-400 font-medium tabular-nums">{{ $pendingOrders }} active floor {{ Str::plural('job', $pendingOrders) }}</span>
                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                    <span class="text-blue-700 dark:text-blue-400 font-bold group-hover:underline flex items-center gap-1">
                        Analytics Hub &rarr;
                    </span>
                </a>
            </div>
        </div>

        <!-- Center Search Filter Input Bar -->
        <div class="max-w-md mx-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <flux:icon name="magnifying-glass" class="size-4" />
                </div>
                <input
                    wire:model.live.debounce.150ms="search"
                    type="text"
                    placeholder="Search installed printing applications..."
                    class="w-full pl-10 pr-16 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                />
                @if (!empty($search))
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-900 dark:hover:text-white text-xs font-semibold">
                        Clear
                    </button>
                @endif
            </div>
        </div>

        <!-- Editorial Module Card Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2 max-w-5xl mx-auto">
            @foreach ($installedApps as $app)
                @php
                    $isPreinstalled = !empty($app['is_preinstalled']);
                    $route = match ($app['key']) {
                        'analytics_hub' => route('owner.analytics-hub'),
                        'inventory_hub' => route('owner.inventory-hub'),
                        'production_hub' => route('staff.production-hub'),
                        'thesis_binding' => route('owner.thesis-binding'),
                        'document_printing' => route('owner.document-printing'),
                        default => null,
                    };
                @endphp
                @if ($route)
                    <a href="{{ $route }}" wire:navigate class="pq-card group flex flex-col justify-between p-5 text-left transition-colors hover:border-blue-600 dark:hover:border-blue-400 cursor-pointer">
                @else
                    <div class="pq-card group flex flex-col justify-between p-5 text-left">
                @endif
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border {{ $app['badge_color'] }}">
                            <flux:icon name="{{ $app['icon'] }}" class="size-5" />
                        </span>
                        @if ($isPreinstalled)
                            <span class="pq-badge-neutral">Core</span>
                        @else
                            <span class="pq-badge-success">Active</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-1">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">
                            {{ $app['name'] }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                            {{ $app['description'] }}
                        </p>
                    </div>
                @if ($route)
                    </a>
                @else
                    </div>
                @endif
            @endforeach

            <!-- Special "+ More Services" App Tile -->
            <button
                type="button"
                wire:click="openMoreServicesModal"
                class="group flex flex-col justify-between p-5 text-left rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 hover:border-blue-600 hover:bg-white dark:hover:border-blue-400 dark:hover:bg-slate-900 transition-colors cursor-pointer"
            >
                <div class="flex items-start justify-between gap-3 w-full">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                        <flux:icon name="squares-plus" class="size-5" />
                    </span>
                    <span class="pq-badge-brand">App Store</span>
                </div>

                <div class="mt-4 space-y-1">
                    <h3 class="text-sm font-extrabold text-blue-700 dark:text-blue-400">
                        + More Services
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Install or remove optional printing modules for your shop.
                    </p>
                </div>
            </button>
        </div>

        @if ($installedApps->isEmpty() && !empty($search))
            <div class="text-center py-8 text-slate-500 dark:text-slate-400 text-xs">
                No installed applications found matching "{{ $search }}".
            </div>
        @endif
    </main>

    <!-- Footer Bar -->
    <footer class="w-full max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-800 pt-4 gap-2">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'PressQueue') }} &bull; {{ $shop ? $shop->name : 'Print Shop' }}</span>
        <span class="font-medium tabular-nums">
            {{ count($activeServiceKeys) }} installed {{ Str::plural('app', count($activeServiceKeys)) }} &bull; {{ count($catalog) - count($activeServiceKeys) }} available in More Services
        </span>
    </footer>

    <!-- More Services Modal / App Store Drawer -->
    @if ($show_more_services_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
            <div class="w-full max-w-3xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-7 shadow-xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">
                            <flux:icon name="squares-plus" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 dark:text-slate-100">More Services App Store</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Install or remove printing applications for {{ $shop ? $shop->name : 'your shop' }}</p>
                        </div>
                    </div>

                    <button type="button" wire:click="closeMoreServicesModal" aria-label="Close modal" class="size-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center transition-colors cursor-pointer">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @foreach ($catalog as $key => $service)
                        @php
                            $isInstalled = in_array($key, $activeServiceKeys, true);
                            $isPreinstalled = PrintServiceCatalog::isPreinstalled($key);
                        @endphp
                        <div class="flex items-start justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="size-10 rounded-xl border {{ $service['badge_color'] }} flex shrink-0 items-center justify-center">
                                    <flux:icon name="{{ $service['icon'] }}" class="size-5" />
                                </div>
                                <div class="space-y-1 min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ $service['name'] }}</h4>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">{{ $service['description'] }}</p>
                                </div>
                            </div>

                            @if ($isPreinstalled)
                                <span class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 select-none">
                                    Pre-installed
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="toggleServiceStatus('{{ $key }}')"
                                    class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer {{ $isInstalled ? 'bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60 dark:border-rose-800 dark:text-rose-300' : 'bg-blue-600 hover:bg-blue-700 text-white dark:hover:bg-blue-500' }}"
                                >
                                    {{ $isInstalled ? 'Remove' : 'Install' }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-slate-200 dark:border-slate-800 pt-4">
                    <flux:button wire:click="closeMoreServicesModal" variant="primary" class="!bg-blue-600 hover:!bg-blue-700 !text-white font-bold px-6 dark:hover:!bg-blue-500">
                        Done Managing Services
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
