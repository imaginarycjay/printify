<?php

use App\Models\InventoryItem;
use App\Models\PrintShop;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use Livewire\Attributes\Layout;
use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $active_tab = 'pricing';
    public bool $mobile_menu_open = false;

    // Toast message state
    public ?string $statusMessage = null;

    // 1. General Setup & Pricing
    public bool $is_active = true;
    public float $hardbound_base_price = 350.00;
    public float $softbound_base_price = 150.00;
    public bool $allow_customer_supplied_paper = true;
    public float $hardbound_cover_only_price = 300.00;
    public float $page_price_bw = 1.50;
    public float $page_price_color = 5.00;
    public float $rush_fee = 150.00;

    // Price Estimator Preview
    public string $test_fulfillment_type = 'full_package'; // 'full_package' or 'cover_only'
    public string $test_binding_type = 'hardbound';
    public int $test_bw_pages = 80;
    public int $test_color_pages = 20;
    public bool $test_is_rush = false;

    // 2. Variants
    public array $cover_colors = ['Maroon', 'Dark Blue', 'Black', 'Green'];
    public string $new_color_input = '';
    public array $foil_colors = ['Gold', 'Silver'];
    public string $new_foil_input = '';
    public array $paper_sizes = ['A4', 'Letter (Short)', 'Legal (Long)'];

    // 3. BOM & Inventory Link
    public bool $auto_deduct_inventory = true;
    public ?int $selected_inventory_item_id = null;
    public string $bom_binding_type = 'hardbound';
    public float $bom_usage_qty = 1.0;
    public string $bom_unit = 'pcs';

    // Quick Add Inventory Modal
    public bool $show_add_inventory_modal = false;
    public string $new_inv_name = '';
    public string $new_inv_sku = '';
    public string $new_inv_category = 'Raw Material';
    public float $new_inv_stock_qty = 50.0;
    public string $new_inv_unit = 'pcs';
    public float $new_inv_reorder_level = 10.0;

    // 4. Production Limits & Scheduling
    public int $daily_production_quota = 20;
    public int $standard_lead_time_days = 4;
    public int $rush_lead_time_days = 1;

    // 5. Customer Form Requirements
    public bool $require_pdf_upload = true;
    public array $custom_cover_fields = ['Thesis Title', 'Name of Researchers', 'Degree / Course', 'School Year'];
    public string $new_field_input = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var PrintShop $shop */
        $shop = $user->printShop;

        // Seed default inventory items if empty for the shop
        if ($shop->inventoryItems()->count() === 0) {
            $defaultMaterials = [
                ['name' => 'Chipboard Heavy Duty (2mm)', 'sku' => 'MAT-CHP-01', 'category' => 'Raw Material', 'stock_qty' => 120.0, 'unit' => 'pcs', 'reorder_level' => 20.0],
                ['name' => 'Leatherette Cover Sheet (Maroon/Black)', 'sku' => 'MAT-LTH-02', 'category' => 'Raw Material', 'stock_qty' => 150.0, 'unit' => 'sheets', 'reorder_level' => 25.0],
                ['name' => 'Hot Melt Binding Glue', 'sku' => 'MAT-GLU-03', 'category' => 'Adhesive', 'stock_qty' => 25.0, 'unit' => 'kg', 'reorder_level' => 5.0],
                ['name' => 'Foil Stamping Roll (Gold/Silver)', 'sku' => 'MAT-FOL-04', 'category' => 'Foil', 'stock_qty' => 30.0, 'unit' => 'rolls', 'reorder_level' => 5.0],
                ['name' => 'A4 Paper 80gsm Premium', 'sku' => 'PAP-A4-80', 'category' => 'Paper', 'stock_qty' => 5000.0, 'unit' => 'sheets', 'reorder_level' => 1000.0],
            ];

            foreach ($defaultMaterials as $mat) {
                $shop->inventoryItems()->create($mat);
            }
        }

        /** @var ThesisBindingConfig $config */
        $config = ThesisBindingConfig::firstOrCreate(
            ['print_shop_id' => $shop->id],
            [
                'is_active' => true,
                'hardbound_base_price' => 350.00,
                'softbound_base_price' => 150.00,
                'page_price_bw' => 1.50,
                'page_price_color' => 5.00,
                'rush_fee' => 150.00,
                'cover_colors' => ThesisBindingConfig::defaultCoverColors(),
                'foil_colors' => ThesisBindingConfig::defaultFoilColors(),
                'paper_sizes' => ThesisBindingConfig::defaultPaperSizes(),
                'auto_deduct_inventory' => true,
                'daily_production_quota' => 20,
                'standard_lead_time_days' => 4,
                'rush_lead_time_days' => 1,
                'require_pdf_upload' => true,
                'custom_cover_fields' => ThesisBindingConfig::defaultCustomCoverFields(),
            ]
        );

        $this->is_active = $config->is_active;
        $this->hardbound_base_price = (float) $config->hardbound_base_price;
        $this->softbound_base_price = (float) $config->softbound_base_price;
        $this->allow_customer_supplied_paper = (bool) ($config->allow_customer_supplied_paper ?? true);
        $this->hardbound_cover_only_price = (float) ($config->hardbound_cover_only_price ?? 300.00);
        $this->page_price_bw = (float) $config->page_price_bw;
        $this->page_price_color = (float) $config->page_price_color;
        $this->rush_fee = (float) $config->rush_fee;

        $this->cover_colors = $config->cover_colors ?? ThesisBindingConfig::defaultCoverColors();
        $this->foil_colors = $config->foil_colors ?? ThesisBindingConfig::defaultFoilColors();
        $this->paper_sizes = $config->paper_sizes ?? ThesisBindingConfig::defaultPaperSizes();

        $this->auto_deduct_inventory = (bool) $config->auto_deduct_inventory;
        $this->daily_production_quota = (int) $config->daily_production_quota;
        $this->standard_lead_time_days = (int) $config->standard_lead_time_days;
        $this->rush_lead_time_days = (int) $config->rush_lead_time_days;

        $this->require_pdf_upload = (bool) $config->require_pdf_upload;
        $this->custom_cover_fields = $config->custom_cover_fields ?? ThesisBindingConfig::defaultCustomCoverFields();

        // Default selected inventory item for BOM form
        $firstItem = $shop->inventoryItems()->first();
        if ($firstItem) {
            $this->selected_inventory_item_id = $firstItem->id;
            $this->bom_unit = $firstItem->unit;
        }
    }

    public function setTab(string $tab): void
    {
        $this->active_tab = $tab;
        $this->mobile_menu_open = false;
    }

    public function toggleMobileMenu(): void
    {
        $this->mobile_menu_open = ! $this->mobile_menu_open;
    }

    public function updatedSelectedInventoryItemId(mixed $val): void
    {
        if ($val) {
            $item = InventoryItem::find($val);
            if ($item) {
                $this->bom_unit = $item->unit;
            }
        }
    }

    protected function getConfig(): ?ThesisBindingConfig
    {
        $shop = auth()->user()?->printShop;
        return $shop ? ThesisBindingConfig::where('print_shop_id', $shop->id)->first() : null;
    }

    public function savePricing(): void
    {
        $this->validate([
            'hardbound_base_price' => ['required', 'numeric', 'min:0'],
            'softbound_base_price' => ['required', 'numeric', 'min:0'],
            'hardbound_cover_only_price' => ['required', 'numeric', 'min:0'],
            'page_price_bw' => ['required', 'numeric', 'min:0'],
            'page_price_color' => ['required', 'numeric', 'min:0'],
            'rush_fee' => ['required', 'numeric', 'min:0'],
        ]);

        $config = $this->getConfig();
        if ($config) {
            $config->update([
                'is_active' => $this->is_active,
                'hardbound_base_price' => $this->hardbound_base_price,
                'softbound_base_price' => $this->softbound_base_price,
                'allow_customer_supplied_paper' => $this->allow_customer_supplied_paper,
                'hardbound_cover_only_price' => $this->hardbound_cover_only_price,
                'page_price_bw' => $this->page_price_bw,
                'page_price_color' => $this->page_price_color,
                'rush_fee' => $this->rush_fee,
            ]);

            $this->statusMessage = 'General Setup & Pricing configuration saved successfully!';
        }
    }

    public function addCoverColor(): void
    {
        $color = trim($this->new_color_input);
        if ($color !== '' && ! in_array($color, $this->cover_colors, true)) {
            $this->cover_colors[] = $color;
            $this->new_color_input = '';
            $this->saveVariants();
        }
    }

    public function removeCoverColor(int $index): void
    {
        if (isset($this->cover_colors[$index])) {
            array_splice($this->cover_colors, $index, 1);
            $this->saveVariants();
        }
    }

    public function toggleFoilColor(string $color): void
    {
        if (in_array($color, $this->foil_colors, true)) {
            $this->foil_colors = array_values(array_filter($this->foil_colors, fn ($c) => $c !== $color));
        } else {
            $this->foil_colors[] = $color;
        }
        $this->saveVariants();
    }

    public function addFoilColor(): void
    {
        $color = trim($this->new_foil_input);
        if ($color !== '' && ! in_array($color, $this->foil_colors, true)) {
            $this->foil_colors[] = $color;
            $this->new_foil_input = '';
            $this->saveVariants();
        }
    }

    public function togglePaperSize(string $size): void
    {
        if (in_array($size, $this->paper_sizes, true)) {
            $this->paper_sizes = array_values(array_filter($this->paper_sizes, fn ($s) => $s !== $size));
        } else {
            $this->paper_sizes[] = $size;
        }
        $this->saveVariants();
    }

    public function saveVariants(): void
    {
        $config = $this->getConfig();
        if ($config) {
            $config->update([
                'cover_colors' => array_values($this->cover_colors),
                'foil_colors' => array_values($this->foil_colors),
                'paper_sizes' => array_values($this->paper_sizes),
            ]);
            $this->statusMessage = 'Product Variants updated successfully!';
        }
    }

    public function toggleAutoDeductInventory(): void
    {
        $this->auto_deduct_inventory = ! $this->auto_deduct_inventory;
        $config = $this->getConfig();
        if ($config) {
            $config->update(['auto_deduct_inventory' => $this->auto_deduct_inventory]);
            $this->statusMessage = 'Automated inventory deduction setting updated!';
        }
    }

    public function addBomItem(): void
    {
        $this->validate([
            'selected_inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'bom_binding_type' => ['required', 'in:hardbound,softbound,both'],
            'bom_usage_qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $config = $this->getConfig();
        if (! $config) {
            return;
        }

        $invItem = InventoryItem::find($this->selected_inventory_item_id);
        if (! $invItem) {
            return;
        }

        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $config->id,
            'inventory_item_id' => $invItem->id,
            'binding_type' => $this->bom_binding_type,
            'usage_qty' => $this->bom_usage_qty,
            'unit' => $this->bom_unit ?: $invItem->unit,
        ]);

        $this->statusMessage = "Added {$invItem->name} to Bill of Materials (BOM)!";
    }

    public function deleteBomItem(int $id): void
    {
        $bomItem = ThesisBindingBomItem::find($id);
        if ($bomItem) {
            $bomItem->delete();
            $this->statusMessage = 'BOM material rule deleted successfully.';
        }
    }

    public function openAddInventoryModal(): void
    {
        $this->show_add_inventory_modal = true;
    }

    public function closeAddInventoryModal(): void
    {
        $this->show_add_inventory_modal = false;
    }

    public function saveQuickInventoryItem(): void
    {
        $this->validate([
            'new_inv_name' => ['required', 'string', 'max:255'],
            'new_inv_category' => ['required', 'string', 'max:100'],
            'new_inv_stock_qty' => ['required', 'numeric', 'min:0'],
            'new_inv_unit' => ['required', 'string', 'max:20'],
        ]);

        $shop = auth()->user()?->printShop;
        if (! $shop) {
            return;
        }

        $newItem = InventoryItem::create([
            'print_shop_id' => $shop->id,
            'name' => trim($this->new_inv_name),
            'sku' => trim($this->new_inv_sku) ?: null,
            'category' => trim($this->new_inv_category),
            'stock_qty' => $this->new_inv_stock_qty,
            'unit' => trim($this->new_inv_unit),
            'reorder_level' => $this->new_inv_reorder_level,
        ]);

        $this->selected_inventory_item_id = $newItem->id;
        $this->bom_unit = $newItem->unit;
        $this->show_add_inventory_modal = false;
        $this->new_inv_name = '';
        $this->new_inv_sku = '';

        $this->statusMessage = "Created new material: {$newItem->name}";
    }

    public function saveProductionLimits(): void
    {
        $this->validate([
            'daily_production_quota' => ['required', 'integer', 'min:1'],
            'standard_lead_time_days' => ['required', 'integer', 'min:1'],
            'rush_lead_time_days' => ['required', 'integer', 'min:1'],
        ]);

        $config = $this->getConfig();
        if ($config) {
            $config->update([
                'daily_production_quota' => $this->daily_production_quota,
                'standard_lead_time_days' => $this->standard_lead_time_days,
                'rush_lead_time_days' => $this->rush_lead_time_days,
            ]);

            $this->statusMessage = 'Production Limits & Lead Times saved successfully!';
        }
    }

    public function addCustomField(): void
    {
        $field = trim($this->new_field_input);
        if ($field !== '' && ! in_array($field, $this->custom_cover_fields, true)) {
            $this->custom_cover_fields[] = $field;
            $this->new_field_input = '';
            $this->saveCustomerFormRequirements();
        }
    }

    public function removeCustomField(int $index): void
    {
        if (isset($this->custom_cover_fields[$index])) {
            array_splice($this->custom_cover_fields, $index, 1);
            $this->saveCustomerFormRequirements();
        }
    }

    public function toggleRequirePdfUpload(): void
    {
        $this->require_pdf_upload = ! $this->require_pdf_upload;
        $this->saveCustomerFormRequirements();
    }

    public function saveCustomerFormRequirements(): void
    {
        $config = $this->getConfig();
        if ($config) {
            $config->update([
                'require_pdf_upload' => $this->require_pdf_upload,
                'custom_cover_fields' => array_values($this->custom_cover_fields),
            ]);
            $this->statusMessage = 'Customer Form Requirements updated successfully!';
        }
    }
}; ?>

