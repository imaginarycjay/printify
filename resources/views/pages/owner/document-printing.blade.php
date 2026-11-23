<?php

use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\PrintShop;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Document Printing & Finishing Configuration')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $active_tab = 'rates'; // 'rates', 'stocks', 'finishing', 'bom'

    // Form fields
    public float $page_price_bw_short = 1.50;
    public float $page_price_bw_a4 = 1.50;
    public float $page_price_bw_long = 2.00;

    public float $page_price_color_short = 5.00;
    public float $page_price_color_a4 = 5.00;
    public float $page_price_color_long = 6.00;

    public float $paper_stock_70gsm_price = 0.00;
    public float $paper_stock_80gsm_price = 0.50;
    public float $paper_stock_100gsm_price = 1.50;
    public int $duplex_discount_percent = 10;

    public bool $allow_staple = true;
    public float $staple_price = 2.00;

    public bool $allow_folder_fastener = true;
    public float $folder_fastener_price = 15.00;

    public bool $allow_ring_binding = true;
    public float $ring_bind_base_price = 45.00;

    public bool $allow_booklet_staple = true;
    public float $booklet_staple_price = 20.00;

    public bool $allow_rush_orders = true;
    public float $rush_fee_amount = 50.00;

    public bool $auto_deduct_inventory = true;

    public ?int $bom_short_paper_item_id = null;
    public ?int $bom_a4_paper_item_id = null;
    public ?int $bom_long_paper_item_id = null;
    public ?int $bom_ring_spine_item_id = null;
    public ?int $bom_pvc_acetate_item_id = null;
    public ?int $bom_back_cover_item_id = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $shop = $user?->operationalShop();
        if (! $shop) {
            return;
        }

        $config = DocumentPrintingConfig::firstOrCreate(
            ['print_shop_id' => $shop->id],
            [
                'page_price_bw_short' => 1.50,
                'page_price_bw_a4' => 1.50,
                'page_price_bw_long' => 2.00,
                'page_price_color_short' => 5.00,
                'page_price_color_a4' => 5.00,
                'page_price_color_long' => 6.00,
                'paper_stock_70gsm_price' => 0.00,
                'paper_stock_80gsm_price' => 0.50,
                'paper_stock_100gsm_price' => 1.50,
                'duplex_discount_percent' => 10,
                'allow_staple' => true,
                'staple_price' => 2.00,
                'allow_folder_fastener' => true,
                'folder_fastener_price' => 15.00,
                'allow_ring_binding' => true,
                'ring_bind_base_price' => 45.00,
                'allow_booklet_staple' => true,
                'booklet_staple_price' => 20.00,
                'allow_rush_orders' => true,
                'rush_fee_amount' => 50.00,
                'auto_deduct_inventory' => true,
            ]
        );

        $this->page_price_bw_short = $config->page_price_bw_short;
        $this->page_price_bw_a4 = $config->page_price_bw_a4;
        $this->page_price_bw_long = $config->page_price_bw_long;

        $this->page_price_color_short = $config->page_price_color_short;
        $this->page_price_color_a4 = $config->page_price_color_a4;
        $this->page_price_color_long = $config->page_price_color_long;

        $this->paper_stock_70gsm_price = $config->paper_stock_70gsm_price;
        $this->paper_stock_80gsm_price = $config->paper_stock_80gsm_price;
        $this->paper_stock_100gsm_price = $config->paper_stock_100gsm_price;
        $this->duplex_discount_percent = $config->duplex_discount_percent;

        $this->allow_staple = $config->allow_staple;
        $this->staple_price = $config->staple_price;

        $this->allow_folder_fastener = $config->allow_folder_fastener;
        $this->folder_fastener_price = $config->folder_fastener_price;

        $this->allow_ring_binding = $config->allow_ring_binding;
        $this->ring_bind_base_price = $config->ring_bind_base_price;

        $this->allow_booklet_staple = $config->allow_booklet_staple;
        $this->booklet_staple_price = $config->booklet_staple_price;

        $this->allow_rush_orders = $config->allow_rush_orders;
        $this->rush_fee_amount = $config->rush_fee_amount;

        $this->auto_deduct_inventory = $config->auto_deduct_inventory;

        $this->bom_short_paper_item_id = $config->bom_short_paper_item_id;
        $this->bom_a4_paper_item_id = $config->bom_a4_paper_item_id;
        $this->bom_long_paper_item_id = $config->bom_long_paper_item_id;
        $this->bom_ring_spine_item_id = $config->bom_ring_spine_item_id;
        $this->bom_pvc_acetate_item_id = $config->bom_pvc_acetate_item_id;
        $this->bom_back_cover_item_id = $config->bom_back_cover_item_id;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'page_price_bw_short' => 'required|numeric|min:0.1',
            'page_price_bw_a4' => 'required|numeric|min:0.1',
            'page_price_bw_long' => 'required|numeric|min:0.1',
            'page_price_color_short' => 'required|numeric|min:0.1',
            'page_price_color_a4' => 'required|numeric|min:0.1',
            'page_price_color_long' => 'required|numeric|min:0.1',
            'paper_stock_80gsm_price' => 'required|numeric|min:0',
            'paper_stock_100gsm_price' => 'required|numeric|min:0',
            'duplex_discount_percent' => 'required|integer|min:0|max:100',
            'staple_price' => 'required|numeric|min:0',
            'folder_fastener_price' => 'required|numeric|min:0',
            'ring_bind_base_price' => 'required|numeric|min:0',
            'booklet_staple_price' => 'required|numeric|min:0',
            'rush_fee_amount' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $shop = $user?->operationalShop();
        if (! $shop) {
            return;
        }

        $config = DocumentPrintingConfig::firstOrNew(['print_shop_id' => $shop->id]);
        $config->fill([
            'page_price_bw_short' => $this->page_price_bw_short,
            'page_price_bw_a4' => $this->page_price_bw_a4,
            'page_price_bw_long' => $this->page_price_bw_long,
            'page_price_color_short' => $this->page_price_color_short,
            'page_price_color_a4' => $this->page_price_color_a4,
            'page_price_color_long' => $this->page_price_color_long,
            'paper_stock_70gsm_price' => $this->paper_stock_70gsm_price,
            'paper_stock_80gsm_price' => $this->paper_stock_80gsm_price,
            'paper_stock_100gsm_price' => $this->paper_stock_100gsm_price,
            'duplex_discount_percent' => $this->duplex_discount_percent,
            'allow_staple' => $this->allow_staple,
            'staple_price' => $this->staple_price,
            'allow_folder_fastener' => $this->allow_folder_fastener,
            'folder_fastener_price' => $this->folder_fastener_price,
            'allow_ring_binding' => $this->allow_ring_binding,
            'ring_bind_base_price' => $this->ring_bind_base_price,
            'allow_booklet_staple' => $this->allow_booklet_staple,
            'booklet_staple_price' => $this->booklet_staple_price,
            'allow_rush_orders' => $this->allow_rush_orders,
            'rush_fee_amount' => $this->rush_fee_amount,
            'auto_deduct_inventory' => $this->auto_deduct_inventory,
            'bom_short_paper_item_id' => $this->bom_short_paper_item_id,
            'bom_a4_paper_item_id' => $this->bom_a4_paper_item_id,
            'bom_long_paper_item_id' => $this->bom_long_paper_item_id,
            'bom_ring_spine_item_id' => $this->bom_ring_spine_item_id,
            'bom_pvc_acetate_item_id' => $this->bom_pvc_acetate_item_id,
            'bom_back_cover_item_id' => $this->bom_back_cover_item_id,
        ]);
        $config->save();

        Flux::toast(
            text: 'Document printing rates and inventory BOM recipes have been saved.',
            heading: 'Configuration Updated',
            variant: 'success'
        );
    }
}; ?>

