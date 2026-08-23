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

    public int $step = 1;

    public string $shop_name = '';

    /** @var array<int, string> */
    public array $selected_services = [];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->printShop) {
            $this->shop_name = $user->printShop->name;
            $this->selected_services = $user->printShop->services()
                ->where('is_active', true)
                ->pluck('service_key')
                ->toArray();
        }

        if (empty($this->selected_services)) {
            // Default select top 4 services
            $this->selected_services = ['thesis_binding', 'document_printing', 'tarpaulin', 'pvc_id'];
        }
    }

    public function nextStep(): void
    {
        $this->validate([
            'shop_name' => 'required|string|min:3|max:100',
        ], [
            'shop_name.required' => 'Please enter a name for your print shop.',
            'shop_name.min' => 'Print shop name must be at least 3 characters.',
        ]);

        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    public function toggleService(string $key): void
    {
        if (PrintServiceCatalog::isPreinstalled($key)) {
            return;
        }

        if (in_array($key, $this->selected_services, true)) {
            $this->selected_services = array_values(array_filter(
                $this->selected_services,
                fn ($s) => $s !== $key
            ));
        } else {
            $this->selected_services[] = $key;
        }
    }

    public function completeSetup(): void
    {
        $this->validate([
            'shop_name' => 'required|string|min:3|max:100',
            'selected_services' => 'required|array|min:1',
        ], [
            'selected_services.min' => 'Please select at least one service offered by your shop.',
        ]);

        $user = auth()->user();

        /** @var PrintShop $shop */
        $shop = PrintShop::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $this->shop_name,
                'is_setup_completed' => true,
            ]
        );

        // Sync services
        $catalog = PrintServiceCatalog::all();
        foreach (array_keys($catalog) as $key) {
            $isActive = in_array($key, $this->selected_services, true);
            ShopService::updateOrCreate(
                [
                    'print_shop_id' => $shop->id,
                    'service_key' => $key,
                ],
                [
                    'is_active' => $isActive,
                ]
            );
        }

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-between p-4 sm:p-8 relative overflow-x-hidden selection:bg-amber-500 selection:text-white">
    <!-- Ambient Background Lighting -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/15 blur-[120px] pointer-events-none"></div>

    <!-- Wizard Header Bar -->
    <header class="w-full max-w-4xl mx-auto flex items-center justify-between py-4 border-b border-stone-800/80 relative z-20">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20">
                <x-app-logo-icon class="size-6 fill-current" />
            </span>
            <div>
                <h1 class="text-lg font-bold text-white tracking-tight">{{ config('app.name', 'Printify') }}</h1>
                <p class="text-xs text-amber-400 font-semibold uppercase tracking-wider">Business Owner Setup Wizard</p>
            </div>
        </div>

        <!-- Step Indicator Pills -->
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $step === 1 ? 'bg-amber-500 text-stone-950' : 'bg-stone-800 text-stone-400' }}">
                <span class="size-4 rounded-full flex items-center justify-center text-[10px] font-extrabold {{ $step === 1 ? 'bg-stone-950 text-amber-400' : 'bg-stone-700 text-stone-200' }}">1</span>
                Shop Identity
            </span>
            <span class="text-stone-600">/</span>
            <span class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $step === 2 ? 'bg-amber-500 text-stone-950' : 'bg-stone-800 text-stone-400' }}">
                <span class="size-4 rounded-full flex items-center justify-center text-[10px] font-extrabold {{ $step === 2 ? 'bg-stone-950 text-amber-400' : 'bg-stone-700 text-stone-200' }}">2</span>
                Services Selection
            </span>
        </div>
    </header>

    <!-- Main Wizard Card Body -->
    <main class="w-full max-w-4xl mx-auto my-auto py-8 relative z-20">
        <div class="rounded-3xl border border-stone-800 bg-stone-900/90 p-6 sm:p-10 shadow-2xl backdrop-blur-xl">
            @if ($step === 1)
                <!-- Step 1: Print Shop Identity -->
                <div class="space-y-6 max-w-xl mx-auto text-center sm:text-left">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                            <flux:icon name="building-storefront" class="size-4" />
                            Step 1 of 2
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">What is the name of your Print Shop?</h2>
                        <p class="text-sm text-stone-400">This will be displayed to customers on your storefront and on job production tickets.</p>
                    </div>

                    <div class="space-y-4 pt-2">
                        <flux:field>
                            <flux:label class="text-stone-300 font-semibold">{{ __('Print Shop Name') }}</flux:label>
                            <flux:input
                                wire:model.defer="shop_name"
                                type="text"
                                icon="building-storefront"
                                placeholder="e.g. Metro Print & Craft Studio"
                                class="text-lg py-3"
                                autofocus
                            />
                            <flux:error name="shop_name" />
                        </flux:field>
                    </div>

                    <div class="flex items-center justify-end pt-4">
                        <flux:button wire:click="nextStep" variant="primary" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-400 text-stone-950 font-bold px-8 py-3 rounded-xl shadow-lg shadow-amber-500/25">
                            Continue to Services Selection
                            <flux:icon name="arrow-right" class="size-4 ms-1" />
                        </flux:button>
                    </div>
                </div>
            @else
                <!-- Step 2: Select Services Offered -->
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-stone-800 pb-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider mb-2">
                                <flux:icon name="squares-plus" class="size-4" />
                                Step 2 of 2
                            </div>
                            <h2 class="text-2xl font-extrabold text-white">Select Services Offered by {{ $shop_name }}</h2>
                            <p class="text-xs text-stone-400">Click to toggle the services available in your shop. You can install or remove services anytime later.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-amber-400 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
                                {{ count($selected_services) }} Services Selected
                            </span>
                        </div>
                    </div>

                    @error('selected_services')
                        <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-medium">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Service Cards Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                        @foreach (App\Services\PrintServiceCatalog::all() as $key => $service)
                            @php
                                $isPreinstalled = App\Services\PrintServiceCatalog::isPreinstalled($key);
                                $isSelected = $isPreinstalled || in_array($key, $selected_services, true);
                            @endphp
                            <div
                                @if (! $isPreinstalled) wire:click="toggleService('{{ $key }}')" @endif
                                class="group relative flex flex-col justify-between p-4 rounded-2xl border transition-all duration-200 cursor-pointer select-none {{ $isSelected ? 'border-amber-500 bg-amber-500/10 shadow-lg shadow-amber-500/10 ring-1 ring-amber-500' : 'border-stone-800 bg-stone-900/60 hover:border-stone-700 hover:bg-stone-800/60' }} {{ $isPreinstalled ? 'cursor-default' : '' }}"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <!-- Squircle Icon Box -->
                                    <div class="size-12 rounded-2xl bg-gradient-to-br {{ $service['gradient'] }} flex items-center justify-center text-white shadow-md group-hover:scale-105 transition-transform duration-200">
                                        <flux:icon name="{{ $service['icon'] }}" class="size-6" />
                                    </div>

                                    @if ($isPreinstalled)
                                        <span class="px-2 py-0.5 rounded text-[9px] font-extrabold bg-violet-500/20 text-violet-300 border border-violet-500/30">Pre-installed</span>
                                    @else
                                        <!-- Checkmark Pill -->
                                        <div class="size-6 rounded-full flex items-center justify-center transition-colors {{ $isSelected ? 'bg-amber-500 text-stone-950' : 'bg-stone-800 border border-stone-700 text-transparent' }}">
                                            <flux:icon name="check" class="size-3.5 stroke-[3]" />
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <h3 class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors leading-tight">
                                        {{ $service['name'] }}
                                    </h3>
                                    <p class="text-[11px] text-stone-400 line-clamp-2 leading-relaxed">
                                        {{ $service['description'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Navigation Footer -->
                    <div class="flex items-center justify-between border-t border-stone-800 pt-6 mt-6">
                        <flux:button wire:click="previousStep" variant="subtle" class="text-stone-400 hover:text-white">
                            <flux:icon name="arrow-left" class="size-4 me-1" />
                            Back to Shop Name
                        </flux:button>

                        <flux:button wire:click="completeSetup" variant="primary" class="bg-amber-500 hover:bg-amber-400 text-stone-950 font-bold px-8 py-3 rounded-xl shadow-lg shadow-amber-500/25">
                            Finish Setup & Launch Dashboard
                            <flux:icon name="sparkles" class="size-4 ms-1" />
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-4xl mx-auto text-center text-xs text-stone-600 py-4 relative z-20">
        &copy; {{ date('Y') }} {{ config('app.name', 'Printify') }}. All rights reserved.
    </footer>
</div>
