<?php

use App\Models\PrintShop;
use App\Models\ShopService;
use App\Services\PrintServiceCatalog;
use Livewire\Component;

new class extends Component {
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

<div class="h-screen w-screen overflow-y-auto no-scrollbar bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-between p-4 sm:px-8 sm:py-4 relative selection:bg-amber-500 selection:text-white">
    <!-- Ambient Background Glow -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $user?->printShop;
        $catalog = PrintServiceCatalog::all();

        // Installed services (always includes pre-installed core apps like web_builder)
        $dbActiveKeys = $shop ? $shop->services()->where('is_active', true)->pluck('service_key')->toArray() : [];
        $activeServiceKeys = array_values(array_unique(array_merge(PrintServiceCatalog::preinstalledKeys(), $dbActiveKeys)));

        // Filter by search query
        $installedApps = collect($catalog)->filter(function ($item) use ($activeServiceKeys) {
            return in_array($item['key'], $activeServiceKeys, true);
        })->filter(function ($item) {
            if (empty($this->search)) return true;
            return str_contains(strtolower($item['name']), strtolower($this->search))
                || str_contains(strtolower($item['description']), strtolower($this->search));
        });
    @endphp

    <!-- App Launcher Top Navigation Bar -->
    <header class="w-full max-w-6xl mx-auto flex items-center justify-between py-2 sm:py-3 border-b border-stone-800/80 relative z-20">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20">
                <x-app-logo-icon class="size-6 fill-current" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-bold text-white tracking-tight">{{ $shop ? $shop->name : config('app.name', 'Printify') }}</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">Owner Hub</span>
                </div>
                <p class="text-[11px] text-stone-400">Single Sign-On Print Operations Facility</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('owner.wizard') }}" wire:navigate class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-stone-800 bg-stone-900/60 text-xs font-semibold text-stone-300 hover:text-white hover:border-stone-700 transition-all">
                <flux:icon name="cog-6-tooth" class="size-3.5 text-stone-400" />
                Re-run Setup Wizard
            </a>

            <!-- Profile Settings Link -->
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="group flex items-center gap-2.5 bg-stone-900 border border-stone-800 hover:border-amber-500/50 hover:bg-stone-800/80 rounded-full py-1 px-3 transition-all duration-200 cursor-pointer shadow-md shadow-stone-950/50"
            >
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-6 rounded-full object-cover border border-amber-500/50 shadow-sm" />
                @else
                    <span class="size-6 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 text-xs font-extrabold flex items-center justify-center shadow-sm">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <span class="text-xs font-bold text-stone-200 group-hover:text-amber-300 hidden sm:inline transition-colors duration-200">{{ $user?->name }}</span>
                <flux:icon name="cog-6-tooth" class="size-3.5 text-stone-400 group-hover:text-amber-400 group-hover:rotate-45 transition-all duration-300" />
            </a>

            <!-- Log Out Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-flex items-center">
                @csrf
                <button
                    type="submit"
                    title="Log Out"
                    class="group flex items-center gap-1.5 bg-stone-900 border border-stone-800 hover:border-red-500/50 hover:bg-red-500/10 text-red-400 hover:text-red-300 rounded-full py-1 px-3 transition-all duration-200 cursor-pointer shadow-md shadow-stone-950/50"
                >
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5 text-red-400 group-hover:scale-110 transition-transform duration-200" />
                    <span class="text-xs font-bold hidden sm:inline">Log Out</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Main App Launcher Body -->
    <main class="w-full max-w-5xl mx-auto my-auto py-10 relative z-20 space-y-8">
        <!-- Main App Launcher Title & Stats -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                <flux:icon name="squares-2x2" class="size-3.5" />
                Printify App Launcher
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">App Launcher</h2>
            <p class="text-xs sm:text-sm text-stone-400">
                {{ count($activeServiceKeys) }} active printing applications available in your shop
            </p>
        </div>

        <!-- Center Search Filter Input Bar -->
        <div class="max-w-md mx-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-500">
                    <flux:icon name="magnifying-glass" class="size-4" />
                </div>
                <input
                    wire:model.live.debounce.150ms="search"
                    type="text"
                    placeholder="Search installed printing applications..."
                    class="w-full pl-10 pr-4 py-3 rounded-2xl bg-stone-900/80 border border-stone-800 text-stone-100 placeholder-stone-500 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all backdrop-blur-md"
                />
                @if (!empty($search))
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-500 hover:text-white text-xs">
                        Clear
                    </button>
                @endif
            </div>
        </div>

        <!-- Squircle App Launcher Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6 pt-4 max-w-4xl mx-auto">
            <!-- Installed Service App Tiles -->
            @foreach ($installedApps as $app)
                @php
                    $isPreinstalled = !empty($app['is_preinstalled']);
                    $route = match ($app['key']) {
                        'inventory_hub' => route('owner.inventory-hub'),
                        'production_hub' => route('staff.production-hub'),
                        'web_builder' => route('owner.web-builder'),
                        'thesis_binding' => route('owner.thesis-binding'),
                        default => null,
                    };
                @endphp
                @if ($route)
                    <a href="{{ $route }}" wire:navigate class="group flex flex-col items-center text-center space-y-2.5 cursor-pointer">
                @else
                    <div class="group flex flex-col items-center text-center space-y-2.5 cursor-pointer">
                @endif
                    <!-- Squircle Container -->
                    <div class="size-20 sm:size-24 rounded-3xl bg-gradient-to-br {{ $app['gradient'] }} flex items-center justify-center text-white shadow-xl shadow-stone-950/40 group-hover:scale-105 group-hover:shadow-amber-500/20 transition-all duration-300 relative border border-white/10">
                        <flux:icon name="{{ $app['icon'] }}" class="size-10 sm:size-11 stroke-[1.5]" />
                        @if ($isPreinstalled)
                            <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded-md bg-violet-400 text-stone-950 text-[9px] font-black uppercase shadow tracking-wider">CORE</span>
                        @else
                            <span class="absolute top-2 right-2 size-2.5 rounded-full bg-emerald-400 ring-2 ring-stone-950"></span>
                        @endif
                    </div>

                    <!-- App Title -->
                    <span class="text-xs sm:text-sm font-bold text-stone-200 group-hover:text-amber-400 transition-colors line-clamp-2 leading-tight px-1">
                        {{ $app['name'] }}
                    </span>
                @if ($route)
                    </a>
                @else
                    </div>
                @endif
            @endforeach

            <!-- Special "+ More Services" App Tile -->
            <div
                wire:click="openMoreServicesModal"
                class="group flex flex-col items-center text-center space-y-2.5 cursor-pointer"
            >
                <!-- Squircle Container for More Services -->
                <div class="size-20 sm:size-24 rounded-3xl bg-stone-900 border-2 border-dashed border-amber-500/40 group-hover:border-amber-500 flex items-center justify-center text-amber-400 shadow-xl shadow-stone-950/40 group-hover:scale-105 group-hover:bg-amber-500/10 transition-all duration-300 relative">
                    <flux:icon name="squares-plus" class="size-10 sm:size-11 stroke-[1.5]" />
                    <span class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full bg-amber-500 text-stone-950 text-[9px] font-extrabold">App Store</span>
                </div>

                <!-- App Title -->
                <span class="text-xs sm:text-sm font-bold text-amber-400 group-hover:text-amber-300 transition-colors leading-tight">
                    + More Services
                </span>
            </div>
        </div>

        @if ($installedApps->isEmpty() && !empty($search))
            <div class="text-center py-8 text-stone-500 text-xs">
                No installed applications found matching "{{ $search }}".
            </div>
        @endif
    </main>

    <!-- Footer Bar -->
    <footer class="w-full max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between text-xs text-stone-600 border-t border-stone-800/80 pt-4 gap-2 relative z-20">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'Printify') }} &bull; {{ $shop ? $shop->name : 'Print Shop' }}</span>
        <span class="text-stone-500 font-medium">
            {{ count($activeServiceKeys) }} installed apps &bull; {{ count($catalog) - count($activeServiceKeys) }} available in More Services
        </span>
    </footer>

    <!-- More Services Modal / App Store Drawer -->
    @if ($show_more_services_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-3xl rounded-3xl border border-stone-800 bg-stone-900 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <flux:icon name="squares-plus" class="size-6" />
                        </span>
                        <div>
                            <h3 class="text-xl font-extrabold text-white">More Services App Store</h3>
                            <p class="text-xs text-stone-400">Install or remove printing applications for {{ $shop ? $shop->name : 'your shop' }}</p>
                        </div>
                    </div>

                    <button wire:click="closeMoreServicesModal" class="size-8 rounded-full bg-stone-800 hover:bg-stone-700 text-stone-400 hover:text-white flex items-center justify-center transition-colors">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($catalog as $key => $service)
                        @php
                            $isInstalled = in_array($key, $activeServiceKeys, true);
                            $isPreinstalled = PrintServiceCatalog::isPreinstalled($key);
                        @endphp
                        <div class="flex items-start justify-between p-4 rounded-2xl border border-stone-800 bg-stone-950/60 gap-3">
                            <div class="flex items-start gap-3">
                                <div class="size-11 rounded-2xl bg-gradient-to-br {{ $service['gradient'] }} flex shrink-0 items-center justify-center text-white shadow-md">
                                    <flux:icon name="{{ $service['icon'] }}" class="size-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-white">{{ $service['name'] }}</h4>
                                        @if ($isPreinstalled)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-violet-500/20 text-violet-300 border border-violet-500/30">Pre-installed</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-stone-400 leading-relaxed">{{ $service['description'] }}</p>
                                </div>
                            </div>

                            @if ($isPreinstalled)
                                <span class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-bold bg-stone-900 border border-stone-800 text-stone-500 cursor-not-allowed select-none">
                                    Pre-installed
                                </span>
                            @else
                                <button
                                    wire:click="toggleServiceStatus('{{ $key }}')"
                                    class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $isInstalled ? 'bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20' : 'bg-amber-500 hover:bg-amber-400 text-stone-950 shadow-md shadow-amber-500/20' }}"
                                >
                                    {{ $isInstalled ? 'Remove' : 'Install' }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-stone-800 pt-4">
                    <flux:button wire:click="closeMoreServicesModal" variant="primary" class="bg-amber-500 hover:bg-amber-400 text-stone-950 font-bold px-6">
                        Done Managing Services
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
