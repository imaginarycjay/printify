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

        if ($shop->getAttribute('approval_status') === 'rejected') { $shop->update(['approval_status' => 'pending', 'rejection_reason' => null]); }

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

<div class="pq-page flex flex-col justify-between p-4 sm:p-8">
    @php
        $wizardShop = auth()->user()?->operationalShop();
    @endphp

    <!-- Wizard Header Bar -->
    <header class="w-full max-w-4xl mx-auto flex flex-wrap items-center justify-between gap-4 py-4 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
            @if ($wizardShop?->is_setup_completed)
                <x-app-back-link :href="route('owner.dashboard')" label="App Launcher" :navigate="true" />
            @else
                <span data-theme-keep class="flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-800">
                    <x-app-logo-icon class="size-6" />
                </span>
            @endif
            <div>
                <h1 class="text-lg font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">{{ config('app.name', 'PressQueue') }}</h1>
                <p class="text-xs text-blue-700 dark:text-blue-400 font-semibold">Shop Setup Wizard</p>
            </div>
        </div>

        <!-- Step Indicator Pills & Theme Toggle -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $step === 1 ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400' }}">
                    <span class="size-4 rounded-full flex items-center justify-center text-[10px] font-extrabold {{ $step === 1 ? 'bg-white text-blue-700' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">1</span>
                    Shop Identity
                </span>
                <span class="text-slate-400 dark:text-slate-600">/</span>
                <span class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $step === 2 ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400' }}">
                    <span class="size-4 rounded-full flex items-center justify-center text-[10px] font-extrabold {{ $step === 2 ? 'bg-white text-blue-700' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">2</span>
                    Services Selection
                </span>
            </div>
            <x-theme-toggle />
        </div>
    </header>

    <!-- Main Wizard Card Body -->
    <main class="w-full max-w-4xl mx-auto my-auto py-8">
        <div class="pq-card p-6 sm:p-10">
            @if ($step === 1)
                <!-- Step 1: Print Shop Identity -->
                <div class="space-y-6 max-w-xl mx-auto text-center sm:text-left">
                    <div class="space-y-2">
                        <span class="pq-badge-brand">
                            <flux:icon name="building-storefront" class="size-3.5" />
                            Step 1 of 2
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">What is the name of your Print Shop?</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">This will be displayed to customers on your storefront and on job production tickets.</p>
                    </div>

                    <div class="space-y-4 pt-2">
                        <flux:field>
                            <flux:label class="text-slate-700 dark:text-slate-300 font-semibold">{{ __('Print Shop Name') }}</flux:label>
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
                        <flux:button wire:click="nextStep" variant="primary" class="w-full sm:w-auto !bg-blue-600 hover:!bg-blue-700 !text-white font-bold px-8 py-3 rounded-xl dark:hover:!bg-blue-500">
                            Proceed
                        </flux:button>
                    </div>
                </div>
            @else
                <!-- Step 2: Select Services Offered -->
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <div>
                            <span class="pq-badge-brand mb-2">
                                <flux:icon name="squares-plus" class="size-3.5" />
                                Step 2 of 2
                            </span>
                            <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100">Select Services Offered by {{ $shop_name }}</h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Click to toggle the services available in your shop. You can install or remove services anytime later.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="pq-badge-brand tabular-nums">
                                {{ count($selected_services) }} Services Selected
                            </span>
                        </div>
                    </div>

                    @error('selected_services')
                        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/60 dark:border-rose-800 dark:text-rose-300 text-xs font-medium">
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
                                class="group relative flex flex-col justify-between p-4 rounded-2xl border transition-colors cursor-pointer select-none {{ $isSelected ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-1 ring-blue-600' : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700' }} {{ $isPreinstalled ? 'cursor-default' : '' }}"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div class="size-10 rounded-xl border {{ $service['badge_color'] }} flex items-center justify-center">
                                        <flux:icon name="{{ $service['icon'] }}" class="size-5" />
                                    </div>

                                    @if ($isPreinstalled)
                                        <span class="pq-badge-neutral">Pre-installed</span>
                                    @else
                                        <div class="size-6 rounded-full flex items-center justify-center transition-colors {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-white border border-slate-300 text-transparent dark:bg-slate-800 dark:border-slate-700' }}">
                                            <flux:icon name="check" class="size-3.5 stroke-[3]" />
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors leading-tight">
                                        {{ $service['name'] }}
                                    </h3>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $service['description'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Navigation Footer -->
                    <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-6 mt-6">
                        <flux:button wire:click="previousStep" variant="subtle" class="border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 font-medium px-6 py-3 rounded-xl">
                            Return
                        </flux:button>

                        <flux:button wire:click="completeSetup" variant="primary" class="!bg-blue-600 hover:!bg-blue-700 !text-white font-bold px-8 py-3 rounded-xl dark:hover:!bg-blue-500">
                            Finish Setup
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-4xl mx-auto text-center text-xs text-slate-500 dark:text-slate-400 py-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'PressQueue') }}. All rights reserved.
    </footer>
</div>