<div class="h-screen w-screen bg-stone-950 text-stone-100 font-sans antialiased flex flex-col md:flex-row relative overflow-hidden selection:bg-amber-500 selection:text-white">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 left-1/3 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $user?->printShop;
        $inventoryList = $shop ? $shop->inventoryItems()->forService('thesis_binding')->orderBy('name')->get() : collect();
        if ($inventoryList->isEmpty() && $shop) {
            $inventoryList = $shop->inventoryItems()->orderBy('name')->get();
        }
        $addOnsList = $shop ? $shop->inventoryItems()->readyToSell()->forService('thesis_binding')->orderBy('name')->get() : collect();
        $configModel = $shop ? $shop->thesisBindingConfig : null;
        $bomList = $configModel ? $configModel->bomItems()->with('inventoryItem')->get() : collect();

        // Live estimate preview calculations
        $baseSelected = $this->test_fulfillment_type === 'cover_only'
            ? $this->hardbound_cover_only_price
            : ($this->test_binding_type === 'hardbound' ? $this->hardbound_base_price : $this->softbound_base_price);
        $bwTotal = $this->test_fulfillment_type === 'cover_only' ? 0.0 : ($this->test_bw_pages * $this->page_price_bw);
        $colorTotal = $this->test_fulfillment_type === 'cover_only' ? 0.0 : ($this->test_color_pages * $this->page_price_color);
        $rushFeeTotal = $this->test_is_rush ? $this->rush_fee : 0.0;
        $estimatedTotalPrice = $baseSelected + $bwTotal + $colorTotal + $rushFeeTotal;
    @endphp

    <!-- DESKTOP RE-DESIGNED SIDEBAR (Hidden on mobile, w-72 on md+) -->
    <aside class="hidden md:flex flex-col w-72 bg-stone-900/90 border-r border-stone-800/80 backdrop-blur-xl p-5 justify-between shrink-0 h-screen overflow-y-auto no-scrollbar z-30 space-y-6">
        <div class="space-y-6">
            <!-- Shop & Module Branding Header -->
            <div class="space-y-3 border-b border-stone-800/80 pb-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20">
                        <flux:icon name="book-open" class="size-6 stroke-[2]" />
                    </span>
                    <div>
                        <h2 class="text-sm font-extrabold text-white leading-tight line-clamp-1">{{ $shop ? $shop->name : 'Print Shop' }}</h2>
                        <span class="text-[10px] font-bold text-amber-400 tracking-wide uppercase">Thesis Binding App</span>
                    </div>
                </div>

                <!-- Service Status Indicator Switch in Sidebar -->
                <div class="flex items-center justify-between p-2.5 rounded-2xl bg-stone-950 border border-stone-800">
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full {{ $is_active ? 'bg-emerald-400 animate-pulse' : 'bg-stone-600' }}"></span>
                        <span class="text-xs font-bold {{ $is_active ? 'text-emerald-400' : 'text-stone-400' }}">
                            {{ $is_active ? 'Service Active' : 'Service Offline' }}
                        </span>
                    </div>
                    <button
                        type="button"
                        wire:click="$toggle('is_active')"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-emerald-500' : 'bg-stone-700' }}"
                    >
                        <span class="pointer-events-none inline-block size-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

            <!-- Dynamic Module Tools Navigation Section -->
            <div class="space-y-2">
                <span class="px-2 text-[10px] font-extrabold text-stone-400 tracking-wider uppercase flex items-center gap-1.5">
                    <flux:icon name="wrench-screwdriver" class="size-3 text-amber-500" />
                    Thesis Binding Tools
                </span>

                <nav class="space-y-1">
                    <button
                        wire:click="setTab('pricing')"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3 {{ $active_tab === 'pricing' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="currency-dollar" class="size-4 shrink-0 {{ $active_tab === 'pricing' ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>1. Pricing & General Setup</span>
                    </button>

                    <button
                        wire:click="setTab('variants')"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3 {{ $active_tab === 'variants' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="swatch" class="size-4 shrink-0 {{ $active_tab === 'variants' ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>2. Product Variants</span>
                    </button>

                    <button
                        wire:click="setTab('bom')"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3 {{ $active_tab === 'bom' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="cube" class="size-4 shrink-0 {{ $active_tab === 'bom' ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>3. BOM & Inventory Link</span>
                    </button>

                    <button
                        wire:click="setTab('production')"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3 {{ $active_tab === 'production' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="clock" class="size-4 shrink-0 {{ $active_tab === 'production' ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>4. Production & Scheduling</span>
                    </button>

                    <button
                        wire:click="setTab('customer_form')"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3 {{ $active_tab === 'customer_form' ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="document-text" class="size-4 shrink-0 {{ $active_tab === 'customer_form' ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>5. Customer Form Rules</span>
                    </button>
                </nav>
            </div>

            <!-- Global System Applications Section -->
            <div class="space-y-2 border-t border-stone-800/80 pt-4">
                <span class="px-2 text-[10px] font-extrabold text-stone-400 tracking-wider uppercase flex items-center gap-1.5">
                    <flux:icon name="squares-2x2" class="size-3 text-indigo-400" />
                    System Apps
                </span>

                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-stone-400 hover:text-white hover:bg-stone-800/60 transition-all">
                        <flux:icon name="squares-2x2" class="size-4 text-stone-500" />
                        <span>App Launcher Hub</span>
                    </a>

                    <a href="{{ route('owner.inventory-hub') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-stone-400 hover:text-white hover:bg-stone-800/60 transition-all">
                        <flux:icon name="archive-box" class="size-4 text-emerald-400" />
                        <span>Central Inventory Hub</span>
                    </a>

                    <a href="{{ route('owner.web-builder') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-stone-400 hover:text-white hover:bg-stone-800/60 transition-all">
                        <flux:icon name="globe-alt" class="size-4 text-violet-400" />
                        <span>Storefront Web Builder</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar Bottom User Badge -->
        <div class="border-t border-stone-800/80 pt-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover border border-amber-500/40 shadow-md" />
                @else
                    <span class="size-8 rounded-full bg-amber-500 text-stone-950 text-xs font-extrabold flex items-center justify-center shadow-md">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <div class="grid leading-tight">
                    <span class="text-xs font-bold text-white truncate max-w-[120px]">{{ $user?->name }}</span>
                    <span class="text-[10px] text-stone-400">Shop Owner</span>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate class="p-1.5 rounded-lg text-stone-500 hover:text-amber-400 hover:bg-stone-800 transition-colors" title="Back to Launcher">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
            </a>
        </div>
    </aside>

    <!-- MOBILE HEADER BAR (Visible on screens smaller than md) -->
    <div class="md:hidden flex items-center justify-between p-4 bg-stone-900 border-b border-stone-800 sticky top-0 z-40">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-xl bg-amber-500 text-stone-950 font-bold">
                <flux:icon name="book-open" class="size-4" />
            </span>
            <div>
                <h2 class="text-xs font-bold text-white">{{ $shop ? $shop->name : 'Print Shop' }}</h2>
                <span class="text-[10px] text-amber-400 font-semibold">Thesis Binding Module</span>
            </div>
        </div>

        <button wire:click="toggleMobileMenu" class="p-2 rounded-xl bg-stone-800 text-stone-300 hover:text-white">
            <flux:icon name="bars-3" class="size-5" />
        </button>
    </div>

    <!-- MOBILE SLIDE-OVER DRAWER MENU -->
    @if ($mobile_menu_open)
        <div class="fixed inset-0 z-50 flex bg-stone-950/80 backdrop-blur-md md:hidden animate-fadeIn">
            <div class="w-4/5 max-w-xs bg-stone-900 h-full p-5 space-y-6 flex flex-col justify-between border-r border-stone-800 overflow-y-auto">
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-stone-800 pb-3">
                        <span class="text-xs font-extrabold text-white">Navigation Menu</span>
                        <button wire:click="toggleMobileMenu" class="p-1 text-stone-400 hover:text-white">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>

                    <div class="space-y-2">
                        <span class="text-[10px] font-extrabold text-stone-400 uppercase tracking-wider">Module Tools</span>
                        <nav class="space-y-1">
                            <button wire:click="setTab('pricing')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold {{ $active_tab === 'pricing' ? 'bg-amber-500 text-stone-950' : 'text-stone-300' }}">1. Pricing & Setup</button>
                            <button wire:click="setTab('variants')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold {{ $active_tab === 'variants' ? 'bg-amber-500 text-stone-950' : 'text-stone-300' }}">2. Product Variants</button>
                            <button wire:click="setTab('bom')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold {{ $active_tab === 'bom' ? 'bg-amber-500 text-stone-950' : 'text-stone-300' }}">3. BOM & Inventory Link</button>
                            <button wire:click="setTab('production')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold {{ $active_tab === 'production' ? 'bg-amber-500 text-stone-950' : 'text-stone-300' }}">4. Production Limits</button>
                            <button wire:click="setTab('customer_form')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-bold {{ $active_tab === 'customer_form' ? 'bg-amber-500 text-stone-950' : 'text-stone-300' }}">5. Customer Form Rules</button>
                        </nav>
                    </div>

                    <div class="space-y-2 border-t border-stone-800 pt-4">
                        <span class="text-[10px] font-extrabold text-stone-400 uppercase tracking-wider">System Apps</span>
                        <nav class="space-y-1">
                            <a href="{{ route('dashboard') }}" wire:navigate class="block px-3 py-2 text-xs text-stone-400 hover:text-white">App Launcher</a>
                            <a href="{{ route('owner.inventory-hub') }}" wire:navigate class="block px-3 py-2 text-xs text-stone-400 hover:text-white">Inventory Hub</a>
                            <a href="{{ route('owner.web-builder') }}" wire:navigate class="block px-3 py-2 text-xs text-stone-400 hover:text-white">Web Builder</a>
                        </nav>
                    </div>
                </div>

                <div class="border-t border-stone-800 pt-3 flex items-center gap-2">
                    <span class="size-7 rounded-full bg-amber-500 text-stone-950 text-xs font-bold flex items-center justify-center">{{ $user?->initials() }}</span>
                    <span class="text-xs font-bold text-white">{{ $user?->name }}</span>
                </div>
            </div>
            <div class="flex-1" wire:click="toggleMobileMenu"></div>
        </div>
    @endif

    <!-- MAIN WORKSPACE CONTENT AREA -->
    <main class="flex-1 h-screen overflow-y-auto no-scrollbar p-6 sm:p-10 max-w-7xl w-full min-w-0 relative z-20 space-y-8 pb-20">

        <!-- Top Header Notification Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-stone-800/80 gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-white tracking-tight">
                        @if ($active_tab === 'pricing')
                            General Setup & Pricing Configuration
                        @elseif ($active_tab === 'variants')
                            Product Variants & Design Options
                        @elseif ($active_tab === 'bom')
                            Bill of Materials (BOM) & Inventory Integration
                        @elseif ($active_tab === 'production')
                            Production Limits & Job Scheduling
                        @else
                            Customer Form Requirements & Custom Fields
                        @endif
                    </h1>
                </div>
                <p class="text-xs text-stone-400">Hardbound & Softbound Thesis Binding Service Workspace</p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30' }}">
                    Status: {{ $is_active ? 'ONLINE' : 'OFFLINE' }}
                </span>
            </div>
        </div>

        @if ($statusMessage)
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-semibold flex items-center justify-between animate-fadeIn">
                <div class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 text-amber-400" />
                    <span>{{ $statusMessage }}</span>
                </div>
                <button wire:click="$set('statusMessage', null)" class="text-amber-400 hover:text-white text-xs">Dismiss</button>
            </div>
        @endif

        <!-- TAB 1: General Setup & Pricing -->
        @if ($active_tab === 'pricing')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Pricing Controls -->
                <div class="lg:col-span-2 rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                        <div>
                            <h3 class="text-base font-extrabold text-white">General Setup & Pricing Rates</h3>
                            <p class="text-xs text-stone-400">Control service availability, binding rates, print page fees, and rush charges</p>
                        </div>
                    </div>

                    <form wire:submit.prevent="savePricing" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Hardbound Base Price -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-stone-300">Base Hardbound Binding Price (₱)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">₱</span>
                                    <input
                                        wire:model="hardbound_base_price"
                                        type="number"
                                        step="0.50"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                                <p class="text-[10px] text-stone-500">Fixed binding charge per hardbound book</p>
                            </div>

                            <!-- Softbound Base Price -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-stone-300">Base Softbound Binding Price (₱)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">₱</span>
                                    <input
                                        wire:model="softbound_base_price"
                                        type="number"
                                        step="0.50"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                                <p class="text-[10px] text-stone-500">Fixed binding charge per softbound book</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-stone-800/80 pt-4">
                            <!-- B&W Page Price -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-stone-300">Print Price per Page: Black & White (₱)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">₱</span>
                                    <input
                                        wire:model="page_price_bw"
                                        type="number"
                                        step="0.10"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                                <p class="text-[10px] text-stone-500">Charged for every monochrome page printed</p>
                            </div>

                            <!-- Color Page Price -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-stone-300">Print Price per Page: Colored (₱)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">₱</span>
                                    <input
                                        wire:model="page_price_color"
                                        type="number"
                                        step="0.10"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                                <p class="text-[10px] text-stone-500">Charged for every full-color page printed</p>
                            </div>
                        </div>

                        <!-- Customer Supplied Paper (Cover-Only) Mode -->
                        <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-white block">Allow Pre-Printed Customer Pages (Cover-Only Binding)</span>
                                    <span class="text-[10px] text-stone-400">Accept jobs where students already printed their pages and only pay for hardbound cover & stamping</span>
                                </div>
                                <input wire:model.live="allow_customer_supplied_paper" type="checkbox" class="rounded accent-amber-500 size-5 cursor-pointer" />
                            </div>

                            @if ($allow_customer_supplied_paper)
                                <div class="space-y-1.5 pt-2 border-t border-stone-800/80">
                                    <label class="text-xs font-bold text-amber-400">Base Hardbound Cover & Binding Only Price (₱)</label>
                                    <div class="relative max-w-xs">
                                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">₱</span>
                                        <input
                                            wire:model="hardbound_cover_only_price"
                                            type="number"
                                            step="0.50"
                                            min="0"
                                            class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-stone-900 border border-stone-700 text-stone-100 text-sm focus:border-amber-500 focus:outline-none font-bold"
                                        />
                                    </div>
                                    <p class="text-[10px] text-stone-500">Fixed rate when paper printing is ₱0.00</p>
                                </div>
                            @endif
                        </div>

                        <!-- Rush Order Fee -->
                        <div class="space-y-1.5 border-t border-stone-800/80 pt-4">
                            <label class="text-xs font-bold text-stone-300">Rush Order Express Fee (₱)</label>
                            <div class="relative max-w-sm">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-stone-500 font-bold text-xs">+₱</span>
                                <input
                                    wire:model="rush_fee"
                                    type="number"
                                    step="1.00"
                                    min="0"
                                    class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                            <p class="text-[10px] text-stone-500">Additional surcharge added when customer selects expedited rush processing</p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-extrabold shadow-md shadow-amber-500/20 transition-all">
                                Save Pricing & Setup
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Customer Live Price Estimator Preview -->
                <div class="rounded-3xl border border-amber-500/30 bg-gradient-to-b from-stone-900 to-stone-950 p-6 space-y-5 shadow-2xl flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 border-b border-stone-800 pb-3">
                            <span class="p-1.5 rounded-lg bg-amber-500/20 text-amber-400">
                                <flux:icon name="calculator" class="size-4" />
                            </span>
                            <div>
                                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-wider">Live Price Simulator</h4>
                                <p class="text-[11px] text-stone-400">Real-time customer checkout calculation</p>
                            </div>
                        </div>

                        <!-- Test Mode -->
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-stone-300">Package Mode</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    wire:click="$set('test_fulfillment_type', 'full_package')"
                                    class="py-1.5 rounded-lg text-[10px] font-bold transition-all {{ $test_fulfillment_type === 'full_package' ? 'bg-amber-500 text-stone-950' : 'bg-stone-900 text-stone-400 border border-stone-800' }}"
                                >
                                    Full Print & Bind
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('test_fulfillment_type', 'cover_only')"
                                    class="py-1.5 rounded-lg text-[10px] font-bold transition-all {{ $test_fulfillment_type === 'cover_only' ? 'bg-amber-500 text-stone-950' : 'bg-stone-900 text-stone-400 border border-stone-800' }}"
                                >
                                    Cover Only
                                </button>
                            </div>
                        </div>

                        @if ($test_fulfillment_type === 'full_package')
                            <!-- Test Binding Type -->
                            <div class="space-y-1">
                                <label class="text-[11px] font-bold text-stone-300">Binding Type</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        wire:click="$set('test_binding_type', 'hardbound')"
                                        class="py-1.5 rounded-lg text-xs font-bold transition-all {{ $test_binding_type === 'hardbound' ? 'bg-amber-500 text-stone-950' : 'bg-stone-900 text-stone-400 border border-stone-800' }}"
                                    >
                                        Hardbound
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="$set('test_binding_type', 'softbound')"
                                        class="py-1.5 rounded-lg text-xs font-bold transition-all {{ $test_binding_type === 'softbound' ? 'bg-amber-500 text-stone-950' : 'bg-stone-900 text-stone-400 border border-stone-800' }}"
                                    >
                                        Softbound
                                    </button>
                                </div>
                            </div>

                            <!-- Page Sliders -->
                            <div class="space-y-3 pt-2">
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-stone-400">B&W Pages:</span>
                                        <span class="font-bold text-white">{{ $test_bw_pages }} pages</span>
                                    </div>
                                    <input wire:model.live="test_bw_pages" type="range" min="0" max="300" class="w-full accent-amber-500 bg-stone-950" />
                                </div>

                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-stone-400">Colored Pages:</span>
                                        <span class="font-bold text-amber-400">{{ $test_color_pages }} pages</span>
                                    </div>
                                    <input wire:model.live="test_color_pages" type="range" min="0" max="200" class="w-full accent-amber-500 bg-stone-950" />
                                </div>
                            </div>
                        @else
                            <div class="p-3 rounded-xl bg-stone-950 border border-stone-800 text-[11px] text-stone-400 space-y-1">
                                <div class="text-amber-400 font-bold">📦 Customer-Supplied Pages</div>
                                <p>Pages printed elsewhere. Printing charges are <strong>₱0.00</strong>. Base cover & stamping applied.</p>
                            </div>
                        @endif

                        <!-- Rush Checkbox -->
                        <div class="flex items-center gap-2 pt-2">
                            <input wire:model.live="test_is_rush" type="checkbox" id="testRush" class="rounded accent-amber-500 bg-stone-950 border-stone-800 size-4" />
                            <label for="testRush" class="text-xs text-stone-300 font-bold">Apply Rush Order (+₱{{ number_format($rush_fee, 2) }})</label>
                        </div>
                    </div>

                    <!-- Breakdown Box -->
                    <div class="rounded-2xl bg-stone-950 p-4 border border-stone-800/80 space-y-2">
                        <div class="flex justify-between text-[11px] text-stone-400">
                            <span>Base {{ $test_fulfillment_type === 'cover_only' ? 'Cover Only' : ucfirst($test_binding_type) }}:</span>
                            <span class="text-stone-200">₱{{ number_format($baseSelected, 2) }}</span>
                        </div>
                        @if ($test_fulfillment_type === 'full_package')
                            <div class="flex justify-between text-[11px] text-stone-400">
                                <span>B&W Pages ({{ $test_bw_pages }} &times; ₱{{ number_format($page_price_bw, 2) }}):</span>
                                <span class="text-stone-200">₱{{ number_format($bwTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-stone-400">
                                <span>Color Pages ({{ $test_color_pages }} &times; ₱{{ number_format($page_price_color, 2) }}):</span>
                                <span class="text-stone-200">₱{{ number_format($colorTotal, 2) }}</span>
                            </div>
                        @endif
                        @if ($test_is_rush)
                            <div class="flex justify-between text-[11px] text-amber-400">
                                <span>Rush Fee:</span>
                                <span>+₱{{ number_format($rushFeeTotal, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center border-t border-stone-800 pt-2 text-sm font-extrabold text-white">
                            <span>Total Estimated Price:</span>
                            <span class="text-lg text-amber-400">₱{{ number_format($estimatedTotalPrice, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: Product Variants -->
        @if ($active_tab === 'variants')
            <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-8 shadow-xl backdrop-blur-md">
                <div>
                    <h3 class="text-base font-extrabold text-white">Product Variants & Design Options</h3>
                    <p class="text-xs text-stone-400">Configure cover choices, foil text colors, and paper dimension standards presented in the customer checkout dropdown</p>
                </div>

                <!-- 1. Cover Colors -->
                <div class="space-y-3 border-t border-stone-800 pt-4">
                    <label class="text-xs font-bold text-stone-200 flex items-center gap-2">
                        <flux:icon name="swatch" class="size-4 text-amber-400" />
                        Available Cover Colors
                    </label>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($cover_colors as $index => $color)
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-stone-950 border border-stone-800 text-xs font-semibold text-stone-200">
                                <span class="size-3 rounded-full bg-amber-500/30 border border-amber-400"></span>
                                {{ $color }}
                                <button type="button" wire:click="removeCoverColor({{ $index }})" class="text-stone-500 hover:text-red-400 transition-colors">
                                    <flux:icon name="x-mark" class="size-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2 max-w-md pt-1">
                        <input
                            wire:model="new_color_input"
                            wire:keydown.enter.prevent="addCoverColor"
                            type="text"
                            placeholder="Type color (e.g. Navy Blue, Matte Black)..."
                            class="flex-1 px-4 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none"
                        />
                        <button type="button" wire:click="addCoverColor" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-bold transition-all">
                            + Add Color
                        </button>
                    </div>
                </div>

                <!-- 2. Foil Text Colors (For Hardbound) -->
                <div class="space-y-3 border-t border-stone-800 pt-6">
                    <div>
                        <label class="text-xs font-bold text-stone-200 flex items-center gap-2">
                            <flux:icon name="sparkles" class="size-4 text-amber-400" />
                            Foil Text Stamping Colors (For Hardbound Covers)
                        </label>
                        <p class="text-[10px] text-stone-400">Select which metallic foil text options customers can select for title embossing</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @php
                            $presetFoilOptions = ['Gold', 'Silver', 'Bronze', 'Rose Gold', 'Copper'];
                        @endphp

                        @foreach ($presetFoilOptions as $preset)
                            @php
                                $isFoilSelected = in_array($preset, $foil_colors, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleFoilColor('{{ $preset }}')"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border {{ $isFoilSelected ? 'bg-amber-500/20 border-amber-500 text-amber-300' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                            >
                                <span class="size-2.5 rounded-full {{ $isFoilSelected ? 'bg-amber-400' : 'bg-stone-600' }}"></span>
                                {{ $preset }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Paper Sizes -->
                <div class="space-y-3 border-t border-stone-800 pt-6">
                    <div>
                        <label class="text-xs font-bold text-stone-200 flex items-center gap-2">
                            <flux:icon name="document-text" class="size-4 text-amber-400" />
                            Allowed Paper Sizes
                        </label>
                        <p class="text-[10px] text-stone-400">Select standard manuscript paper dimensions accepted for binding</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @php
                            $presetPaperSizes = ['A4', 'Letter (Short)', 'Legal (Long)'];
                        @endphp

                        @foreach ($presetPaperSizes as $size)
                            @php
                                $isSizeSelected = in_array($size, $paper_sizes, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="togglePaperSize('{{ $size }}')"
                                class="p-4 rounded-2xl border text-left transition-all space-y-1 {{ $isSizeSelected ? 'bg-amber-500/10 border-amber-500/50 text-white' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold">{{ $size }}</span>
                                    <flux:icon name="{{ $isSizeSelected ? 'check-circle' : 'plus-circle' }}" class="size-4 {{ $isSizeSelected ? 'text-amber-400' : 'text-stone-600' }}" />
                                </div>
                                <p class="text-[10px] text-stone-500">
                                    @if ($size === 'A4')
                                        210 &times; 297 mm (Standard Thesis Format)
                                    @elseif ($size === 'Letter (Short)')
                                        8.5 &times; 11 inches
                                    @else
                                        8.5 &times; 14 inches
                                    @endif
                                </p>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 3: Bill of Materials (BOM) & Inventory Link -->
        @if ($active_tab === 'bom')
            <div class="space-y-6">
                <!-- Header Banner & Automated Deduction Switch -->
                <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-4 shadow-xl backdrop-blur-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                <flux:icon name="cube" class="size-5 text-amber-400" />
                                Bill of Materials (BOM) & Recipe Formulation
                            </h3>
                            <a href="{{ route('owner.inventory-hub') }}" wire:navigate class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-bold hover:bg-emerald-500/20 transition-all flex items-center gap-1">
                                <flux:icon name="archive-box" class="size-3.5" />
                                <span>Central Inventory Hub</span>
                            </a>
                        </div>
                        <p class="text-xs text-stone-400 mt-1">Select raw materials from Central Inventory to formulate exact consumption recipes per book copy</p>
                    </div>

                    <!-- Automated Deduction Toggle -->
                    <div class="flex items-center gap-3 bg-stone-950 p-3 rounded-2xl border border-stone-800 shrink-0">
                        <div>
                            <span class="text-xs font-bold block text-stone-200">Automated Deduction</span>
                            <span class="text-[10px] text-stone-400">Deduct stock when marked "Done"</span>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleAutoDeductInventory"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $auto_deduct_inventory ? 'bg-amber-500' : 'bg-stone-700' }}"
                        >
                            <span class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $auto_deduct_inventory ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                </div>

                <!-- Add BOM Link Form -->
                <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-4 shadow-xl backdrop-blur-md">
                    <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                        <flux:icon name="plus-circle" class="size-4" />
                        Add Material Usage Rule
                    </h4>

                    <form wire:submit.prevent="addBomItem" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <!-- Inventory Material Selector -->
                        <div class="sm:col-span-2 space-y-1.5">
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-stone-300">Material Selector (Linked to Inventory)</label>
                                <button type="button" wire:click="openAddInventoryModal" class="text-[10px] text-amber-400 hover:text-amber-300 font-bold">
                                    + New Material Item
                                </button>
                            </div>
                            <select
                                wire:model.live="selected_inventory_item_id"
                                class="w-full px-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none"
                            >
                                @foreach ($inventoryList as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} (Stock: {{ number_format($item->stock_qty, 1) }} {{ $item->unit }}) &ndash; {{ $item->category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Target Binding Type -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-stone-300">Binding Target</label>
                            <select
                                wire:model="bom_binding_type"
                                class="w-full px-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none"
                            >
                                <option value="hardbound">Hardbound Only</option>
                                <option value="softbound">Softbound Only</option>
                                <option value="both">Both Hardbound & Softbound</option>
                            </select>
                        </div>

                        <!-- Usage per Book -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-stone-300">Usage Quantity per Book</label>
                            <div class="flex items-center gap-2">
                                <input
                                    wire:model="bom_usage_qty"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-3 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none"
                                />
                                <span class="text-xs text-stone-400 font-bold shrink-0 min-w-10">{{ $bom_unit }}</span>
                            </div>
                        </div>

                        <div class="sm:col-span-4 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-extrabold shadow-md transition-all">
                                Save BOM Rule
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing BOM Rules Table -->
                <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-4 shadow-xl backdrop-blur-md overflow-x-auto">
                    <h4 class="text-xs font-extrabold text-stone-300 uppercase tracking-wider">Active Bill of Materials Rules</h4>

                    @if ($bomList->isEmpty())
                        <div class="p-6 text-center text-xs text-stone-500 border border-dashed border-stone-800 rounded-2xl">
                            No inventory materials linked yet. Use the form above to link materials (e.g. 2 pcs Chipboard per 1 Hardbound).
                        </div>
                    @else
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-stone-800 text-stone-400 font-bold uppercase text-[10px]">
                                    <th class="py-3 px-4">Material Name</th>
                                    <th class="py-3 px-4">Category</th>
                                    <th class="py-3 px-4">Binding Target</th>
                                    <th class="py-3 px-4">Usage per Book</th>
                                    <th class="py-3 px-4">Current Stock</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-800/60 text-stone-200">
                                @foreach ($bomList as $bom)
                                    <tr>
                                        <td class="py-3 px-4 font-bold text-white">{{ $bom->inventoryItem?->name ?? 'Material #'.$bom->inventory_item_id }}</td>
                                        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-md bg-stone-950 border border-stone-800 text-[10px] text-stone-400">{{ $bom->inventoryItem?->category }}</span></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded-md font-bold text-[10px] {{ $bom->binding_type === 'hardbound' ? 'bg-amber-500/20 text-amber-300' : ($bom->binding_type === 'softbound' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300') }}">
                                                {{ ucfirst($bom->binding_type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-amber-400">{{ number_format($bom->usage_qty, 2) }} {{ $bom->unit }}</td>
                                        <td class="py-3 px-4 font-bold text-stone-300">{{ number_format($bom->inventoryItem?->stock_qty ?? 0, 1) }} {{ $bom->inventoryItem?->unit }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <button wire:click="deleteBomItem({{ $bom->id }})" class="text-stone-500 hover:text-red-400 transition-colors">
                                                <flux:icon name="trash" class="size-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- Related Ready-to-Buy Thesis Add-on Products -->
                <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-4 shadow-xl backdrop-blur-md">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-extrabold text-stone-300 uppercase tracking-wider flex items-center gap-2">
                                <flux:icon name="tag" class="size-4 text-cyan-400" />
                                Related Ready-to-Buy Thesis Add-ons
                            </h4>
                            <p class="text-[11px] text-stone-400">Retail products (certificate holders, plastic covers, CD pockets) offered to thesis binding clients</p>
                        </div>
                        <a href="{{ route('owner.inventory-hub') }}" wire:navigate class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                            <span>Manage in Central Inventory Hub</span>
                            <flux:icon name="arrow-right" class="size-3" />
                        </a>
                    </div>

                    @if ($addOnsList->isEmpty())
                        <div class="p-6 text-center text-xs text-stone-500 border border-dashed border-stone-800 rounded-2xl">
                            No ready-to-sell add-on products currently tagged for Thesis Binding. You can add items in the Central Inventory Hub under 'Ready-to-Sell' with Service: Thesis Binding.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach ($addOnsList as $addon)
                                <div class="p-4 rounded-2xl bg-stone-950/70 border border-stone-800/80 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="space-y-0.5">
                                            <div class="text-xs font-bold text-white leading-tight">{{ $addon->name }}</div>
                                            <div class="text-[10px] text-stone-500 font-mono">{{ $addon->sku ?? 'NO-SKU' }}</div>
                                        </div>
                                        @if ($addon->isOutOfStock())
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-red-500/20 text-red-400 border border-red-500/30 shrink-0">Out of Stock</span>
                                        @elseif ($addon->isLowStock())
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0">Low ({{ $addon->stock_qty }})</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shrink-0">{{ $addon->stock_qty }} {{ $addon->unit }}</span>
                                        @endif
                                    </div>
                                    <div class="flex justify-between items-center text-xs pt-1 border-t border-stone-800/60">
                                        <span class="text-stone-500 text-[11px]">Cost: ₱{{ number_format($addon->unit_cost, 2) }}</span>
                                        <strong class="text-cyan-300">Sell: ₱{{ number_format($addon->selling_price ?? 0, 2) }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- TAB 4: Production Limits & Scheduling -->
        @if ($active_tab === 'production')
            <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-6 shadow-xl backdrop-blur-md max-w-3xl">
                <div>
                    <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                        <flux:icon name="clock" class="size-5 text-amber-400" />
                        Production Capacity Quotas & Lead Times
                    </h3>
                    <p class="text-xs text-stone-400">Integrates with Demand Forecasting & Job Scheduling to prevent shop capacity overload</p>
                </div>

                <form wire:submit.prevent="saveProductionLimits" class="space-y-6 border-t border-stone-800 pt-4">
                    <!-- Daily Production Quota -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-stone-200">Daily Production Quota Limit (Orders / Day)</label>
                        <div class="relative max-w-sm">
                            <input
                                wire:model="daily_production_quota"
                                type="number"
                                min="1"
                                class="w-full px-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <p class="text-[10px] text-stone-500">Maximum thesis binding orders staff can process per day before capping slot availability</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-stone-800/80 pt-4">
                        <!-- Standard Lead Time -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-stone-200">Standard Lead Time (Business Days)</label>
                            <div class="relative">
                                <input
                                    wire:model="standard_lead_time_days"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                            <p class="text-[10px] text-stone-500">Normal processing turnaround time (e.g. 3 to 5 days)</p>
                        </div>

                        <!-- Rush Lead Time -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-stone-200">Rush Order Lead Time (Business Days)</label>
                            <div class="relative">
                                <input
                                    wire:model="rush_lead_time_days"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-sm focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                            <p class="text-[10px] text-stone-500">Expedited rush turnaround time (e.g. 1 day)</p>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-stone-800 pt-4">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-extrabold shadow-md transition-all">
                            Save Production Limits & Scheduling
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- TAB 5: Customer Form Requirements -->
        @if ($active_tab === 'customer_form')
            <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 space-y-8 shadow-xl backdrop-blur-md max-w-4xl">
                <div>
                    <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                        <flux:icon name="document-text" class="size-5 text-amber-400" />
                        Customer Order Form Requirements
                    </h3>
                    <p class="text-xs text-stone-400">Define the mandatory files and cover text metadata customers must submit prior to checkout</p>
                </div>

                <!-- 1. Mandatory PDF File Upload -->
                <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-200 block">Require Manuscript PDF File Upload</span>
                        <span class="text-[10px] text-stone-400">Forces customer to attach print-ready document file before adding to cart</span>
                    </div>

                    <button
                        type="button"
                        wire:click="toggleRequirePdfUpload"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $require_pdf_upload ? 'bg-amber-500' : 'bg-stone-700' }}"
                    >
                        <span class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $require_pdf_upload ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <!-- 2. Custom Cover Text Fields Tag Builder -->
                <div class="space-y-4 border-t border-stone-800 pt-6">
                    <div>
                        <label class="text-xs font-bold text-stone-200 block">Custom Cover Text Metadata Fields</label>
                        <p class="text-[10px] text-stone-400">Admin-defined input prompts for gold/silver cover foil stamping (e.g. Title, Author Names, School Year)</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($custom_cover_fields as $index => $field)
                            <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-xs font-bold text-amber-400">
                                <flux:icon name="tag" class="size-3.5" />
                                {{ $field }}
                                <button type="button" wire:click="removeCustomField({{ $index }})" class="text-stone-500 hover:text-red-400 transition-colors">
                                    <flux:icon name="x-mark" class="size-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2 max-w-md">
                        <input
                            wire:model="new_field_input"
                            wire:keydown.enter.prevent="addCustomField"
                            type="text"
                            placeholder="Add cover field (e.g. Panel Advisor, School Name)..."
                            class="flex-1 px-4 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none"
                        />
                        <button type="button" wire:click="addCustomField" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-bold transition-all">
                            + Add Field Tag
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </main>

    <!-- Quick Add Inventory Item Modal -->
    @if ($show_add_inventory_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-lg rounded-3xl border border-stone-800 bg-stone-900 p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between border-b border-stone-800 pb-3">
                    <h3 class="text-base font-extrabold text-white">Create New Inventory Material</h3>
                    <button wire:click="closeAddInventoryModal" class="size-8 rounded-full bg-stone-800 text-stone-400 hover:text-white flex items-center justify-center">
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </div>

                <form wire:submit.prevent="saveQuickInventoryItem" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-300">Material Name *</label>
                        <input wire:model="new_inv_name" type="text" placeholder="e.g. Premium Leatherette Dark Blue" class="w-full px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Category</label>
                            <select wire:model="new_inv_category" class="w-full px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none">
                                <option value="Raw Material">Raw Material</option>
                                <option value="Paper">Paper</option>
                                <option value="Foil">Foil</option>
                                <option value="Adhesive">Adhesive</option>
                                <option value="Packaging">Packaging</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Unit (pcs, sheets, kg)</label>
                            <input wire:model="new_inv_unit" type="text" placeholder="pcs" class="w-full px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Initial Stock Qty</label>
                            <input wire:model="new_inv_stock_qty" type="number" step="0.1" class="w-full px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Reorder Level Alert</label>
                            <input wire:model="new_inv_reorder_level" type="number" step="0.1" class="w-full px-3.5 py-2 rounded-xl bg-stone-950 border border-stone-800 text-stone-100 text-xs focus:border-amber-500 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-stone-800">
                        <button type="button" wire:click="closeAddInventoryModal" class="px-4 py-2 rounded-xl bg-stone-800 text-stone-300 text-xs font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-extrabold">Create Material</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