<div class="pq-page flex flex-col justify-between">
    @php
        $user = auth()->user();
        $shop = $user?->operationalShop();
        $inventoryItems = $shop ? $shop->inventoryItems : collect();
    @endphp

    <!-- Top Navigation Header -->
    <header class="pq-topbar px-4 sm:px-8 py-3.5 flex flex-wrap gap-3 items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <x-app-back-link :href="route('owner.dashboard')" label="App Launcher" :navigate="true" />
            <span class="hidden sm:flex size-10 shrink-0 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                <flux:icon name="document-text" class="size-5" />
            </span>
            <div class="min-w-0">
                <h1 class="truncate text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                    Document Printing &amp; Ring Binding
                </h1>
                <p class="truncate text-xs text-slate-600 dark:text-slate-400">{{ $shop ? $shop->name : 'PressQueue' }} &bull; Page Rates, Duplex Rules, Finishing Options &amp; BOM Recipes</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('owner.platform') }}#availability" wire:navigate class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-blue-600 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-400 dark:hover:text-white transition-colors">
                <flux:icon name="signal" class="size-4" /> Availability
            </a>
            <a href="{{ route('owner.inventory-hub', ['from' => 'document-printing']) }}" wire:navigate class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-blue-600 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-400 dark:hover:text-white transition-colors">
                <flux:icon name="archive-box" class="size-4" /> Inventory
            </a>
            <x-theme-toggle />

            <button
                type="button"
                wire:click="saveSettings"
                class="min-h-10 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition-colors cursor-pointer inline-flex items-center gap-1.5 dark:hover:bg-blue-500"
            >
                <flux:icon name="check" class="size-3.5" />
                <span>Save Rates</span>
            </button>
        </div>
    </header>

    <!-- Main Config Body -->
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-8 py-8 space-y-6">
        <!-- Tab Navigation Bar -->
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 overflow-x-auto">
            <button
                type="button"
                wire:click="$set('active_tab', 'rates')"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'rates' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:border-blue-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:border-blue-400' }}"
            >
                <flux:icon name="currency-dollar" class="size-4" />
                1. Page Rates &amp; Sizes
            </button>

            <button
                type="button"
                wire:click="$set('active_tab', 'stocks')"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'stocks' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:border-blue-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:border-blue-400' }}"
            >
                <flux:icon name="scale" class="size-4" />
                2. Paper Stock &amp; Duplex
            </button>

            <button
                type="button"
                wire:click="$set('active_tab', 'finishing')"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'finishing' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:border-blue-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:border-blue-400' }}"
            >
                <flux:icon name="sparkles" class="size-4" />
                3. Finishing &amp; Binding
            </button>

            <button
                type="button"
                wire:click="$set('active_tab', 'bom')"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors flex items-center gap-2 cursor-pointer {{ $active_tab === 'bom' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:border-blue-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:border-blue-400' }}"
            >
                <flux:icon name="archive-box" class="size-4" />
                4. Inventory BOM Recipes
            </button>
        </div>

        <!-- Tab 1: Page Rates & Sizes -->
        @if ($active_tab === 'rates')
            <div class="space-y-6">
                <div class="pq-card p-6 space-y-6">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                            <flux:icon name="document-text" class="size-5 text-sky-600 dark:text-sky-400" />
                            Monochrome (Black &amp; White) Page Rates
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Standard per-page printing fee for grayscale text and documents</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Short / Letter (8.5 x 11 in)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_bw_short"
                                    type="number"
                                    step="0.25"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱1.50 / page</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">A4 (8.27 x 11.69 in)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_bw_a4"
                                    type="number"
                                    step="0.25"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱1.50 / page</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Long / Legal (8.5 x 13 in)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_bw_long"
                                    type="number"
                                    step="0.25"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱2.00 / page</span>
                        </div>
                    </div>
                </div>

                <div class="pq-card p-6 space-y-6">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                            <flux:icon name="sparkles" class="size-5 text-blue-600 dark:text-blue-400" />
                            Full Colored Page Rates
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">High-resolution colored graphics, charts, diagrams, and photo prints</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Short / Letter Color</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_color_short"
                                    type="number"
                                    step="0.50"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱5.00 / page</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">A4 Color</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_color_a4"
                                    type="number"
                                    step="0.50"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱5.00 / page</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Long / Legal Color</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="page_price_color_long"
                                    type="number"
                                    step="0.50"
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 focus:border-blue-600 dark:focus:border-blue-400 rounded-xl pl-8 pr-3 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">Default rate: ₱6.00 / page</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 2: Paper Stock & Duplex Settings -->
        @if ($active_tab === 'stocks')
            <div class="pq-card p-6 space-y-6">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <flux:icon name="scale" class="size-5 text-sky-600 dark:text-sky-400" />
                        Paper Stock Weight Surcharges &amp; Duplex Discount
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Configure extra fees for heavy paper stock and discounts for paper-saving back-to-back printing</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="pq-well p-4 space-y-4">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300">Paper Weight Add-on (per physical sheet)</h4>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Standard 70gsm (Default)</label>
                            <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">+₱0.00 (Included in base)</div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Premium 80gsm Extra Fee</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="paper_stock_80gsm_price"
                                    type="number"
                                    step="0.25"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Specialty 100gsm Extra Fee</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                <input
                                    wire:model="paper_stock_100gsm_price"
                                    type="number"
                                    step="0.25"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pq-well p-4 space-y-4 flex flex-col justify-between">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300">Duplex (Back-to-Back) Discount</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                Encourage customers to save paper by offering an automated percentage discount when printing 2-sided pages.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Duplex Discount (%)</label>
                            <div class="relative">
                                <input
                                    wire:model="duplex_discount_percent"
                                    type="number"
                                    min="0"
                                    max="50"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-3 pr-8 py-2 text-sm text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                                />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">%</span>
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">e.g. 10% discount off page total</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 3: Finishing & Binding Services -->
        @if ($active_tab === 'finishing')
            <div class="pq-card p-6 space-y-6">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <flux:icon name="sparkles" class="size-5 text-blue-600 dark:text-blue-400" />
                        Finishing &amp; Post-Press Binding Services
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Offer value-added binding options to turn loose printouts into organized handouts, reviewers, and portfolios</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Corner Stapling -->
                    <div class="pq-well p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100 inline-flex items-center gap-1.5">
                                <flux:icon name="paper-clip" class="size-4 text-slate-500" />
                                Corner Stapling
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="allow_staple" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                            <input
                                wire:model="staple_price"
                                type="number"
                                step="1"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                            />
                        </div>
                    </div>

                    <!-- Sliding Folder & Fastener -->
                    <div class="pq-well p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100 inline-flex items-center gap-1.5">
                                <flux:icon name="folder" class="size-4 text-slate-500" />
                                Sliding Folder &amp; Fastener
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="allow_folder_fastener" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                            <input
                                wire:model="folder_fastener_price"
                                type="number"
                                step="1"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                            />
                        </div>
                    </div>

                    <!-- Plastic Ring / Coil Binding -->
                    <div class="pq-well p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100 inline-flex items-center gap-1.5">
                                <flux:icon name="book-open" class="size-4 text-slate-500" />
                                Plastic Ring Binding (with Clear PVC)
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="allow_ring_binding" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                            <input
                                wire:model="ring_bind_base_price"
                                type="number"
                                step="5"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                            />
                        </div>
                    </div>

                    <!-- Rush Processing Fee -->
                    <div class="pq-well p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-amber-700 dark:text-amber-400 inline-flex items-center gap-1.5">
                                <flux:icon name="bolt" class="size-4" />
                                Rush Order Surcharge
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="allow_rush_orders" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                            <input
                                wire:model="rush_fee_amount"
                                type="number"
                                step="10"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 font-mono tabular-nums font-bold outline-none focus:border-blue-600 dark:focus:border-blue-400"
                            />
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 4: Automated Inventory BOM Recipes -->
        @if ($active_tab === 'bom')
            <div class="pq-card p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                            <flux:icon name="archive-box" class="size-5 text-emerald-700 dark:text-emerald-400" />
                            Automated Inventory Bill of Materials (BOM) Linking
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Link document sizes and ring bindings directly to Central Inventory items to automate stock deduction</p>
                    </div>

                    <!-- Auto-deduct toggle -->
                    <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
                        <input type="checkbox" wire:model="auto_deduct_inventory" class="sr-only peer">
                        <div class="w-8 h-4 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Auto-Deduct Stock</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Short Paper BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="document" class="size-3.5 text-slate-500" />
                            Short / Letter Paper Stock Item
                        </label>
                        <select wire:model="bom_short_paper_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1 sheet per simplex page (or 1 sheet per 2 duplex pages)</p>
                    </div>

                    <!-- A4 Paper BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="document" class="size-3.5 text-slate-500" />
                            A4 Paper Stock Item
                        </label>
                        <select wire:model="bom_a4_paper_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1 sheet per simplex page (or 1 sheet per 2 duplex pages)</p>
                    </div>

                    <!-- Long Paper BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="document" class="size-3.5 text-slate-500" />
                            Long / Legal Paper Stock Item
                        </label>
                        <select wire:model="bom_long_paper_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1 sheet per simplex page (or 1 sheet per 2 duplex pages)</p>
                    </div>

                    <!-- Plastic Ring Spine BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="book-open" class="size-3.5 text-slate-500" />
                            Plastic Ring Comb / Coil Spines
                        </label>
                        <select wire:model="bom_ring_spine_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1 ring spine per ring-bound copy</p>
                    </div>

                    <!-- PVC Acetate Sheet BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="shield-check" class="size-3.5 text-slate-500" />
                            Clear PVC Acetate Covers
                        </label>
                        <select wire:model="bom_pvc_acetate_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1-2 PVC sheets per ring-bound copy</p>
                    </div>

                    <!-- Morocco / Back Cover Board BOM -->
                    <div class="pq-well p-4 space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5">
                            <flux:icon name="rectangle-stack" class="size-3.5 text-slate-500" />
                            Morocco / Linen Back Board Item
                        </label>
                        <select wire:model="bom_back_cover_item_id" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 outline-none focus:border-blue-600 dark:focus:border-blue-400">
                            <option value="">-- Do not link (Manual) --</option>
                            @foreach ($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stock: {{ $item->stock_qty }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Deducts 1 board sheet per ring-bound copy</p>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <!-- Footer Bar -->
    <footer class="w-full bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 px-4 sm:px-8 py-3 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-2">
        <span>&copy; {{ date('Y') }} {{ $shop ? $shop->name : 'PressQueue' }} &bull; Document Printing Service Module</span>
        <span class="font-medium">
            Duplex Sheet Calculation &amp; Automated Ring BOM Burn
        </span>
    </footer>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
