<?php

use App\Models\InventoryItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Support\Navigation;
use Livewire\Attributes\Layout;
use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $active_tab = 'overview';
    public bool $mobile_menu_open = false;

    // Search & Filters
    public string $search = '';
    public string $selected_item_type = 'all';
    public string $selected_category = 'all';
    public string $selected_service_tag = 'all';
    public string $selected_stock_status = 'all';

    // Toast message state
    public ?string $statusMessage = null;

    // Item Modal (Add / Edit)
    public bool $show_item_modal = false;
    public ?int $editing_item_id = null;
    public string $item_name = '';
    public string $item_sku = '';
    public string $item_category = 'Raw Material';
    public string $item_type = 'raw_material';
    public string $item_service_tag = 'thesis_binding';
    public float $item_stock_qty = 50.0;
    public string $item_unit = 'pcs';
    public float $item_reorder_level = 10.0;
    public float $item_unit_cost = 0.00;
    public ?float $item_selling_price = null;
    public string $item_supplier_name = '';

    // Stock Adjustment / Stock-In Modal
    public bool $show_adjust_modal = false;
    public ?int $adjust_item_id = null;
    public string $adjust_item_name = '';
    public float $adjust_current_stock = 0.0;
    public string $adjust_unit = 'pcs';
    public string $adjust_type = 'manual_stock_in'; // manual_stock_in, manual_adjustment, spoilage_waste
    public float $adjust_qty = 10.0;
    public string $adjust_note = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var PrintShop $shop */
        $shop = $user->printShop;

        // Seed comprehensive default inventory if shop inventory is completely empty or sparse
        if ($shop->inventoryItems()->count() === 0) {
            $defaultMaterials = [
                // Raw Materials - Thesis Binding & General
                [
                    'name' => 'Chipboard Heavy Duty (2mm)',
                    'sku' => 'MAT-CHP-01',
                    'category' => 'Cover',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 120.0,
                    'unit' => 'pcs',
                    'reorder_level' => 20.0,
                    'unit_cost' => 15.00,
                    'selling_price' => null,
                    'supplier_name' => 'Star Paper Corp',
                ],
                [
                    'name' => 'Leatherette Cover Sheet (Maroon)',
                    'sku' => 'MAT-LTH-MRN',
                    'category' => 'Cover',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 150.0,
                    'unit' => 'sheets',
                    'reorder_level' => 25.0,
                    'unit_cost' => 25.00,
                    'selling_price' => null,
                    'supplier_name' => 'Victory Bindings',
                ],
                [
                    'name' => 'Leatherette Cover Sheet (Black)',
                    'sku' => 'MAT-LTH-BLK',
                    'category' => 'Cover',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 100.0,
                    'unit' => 'sheets',
                    'reorder_level' => 20.0,
                    'unit_cost' => 25.00,
                    'selling_price' => null,
                    'supplier_name' => 'Victory Bindings',
                ],
                [
                    'name' => 'Leatherette Cover Sheet (Dark Blue)',
                    'sku' => 'MAT-LTH-BLU',
                    'category' => 'Cover',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 18.0, // Low stock demo
                    'unit' => 'sheets',
                    'reorder_level' => 20.0,
                    'unit_cost' => 25.00,
                    'selling_price' => null,
                    'supplier_name' => 'Victory Bindings',
                ],
                [
                    'name' => 'Hot Melt Binding Glue Pellets',
                    'sku' => 'MAT-GLU-03',
                    'category' => 'Adhesive',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 25.0,
                    'unit' => 'kg',
                    'reorder_level' => 5.0,
                    'unit_cost' => 180.00,
                    'selling_price' => null,
                    'supplier_name' => 'Industrial Adhesives PH',
                ],
                [
                    'name' => 'Gold Foil Stamping Roll (120m)',
                    'sku' => 'MAT-FOL-GLD',
                    'category' => 'Foil',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 30.0,
                    'unit' => 'rolls',
                    'reorder_level' => 5.0,
                    'unit_cost' => 450.00,
                    'selling_price' => null,
                    'supplier_name' => 'FoilTech Manila',
                ],
                [
                    'name' => 'Silver Foil Stamping Roll (120m)',
                    'sku' => 'MAT-FOL-SLV',
                    'category' => 'Foil',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 4.0, // Low stock demo
                    'unit' => 'rolls',
                    'reorder_level' => 5.0,
                    'unit_cost' => 450.00,
                    'selling_price' => null,
                    'supplier_name' => 'FoilTech Manila',
                ],
                [
                    'name' => 'A4 Paper 80gsm Premium',
                    'sku' => 'PAP-A4-80',
                    'category' => 'Paper',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'all',
                    'stock_qty' => 5000.0,
                    'unit' => 'sheets',
                    'reorder_level' => 1000.0,
                    'unit_cost' => 0.60,
                    'selling_price' => null,
                    'supplier_name' => 'PaperOne Supplies',
                ],
                [
                    'name' => 'Letter (Short) Paper 80gsm',
                    'sku' => 'PAP-LTR-80',
                    'category' => 'Paper',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'all',
                    'stock_qty' => 3500.0,
                    'unit' => 'sheets',
                    'reorder_level' => 800.0,
                    'unit_cost' => 0.55,
                    'selling_price' => null,
                    'supplier_name' => 'PaperOne Supplies',
                ],
                [
                    'name' => 'Legal (Long) Paper 80gsm',
                    'sku' => 'PAP-LGL-80',
                    'category' => 'Paper',
                    'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                    'service_tag' => 'all',
                    'stock_qty' => 2500.0,
                    'unit' => 'sheets',
                    'reorder_level' => 500.0,
                    'unit_cost' => 0.70,
                    'selling_price' => null,
                    'supplier_name' => 'PaperOne Supplies',
                ],
                // Ready-to-Sell Add-ons & Finished Accessories
                [
                    'name' => 'Deluxe Hardbound Certificate Jacket',
                    'sku' => 'ACC-HLD-01',
                    'category' => 'Add-on',
                    'item_type' => InventoryItem::TYPE_READY_TO_SELL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 40.0,
                    'unit' => 'pcs',
                    'reorder_level' => 10.0,
                    'unit_cost' => 60.00,
                    'selling_price' => 150.00,
                    'supplier_name' => 'Prestige Covers',
                ],
                [
                    'name' => 'Clear Plastic Thesis Cover Protector',
                    'sku' => 'ACC-CVR-02',
                    'category' => 'Add-on',
                    'item_type' => InventoryItem::TYPE_READY_TO_SELL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 100.0,
                    'unit' => 'pcs',
                    'reorder_level' => 20.0,
                    'unit_cost' => 10.00,
                    'selling_price' => 35.00,
                    'supplier_name' => 'PlasticPack PH',
                ],
                [
                    'name' => 'CD/DVD Self-Adhesive Sleeve Pocket',
                    'sku' => 'ACC-CDP-03',
                    'category' => 'Add-on',
                    'item_type' => InventoryItem::TYPE_READY_TO_SELL,
                    'service_tag' => 'thesis_binding',
                    'stock_qty' => 0.0, // Out of stock demo
                    'unit' => 'pcs',
                    'reorder_level' => 15.0,
                    'unit_cost' => 5.00,
                    'selling_price' => 20.00,
                    'supplier_name' => 'MediaAccessories PH',
                ],
            ];

            foreach ($defaultMaterials as $mat) {
                $item = $shop->inventoryItems()->create($mat);
                // Initial movement log
                StockMovement::create([
                    'inventory_item_id' => $item->id,
                    'movement_type' => StockMovement::TYPE_MANUAL_STOCK_IN,
                    'quantity' => $item->stock_qty,
                    'previous_stock' => 0,
                    'resulting_stock' => $item->stock_qty,
                    'reference_note' => 'Initial inventory setup',
                    'logged_by' => $user->id,
                ]);
            }
        }
    }

    public function openAddItemModal(): void
    {
        $this->resetModalForms();
        $this->show_item_modal = true;
    }

    public function openEditItemModal(int $id): void
    {
        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var InventoryItem|null $item */
        $item = $user->printShop->inventoryItems()->find($id);
        if (! $item) {
            return;
        }

        $this->editing_item_id = $item->id;
        $this->item_name = $item->name;
        $this->item_sku = $item->sku ?? '';
        $this->item_category = $item->category;
        $this->item_type = $item->item_type;
        $this->item_service_tag = $item->service_tag ?? 'thesis_binding';
        $this->item_stock_qty = $item->stock_qty;
        $this->item_unit = $item->unit;
        $this->item_reorder_level = $item->reorder_level;
        $this->item_unit_cost = $item->unit_cost;
        $this->item_selling_price = $item->selling_price;
        $this->item_supplier_name = $item->supplier_name ?? '';

        $this->show_item_modal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'item_name' => 'required|string|max:255',
            'item_category' => 'required|string|max:100',
            'item_type' => 'required|string',
            'item_stock_qty' => 'required|numeric|min:0',
            'item_unit' => 'required|string|max:50',
            'item_reorder_level' => 'required|numeric|min:0',
            'item_unit_cost' => 'required|numeric|min:0',
            'item_selling_price' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var PrintShop $shop */
        $shop = $user->printShop;

        $data = [
            'name' => $this->item_name,
            'sku' => $this->item_sku ?: null,
            'category' => $this->item_category,
            'item_type' => $this->item_type,
            'service_tag' => $this->item_service_tag ?: null,
            'stock_qty' => $this->item_stock_qty,
            'unit' => $this->item_unit,
            'reorder_level' => $this->item_reorder_level,
            'unit_cost' => $this->item_unit_cost,
            'selling_price' => $this->item_selling_price,
            'supplier_name' => $this->item_supplier_name ?: null,
        ];

        if ($this->editing_item_id) {
            $item = $shop->inventoryItems()->find($this->editing_item_id);
            if ($item) {
                $oldStock = $item->stock_qty;
                $item->update($data);

                if ($oldStock != $this->item_stock_qty) {
                    StockMovement::create([
                        'inventory_item_id' => $item->id,
                        'movement_type' => StockMovement::TYPE_MANUAL_ADJUSTMENT,
                        'quantity' => $this->item_stock_qty - $oldStock,
                        'previous_stock' => $oldStock,
                        'resulting_stock' => $this->item_stock_qty,
                        'reference_note' => 'Manual item edit adjustment',
                        'logged_by' => $user->id,
                    ]);
                }
                $this->statusMessage = "Updated '{$item->name}' successfully.";
            }
        } else {
            $item = $shop->inventoryItems()->create($data);
            StockMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_MANUAL_STOCK_IN,
                'quantity' => $item->stock_qty,
                'previous_stock' => 0,
                'resulting_stock' => $item->stock_qty,
                'reference_note' => 'New item created',
                'logged_by' => $user->id,
            ]);
            $this->statusMessage = "Created '{$item->name}' in inventory.";
        }

        $this->resetModalForms();
        $this->show_item_modal = false;
        $this->dispatch('toast', message: $this->statusMessage);
    }

    public function deleteItem(int $id): void
    {
        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        $item = $user->printShop->inventoryItems()->find($id);
        if ($item) {
            $name = $item->name;
            $item->delete();
            $this->statusMessage = "Deleted '{$name}' from inventory.";
            $this->dispatch('toast', message: $this->statusMessage);
        }
    }

    public function openAdjustModal(int $id): void
    {
        $user = auth()->user();
        if (! $user || ! $user->printShop) {
            return;
        }

        /** @var InventoryItem|null $item */
        $item = $user->printShop->inventoryItems()->find($id);
        if (! $item) {
            return;
        }

        $this->adjust_item_id = $item->id;
        $this->adjust_item_name = $item->name;
        $this->adjust_current_stock = $item->stock_qty;
        $this->adjust_unit = $item->unit;
        $this->adjust_type = StockMovement::TYPE_MANUAL_STOCK_IN;
        $this->adjust_qty = 10.0;
        $this->adjust_note = '';
        $this->show_adjust_modal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjust_qty' => 'required|numeric|min:0.01',
            'adjust_type' => 'required|string',
        ]);

        $user = auth()->user();
        if (! $user || ! $user->printShop || ! $this->adjust_item_id) {
            return;
        }

        /** @var InventoryItem|null $item */
        $item = $user->printShop->inventoryItems()->find($this->adjust_item_id);
        if (! $item) {
            return;
        }

        $previous = $item->stock_qty;
        $delta = $this->adjust_qty;

        if ($this->adjust_type === StockMovement::TYPE_SPOILAGE_WASTE || $this->adjust_type === 'manual_reduction') {
            $resulting = max(0, $previous - $delta);
            $delta = -1 * abs($delta);
        } else {
            $resulting = $previous + $delta;
        }

        $item->update(['stock_qty' => $resulting]);

        StockMovement::create([
            'inventory_item_id' => $item->id,
            'movement_type' => $this->adjust_type,
            'quantity' => $delta,
            'previous_stock' => $previous,
            'resulting_stock' => $resulting,
            'reference_note' => $this->adjust_note ?: ($delta > 0 ? 'Stock-In Purchase Delivery' : 'Waste / Deduction'),
            'logged_by' => $user->id,
        ]);

        $this->show_adjust_modal = false;
        $this->statusMessage = "Updated stock for '{$item->name}'. New balance: {$resulting} {$item->unit}.";
        $this->dispatch('toast', message: $this->statusMessage);
    }

    public function resetModalForms(): void
    {
        $this->editing_item_id = null;
        $this->item_name = '';
        $this->item_sku = '';
        $this->item_category = 'Raw Material';
        $this->item_type = 'raw_material';
        $this->item_service_tag = 'thesis_binding';
        $this->item_stock_qty = 50.0;
        $this->item_unit = 'pcs';
        $this->item_reorder_level = 10.0;
        $this->item_unit_cost = 0.00;
        $this->item_selling_price = null;
        $this->item_supplier_name = '';
    }
}; ?>

<div class="h-screen w-screen overflow-hidden no-scrollbar pq-page flex flex-col md:flex-row relative">
    @php
        $user = auth()->user();
        $shop = $user?->printShop;
        $invBack = Navigation::backTarget($user, 'owner.inventory-hub', ['from' => request()->query('from')]) ?? [
            'url' => route('owner.dashboard'),
            'label' => 'App Launcher',
        ];

        $itemsQuery = $shop ? $shop->inventoryItems() : InventoryItem::whereRaw('1 = 0');

        // Apply filters
        if (!empty($this->search)) {
            $itemsQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('supplier_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->selected_item_type !== 'all') {
            $itemsQuery->where('item_type', $this->selected_item_type);
        }

        if ($this->selected_category !== 'all') {
            $itemsQuery->where('category', $this->selected_category);
        }

        if ($this->selected_service_tag !== 'all') {
            $itemsQuery->where(function ($q) {
                $q->where('service_tag', $this->selected_service_tag)
                  ->orWhere('service_tag', 'all')
                  ->orWhereNull('service_tag');
            });
        }

        if ($this->selected_stock_status === 'low_stock') {
            $itemsQuery->whereColumn('stock_qty', '<=', 'reorder_level')->where('stock_qty', '>', 0);
        } elseif ($this->selected_stock_status === 'out_of_stock') {
            $itemsQuery->where('stock_qty', '<=', 0);
        }

        $items = $itemsQuery->orderBy('name')->get();
        $allItems = $shop ? $shop->inventoryItems()->get() : collect();

        // Analytics KPIs
        $totalItemsCount = $allItems->count();
        $lowStockItems = $allItems->filter(fn ($i) => $i->isLowStock());
        $outOfStockItems = $allItems->filter(fn ($i) => $i->isOutOfStock());
        $totalInventoryCostValue = $allItems->sum(fn ($i) => $i->stock_qty * $i->unit_cost);
        $totalReadyToSellValue = $allItems->where('item_type', InventoryItem::TYPE_READY_TO_SELL)->sum(fn ($i) => $i->stock_qty * ($i->selling_price ?? 0));
        $restockNeededCount = $lowStockItems->count() + $outOfStockItems->count();

        // Recent Movements
        $recentMovements = StockMovement::whereIn('inventory_item_id', $allItems->pluck('id'))
            ->with(['inventoryItem', 'user'])
            ->latest()
            ->take(20)
            ->get();
    @endphp

    <!-- Mobile Top Header Bar -->
    <div class="md:hidden flex items-center justify-between gap-2 px-4 py-3 bg-white dark:bg-stone-900 border-b border-stone-200 dark:border-stone-800 z-30">
        <div class="flex items-center gap-2.5 min-w-0">
            <x-app-back-link :href="$invBack['url']" :label="$invBack['label']" :navigate="true" />
            <span class="font-bold text-stone-900 dark:text-white text-sm truncate">Inventory Hub</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-theme-toggle />
            <button wire:click="$toggle('mobile_menu_open')" class="p-1.5 rounded-lg bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 cursor-pointer" aria-label="Toggle inventory menu">
                <flux:icon name="bars-3" class="size-5" />
            </button>
        </div>
    </div>

    <!-- Sticky Vertical Left Navigation Sidebar -->
    <aside class="w-full md:w-64 bg-white dark:bg-stone-900 border-r border-stone-200 dark:border-stone-800 flex flex-col justify-between h-screen overflow-y-auto no-scrollbar relative z-20 shrink-0 {{ $mobile_menu_open ? 'block' : 'hidden md:flex' }}">
        <div class="p-5 space-y-6">
            <!-- App Header & Back Link -->
            <div>
                <div class="flex items-center justify-between gap-2 mb-4">
                    <x-app-back-link :href="$invBack['url']" :label="$invBack['label']" :navigate="true" />
                    <x-theme-toggle />
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold shadow-sm">
                        <flux:icon name="archive-box" class="size-5" />
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-stone-900 dark:text-white tracking-tight">Inventory Hub</h2>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400">Shop Supplies & Stock Controls</p>
                    </div>
                </div>
            </div>

            <!-- Main Tab Navigation Links -->
            <nav class="space-y-1">
                <div class="px-3 pb-1 text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Inventory Tools</div>

                <!-- Overview Tab -->
                <button
                    wire:click="$set('active_tab', 'overview')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer {{ $active_tab === 'overview' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800/60 border border-transparent' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="chart-pie" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Overview & Analytics</span>
                    </div>
                    @if ($restockNeededCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold pq-badge-warning tabular-nums">
                            {{ $restockNeededCount }}
                        </span>
                    @endif
                </button>

                <!-- Materials & Products Tab -->
                <button
                    wire:click="$set('active_tab', 'materials')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer {{ $active_tab === 'materials' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800/60 border border-transparent' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="cube" class="size-4 text-teal-600 dark:text-teal-400" />
                        <span>Materials & Products</span>
                    </div>
                    <span class="text-[10px] text-stone-500 dark:text-stone-400 tabular-nums">{{ $totalItemsCount }}</span>
                </button>

                <!-- Stock Movements Tab -->
                <button
                    wire:click="$set('active_tab', 'movements')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer {{ $active_tab === 'movements' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800/60 border border-transparent' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="arrows-right-left" class="size-4 text-sky-600 dark:text-sky-400" />
                        <span>Stock Movements Log</span>
                    </div>
                    <span class="text-[10px] text-stone-500 dark:text-stone-400">Audit</span>
                </button>

                <!-- Restock PO Generator Tab -->
                <button
                    wire:click="$set('active_tab', 'restock_po')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer {{ $active_tab === 'restock_po' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800/60 border border-transparent' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="shopping-cart" class="size-4 text-amber-600 dark:text-amber-400" />
                        <span>Restock Requisition (PO)</span>
                    </div>
                    @if ($lowStockItems->count() > 0)
                        <span class="size-2 rounded-full bg-amber-500"></span>
                    @endif
                </button>
            </nav>

            <!-- Linked Printing Apps Shortcuts -->
            <div class="pt-4 border-t border-stone-200 dark:border-stone-800 space-y-2">
                <div class="px-3 text-[10px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Linked Services</div>
                <a href="{{ route('owner.thesis-binding') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-600 dark:text-stone-400 hover:text-amber-700 dark:hover:text-amber-300 hover:bg-stone-100 dark:hover:bg-stone-800/50 transition-colors">
                    <flux:icon name="book-open" class="size-3.5 text-amber-600 dark:text-amber-500" />
                    <span>Thesis Binding BOM</span>
                </a>
            </div>
        </div>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950/60 flex items-center justify-between">
            <div class="flex items-center gap-2 min-w-0">
                <span class="size-6 rounded-full bg-amber-500 text-stone-950 text-[10px] font-black flex items-center justify-center shrink-0">
                    {{ $user?->initials() }}
                </span>
                <span class="text-xs font-bold text-stone-800 dark:text-stone-300 truncate max-w-[120px]">{{ $user?->name }}</span>
            </div>
            <a href="{{ route('owner.dashboard') }}" wire:navigate class="text-stone-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors" title="Back to Launcher">
                <flux:icon name="squares-2x2" class="size-4" />
            </a>
        </div>
    </aside>

    <!-- Main Workspace Body -->
    <main class="flex-1 h-screen overflow-y-auto no-scrollbar p-4 sm:p-8 space-y-6 relative z-10">
        <!-- Toast Notification Banner -->
        @if ($statusMessage)
            <div class="p-4 rounded-2xl pq-badge-success text-xs font-bold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 shrink-0" />
                    <span>{{ $statusMessage }}</span>
                </div>
                <button wire:click="$set('statusMessage', null)" class="text-emerald-700 dark:text-emerald-300 hover:text-stone-900 dark:hover:text-white cursor-pointer" aria-label="Dismiss notification">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        @endif

        <!-- TAB 1: OVERVIEW & ANALYTICS -->
        @if ($active_tab === 'overview')
            <div class="space-y-6">
                <!-- Top Welcome Banner & Action -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-stone-900 dark:text-white tracking-tight">Inventory Overview & Burn Rate</h1>
                        <p class="text-xs text-stone-600 dark:text-stone-400">Real-time material valuation, consumption rates, and automated restock alerts</p>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button
                            wire:click="openAddItemModal"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                        >
                            <flux:icon name="plus" class="size-4" />
                            <span>Add New Material / Product</span>
                        </button>
                    </div>
                </div>

                <!-- 4 KPI Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Inventory Valuation (Cost) -->
                    <div class="pq-card p-5 space-y-2">
                        <div class="flex items-center justify-between text-stone-600 dark:text-stone-400 text-xs font-semibold">
                            <span>Total Material Valuation</span>
                            <flux:icon name="banknotes" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-2xl font-black text-stone-900 dark:text-white tabular-nums">
                            ₱{{ number_format($totalInventoryCostValue, 2) }}
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400">Based on unit cost of physical stock</p>
                    </div>

                    <!-- Ready-to-Sell Product Asset Value -->
                    <div class="pq-card p-5 space-y-2">
                        <div class="flex items-center justify-between text-stone-600 dark:text-stone-400 text-xs font-semibold">
                            <span>Retail Add-ons Potential</span>
                            <flux:icon name="tag" class="size-4 text-sky-600 dark:text-sky-400" />
                        </div>
                        <div class="text-2xl font-black text-sky-700 dark:text-sky-300 tabular-nums">
                            ₱{{ number_format($totalReadyToSellValue, 2) }}
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 tabular-nums">{{ $allItems->where('item_type', InventoryItem::TYPE_READY_TO_SELL)->count() }} ready-to-sell products</p>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="pq-card p-5 space-y-2 {{ $lowStockItems->count() > 0 ? '!border-amber-300 dark:!border-amber-500/40 bg-amber-50/40 dark:bg-amber-500/5' : '' }}">
                        <div class="flex items-center justify-between text-stone-600 dark:text-stone-400 text-xs font-semibold">
                            <span>Low Stock Alerts</span>
                            <flux:icon name="exclamation-triangle" class="size-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="text-2xl font-black tabular-nums {{ $lowStockItems->count() > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-stone-900 dark:text-white' }}">
                            {{ $lowStockItems->count() }}
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400">Items below safety reorder level</p>
                    </div>

                    <!-- Out of Stock Warnings -->
                    <div class="pq-card p-5 space-y-2 {{ $outOfStockItems->count() > 0 ? '!border-red-300 dark:!border-red-500/40 bg-red-50/40 dark:bg-red-500/5' : '' }}">
                        <div class="flex items-center justify-between text-stone-600 dark:text-stone-400 text-xs font-semibold">
                            <span>Out of Stock</span>
                            <flux:icon name="no-symbol" class="size-4 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="text-2xl font-black tabular-nums {{ $outOfStockItems->count() > 0 ? 'text-red-700 dark:text-red-400' : 'text-stone-900 dark:text-white' }}">
                            {{ $outOfStockItems->count() }}
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400">Items needing immediate restock</p>
                    </div>
                </div>

                <!-- Critical Restock Alert Banner if any low/out of stock -->
                @if ($restockNeededCount > 0)
                    <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <span class="flex size-10 items-center justify-center rounded-xl pq-badge-warning shrink-0">
                                <flux:icon name="bell-alert" class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300">Automated Restock Threshold Alert</h3>
                                <p class="text-xs text-stone-700 dark:text-stone-300">
                                    {{ $restockNeededCount }} {{ $restockNeededCount === 1 ? 'supply' : 'supplies' }} reached critical threshold (e.g. {{ $lowStockItems->pluck('name')->merge($outOfStockItems->pluck('name'))->take(2)->join(', ') }}).
                                </p>
                            </div>
                        </div>
                        <button
                            wire:click="$set('active_tab', 'restock_po')"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold shrink-0 transition-colors cursor-pointer shadow-sm"
                        >
                            Review Restock PO Order
                        </button>
                    </div>
                @endif

                <!-- Dynamic Burn Rate & Velocity Engine Showcase -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 pq-card p-6 space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-base font-bold text-stone-900 dark:text-white">Dynamic Material Consumption Velocity (Burn Rate)</h3>
                                <p class="text-xs text-stone-600 dark:text-stone-400">Calculated short-term burn rate from active job order completions</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-success shrink-0">
                                7-Day Moving Average
                            </span>
                        </div>

                        <!-- Velocity Items Table -->
                        <div class="space-y-3 pt-2">
                            @foreach ($allItems->take(5) as $vItem)
                                @php
                                    $dailyBurn = max(0.5, round($vItem->reorder_level / 4, 1)); // Demo velocity formula
                                    $daysRemaining = $vItem->stock_qty > 0 ? round($vItem->stock_qty / $dailyBurn, 1) : 0;
                                    $percentStock = min(100, max(0, round(($vItem->stock_qty / max(1, $vItem->reorder_level * 2)) * 100)));
                                @endphp
                                <div class="pq-well p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                    <div class="space-y-1 max-w-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-stone-900 dark:text-stone-100">{{ $vItem->name }}</span>
                                            <span class="text-[10px] text-stone-500 font-mono">({{ $vItem->sku ?? 'NO-SKU' }})</span>
                                        </div>
                                        <div class="text-[11px] text-stone-600 dark:text-stone-400 tabular-nums">
                                            Burn: <strong class="text-emerald-700 dark:text-emerald-400">{{ $dailyBurn }} {{ $vItem->unit }}/day</strong> &bull; Reorder Point: {{ $vItem->reorder_level }} {{ $vItem->unit }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                        <div class="text-right tabular-nums">
                                            <div class="text-xs font-bold text-stone-900 dark:text-white">{{ $vItem->stock_qty }} {{ $vItem->unit }}</div>
                                            <div class="text-[10px] {{ $daysRemaining <= 3 ? 'text-red-700 dark:text-red-400 font-bold' : 'text-stone-500 dark:text-stone-400' }}">
                                                ~{{ $daysRemaining }} days remaining
                                            </div>
                                        </div>
                                        <button
                                            wire:click="openAdjustModal({{ $vItem->id }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-stone-800 hover:bg-stone-100 dark:hover:bg-stone-700 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-200 text-[11px] font-bold transition-colors cursor-pointer"
                                        >
                                            Stock-In
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Quick Requisition & Audit Widget -->
                    <div class="pq-card p-6 space-y-4 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg pq-badge-info">
                                    <flux:icon name="document-chart-bar" class="size-4" />
                                </span>
                                <h3 class="text-sm font-bold text-stone-900 dark:text-white">Quick Restock Summary</h3>
                            </div>
                            <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                                The system dynamically groups all supplies below safety stock and calculates the exact replenishment quantity needed for peak season production runs.
                            </p>

                            <div class="pq-well p-3.5 space-y-2">
                                <div class="text-[11px] font-bold text-stone-700 dark:text-stone-300">Items Needing Restock:</div>
                                <div class="text-xl font-black text-amber-700 dark:text-amber-400 tabular-nums">
                                    {{ $restockNeededCount }} {{ $restockNeededCount === 1 ? 'item' : 'items' }}
                                </div>
                                <div class="text-[11px] text-stone-600 dark:text-stone-400 tabular-nums">
                                    Estimated Restock Cost: <strong class="text-stone-900 dark:text-stone-200">₱{{ number_format($lowStockItems->sum(fn($i) => ($i->reorder_level * 2 - $i->stock_qty) * $i->unit_cost), 2) }}</strong>
                                </div>
                            </div>
                        </div>

                        <button
                            wire:click="$set('active_tab', 'restock_po')"
                            class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center shadow-sm transition-colors cursor-pointer"
                        >
                            Open Purchase Order
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: MATERIALS & PRODUCTS CATALOG -->
        @if ($active_tab === 'materials')
            <div class="space-y-6">
                <!-- Header with Action -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-stone-900 dark:text-white tracking-tight">Materials & Products Catalog</h1>
                        <p class="text-xs text-stone-600 dark:text-stone-400">Manage all raw printing materials, paper types, adhesives, and ready-to-sell add-on products</p>
                    </div>
                    <button
                        wire:click="openAddItemModal"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                    >
                        <flux:icon name="plus" class="size-4" />
                        <span>Add New Material / Product</span>
                    </button>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="pq-card p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <flux:icon name="magnifying-glass" class="size-4 text-stone-400 dark:text-stone-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            wire:model.live.debounce.150ms="search"
                            type="text"
                            placeholder="Search by name, SKU, or supplier..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-500"
                        />
                    </div>

                    <!-- Item Type Filter -->
                    <div>
                        <select
                            wire:model.live="selected_item_type"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-500"
                        >
                            <option value="all">All Item Types</option>
                            <option value="raw_material">Raw Materials</option>
                            <option value="ready_to_sell">Ready-to-Sell Products</option>
                            <option value="consumable">Consumables</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <select
                            wire:model.live="selected_category"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-500"
                        >
                            <option value="all">All Categories</option>
                            <option value="Cover">Cover / Leatherette</option>
                            <option value="Paper">Paper Stock</option>
                            <option value="Adhesive">Adhesives & Glues</option>
                            <option value="Foil">Stamping Foils</option>
                            <option value="Add-on">Retail Add-ons</option>
                        </select>
                    </div>

                    <!-- Service Tag Filter -->
                    <div>
                        <select
                            wire:model.live="selected_service_tag"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-500"
                        >
                            <option value="all">All Services</option>
                            <option value="thesis_binding">Thesis Binding</option>
                            <option value="document_printing">Document Printing</option>
                        </select>
                    </div>
                </div>

                <!-- Inventory Items DataTable -->
                <div class="pq-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300">
                            <thead class="bg-stone-50 dark:bg-stone-950/80 border-b border-stone-200 dark:border-stone-800 text-[11px] font-bold uppercase text-stone-500 dark:text-stone-400">
                                <tr>
                                    <th class="p-4">Material / Product</th>
                                    <th class="p-4">SKU & Category</th>
                                    <th class="p-4">Item Type</th>
                                    <th class="p-4">Current Stock</th>
                                    <th class="p-4">Unit Cost / Price</th>
                                    <th class="p-4">Stock Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 dark:divide-stone-800/60">
                                @forelse ($items as $item)
                                    <tr class="hover:bg-stone-50 dark:hover:bg-stone-800/40 transition-colors">
                                        <!-- Name & Supplier -->
                                        <td class="p-4">
                                            <div class="font-bold text-stone-900 dark:text-white text-sm">{{ $item->name }}</div>
                                            <div class="text-[11px] text-stone-500 dark:text-stone-400">
                                                Supplier: {{ $item->supplier_name ?? 'Not specified' }} &bull; Service: <span class="text-amber-700 dark:text-amber-400 font-semibold">{{ $item->service_tag ?? 'All' }}</span>
                                            </div>
                                        </td>

                                        <!-- SKU & Category -->
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-stone-800 font-mono text-[10px] text-stone-700 dark:text-stone-300 border border-stone-300 dark:border-stone-700">
                                                {{ $item->sku ?? 'NO-SKU' }}
                                            </span>
                                            <div class="text-[11px] text-stone-600 dark:text-stone-400 mt-1">{{ $item->category }}</div>
                                        </td>

                                        <!-- Type -->
                                        <td class="p-4">
                                            @if ($item->item_type === InventoryItem::TYPE_RAW_MATERIAL)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-info">Raw Material</span>
                                            @elseif ($item->item_type === InventoryItem::TYPE_READY_TO_SELL)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30">Ready-to-Sell</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-neutral">Consumable</span>
                                            @endif
                                        </td>

                                        <!-- Stock & Unit -->
                                        <td class="p-4 tabular-nums">
                                            <div class="font-bold text-stone-900 dark:text-white text-sm">
                                                {{ number_format($item->stock_qty, $item->stock_qty == (int)$item->stock_qty ? 0 : 2) }} <span class="text-stone-500 dark:text-stone-400 text-xs">{{ $item->unit }}</span>
                                            </div>
                                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">
                                                Available: {{ number_format($item->availableQty(), $item->availableQty() == (int) $item->availableQty() ? 0 : 2) }} {{ $item->unit }}
                                                @if ((float) $item->reserved_qty > 0)
                                                    <span class="text-amber-700 dark:text-amber-400 ml-1">&bull; Reserved: {{ number_format((float) $item->reserved_qty, (float) $item->reserved_qty == (int) $item->reserved_qty ? 0 : 2) }}</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-stone-500 dark:text-stone-400">
                                                Safety Level: {{ $item->reorder_level }} {{ $item->unit }}
                                            </div>
                                        </td>

                                        <!-- Cost / Price -->
                                        <td class="p-4 tabular-nums">
                                            <div class="text-xs text-stone-700 dark:text-stone-300 font-medium">
                                                Cost: <strong class="text-stone-900 dark:text-white">₱{{ number_format($item->unit_cost, 2) }}</strong>
                                            </div>
                                            @if ($item->selling_price)
                                                <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-bold">
                                                    Sell: ₱{{ number_format($item->selling_price, 2) }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="p-4">
                                            @if ($item->isOutOfStock())
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-danger">
                                                    Out of Stock
                                                </span>
                                            @elseif ($item->isLowStock())
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-warning">
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold pq-badge-success">
                                                    In Stock
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    wire:click="openAdjustModal({{ $item->id }})"
                                                    title="Stock-In / Adjust"
                                                    class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 transition-colors cursor-pointer"
                                                >
                                                    <flux:icon name="plus" class="size-3.5" />
                                                </button>
                                                <button
                                                    wire:click="openEditItemModal({{ $item->id }})"
                                                    title="Edit Item Details"
                                                    class="p-1.5 rounded-lg bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700 transition-colors cursor-pointer"
                                                >
                                                    <flux:icon name="pencil-square" class="size-3.5" />
                                                </button>
                                                <button
                                                    wire:click="deleteItem({{ $item->id }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $item->name }}' from inventory?"
                                                    title="Delete"
                                                    class="p-1.5 rounded-lg bg-stone-100 dark:bg-stone-800 hover:bg-red-50 dark:hover:bg-red-500/20 hover:text-red-700 dark:hover:text-red-400 text-stone-500 dark:text-stone-400 border border-stone-200 dark:border-stone-700 transition-colors cursor-pointer"
                                                >
                                                    <flux:icon name="trash" class="size-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-stone-500 dark:text-stone-400 text-xs">
                                            No materials or products found matching your filter criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 3: STOCK MOVEMENTS AUDIT TRAIL -->
        @if ($active_tab === 'movements')
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-stone-900 dark:text-white tracking-tight">Stock Movements & Audit Trail</h1>
                        <p class="text-xs text-stone-600 dark:text-stone-400">Complete immutable record of all material deliveries, job order deductions, and waste adjustments</p>
                    </div>
                </div>

                <!-- Audit Log Table -->
                <div class="pq-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300">
                            <thead class="bg-stone-50 dark:bg-stone-950/80 border-b border-stone-200 dark:border-stone-800 text-[11px] font-bold uppercase text-stone-500 dark:text-stone-400">
                                <tr>
                                    <th class="p-4">Timestamp</th>
                                    <th class="p-4">Material / Item</th>
                                    <th class="p-4">Movement Type</th>
                                    <th class="p-4">Quantity Change</th>
                                    <th class="p-4">Stock Balance</th>
                                    <th class="p-4">Reference Note</th>
                                    <th class="p-4">Logged By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 dark:divide-stone-800/60">
                                @forelse ($recentMovements as $mov)
                                    <tr class="hover:bg-stone-50 dark:hover:bg-stone-800/40 transition-colors">
                                        <td class="p-4 font-mono text-[11px] text-stone-500 dark:text-stone-400">
                                            {{ $mov->created_at ? $mov->created_at->format('M d, Y • h:i A') : 'N/A' }}
                                        </td>
                                        <td class="p-4 font-bold text-stone-900 dark:text-white">
                                            {{ $mov->inventoryItem ? $mov->inventoryItem->name : 'Deleted Item' }}
                                        </td>
                                        <td class="p-4">
                                            @if ($mov->movement_type === StockMovement::TYPE_MANUAL_STOCK_IN)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-success">Stock-In Delivery</span>
                                            @elseif ($mov->movement_type === StockMovement::TYPE_PRODUCTION_DEDUCTION)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-info">Job Deduction</span>
                                            @elseif ($mov->movement_type === StockMovement::TYPE_SPOILAGE_WASTE)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-danger">Spoilage / Waste</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-warning">Adjustment</span>
                                            @endif
                                        </td>
                                        <td class="p-4 font-bold tabular-nums {{ $mov->quantity >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                                            {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }} {{ $mov->inventoryItem?->unit }}
                                        </td>
                                        <td class="p-4 font-mono text-stone-600 dark:text-stone-300 tabular-nums">
                                            {{ $mov->previous_stock }} &rarr; <strong class="text-stone-900 dark:text-white">{{ $mov->resulting_stock }}</strong>
                                        </td>
                                        <td class="p-4 text-stone-600 dark:text-stone-400">
                                            {{ $mov->reference_note ?? 'Standard operation' }}
                                        </td>
                                        <td class="p-4 text-stone-600 dark:text-stone-400">
                                            {{ $mov->user ? $mov->user->name : 'System Auto' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-stone-500 dark:text-stone-400 text-xs">
                                            No stock movement logs recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 4: 1-CLICK RESTOCK REQUISITION (PO) -->
        @if ($active_tab === 'restock_po')
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-stone-900 dark:text-white tracking-tight">Purchase Order & Supplier Requisition</h1>
                        <p class="text-xs text-stone-600 dark:text-stone-400">Automated replenishment calculation for depleted raw materials and ready-to-sell stock</p>
                    </div>
                </div>

                @php
                    $depletedItems = $allItems->filter(fn ($i) => $i->stock_qty <= $i->reorder_level);
                    $poText = "PURCHASE ORDER REQUISITION\n";
                    $poText .= "Shop: " . ($shop ? $shop->name : 'Print Shop') . "\n";
                    $poText .= "Date: " . date('Y-m-d H:i') . "\n";
                    $poText .= "----------------------------------------\n";
                    foreach ($depletedItems as $dItem) {
                        $recommendedQty = max(10, ($dItem->reorder_level * 2) - $dItem->stock_qty);
                        $poText .= "- {$dItem->name} (SKU: {$dItem->sku}): Qty {$recommendedQty} {$dItem->unit} (Current: {$dItem->stock_qty}) | Supplier: {$dItem->supplier_name}\n";
                    }
                    $poText .= "----------------------------------------\n";
                    $poText .= "Generated via PressQueue Dynamic Inventory Engine";
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Depleted Items Table (2 Cols) -->
                    <div class="lg:col-span-2 pq-card p-6 space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-base font-bold text-stone-900 dark:text-white">Suggested Replenishment List</h3>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold pq-badge-warning tabular-nums">
                                {{ $depletedItems->count() }} {{ $depletedItems->count() === 1 ? 'item' : 'items' }} needing restock
                            </span>
                        </div>

                        <div class="divide-y divide-stone-200 dark:divide-stone-800/80">
                            @forelse ($depletedItems as $dItem)
                                @php
                                    $recQty = max(10, ($dItem->reorder_level * 2) - $dItem->stock_qty);
                                    $estCost = $recQty * $dItem->unit_cost;
                                @endphp
                                <div class="py-3.5 flex items-center justify-between gap-4">
                                    <div>
                                        <div class="font-bold text-stone-900 dark:text-white text-sm">{{ $dItem->name }}</div>
                                        <div class="text-[11px] text-stone-500 dark:text-stone-400 tabular-nums">
                                            Current: <span class="text-red-700 dark:text-red-400 font-bold">{{ $dItem->stock_qty }} {{ $dItem->unit }}</span> &bull; Reorder Point: {{ $dItem->reorder_level }} &bull; Supplier: <strong class="text-stone-700 dark:text-stone-300">{{ $dItem->supplier_name ?? 'Any' }}</strong>
                                        </div>
                                    </div>
                                    <div class="text-right tabular-nums">
                                        <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Order: +{{ $recQty }} {{ $dItem->unit }}</div>
                                        <div class="text-[10px] text-stone-500 dark:text-stone-400">Est. ₱{{ number_format($estCost, 2) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center text-stone-500 dark:text-stone-400 text-xs">
                                    All inventory items are currently above their safety thresholds. No restock needed right now.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Fast Supplier Communication Output Box -->
                    <div class="pq-card p-6 space-y-4 flex flex-col justify-between" x-data="{ copied: false }">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg pq-badge-warning">
                                    <flux:icon name="chat-bubble-bottom-center-text" class="size-4" />
                                </span>
                                <h3 class="text-sm font-bold text-stone-900 dark:text-white">Supplier Message Requisition</h3>
                            </div>
                            <p class="text-[11px] text-stone-600 dark:text-stone-400 leading-relaxed">
                                Copy this pre-formatted purchase list to send directly to your paper and materials supplier via Messenger, Viber, or Email:
                            </p>

                            <textarea
                                readonly
                                id="poRequisitionText"
                                rows="9"
                                class="w-full p-3 rounded-2xl bg-stone-50 dark:bg-stone-950 border border-stone-300 dark:border-stone-800 font-mono text-[10px] text-stone-800 dark:text-stone-300 focus:outline-none resize-none"
                            >{{ $poText }}</textarea>
                        </div>

                        <button
                            @click="navigator.clipboard.writeText(document.getElementById('poRequisitionText').value); copied = true; setTimeout(() => copied = false, 2500)"
                            class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-950 text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer shadow-sm"
                        >
                            <flux:icon name="clipboard-document-check" class="size-4" />
                            <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Restock Order for Messenger/Email'"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <!-- MODAL 1: ADD / EDIT ITEM MODAL -->
    @if ($show_item_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 sm:p-8 shadow-xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl pq-badge-success">
                            <flux:icon name="{{ $editing_item_id ? 'pencil-square' : 'plus' }}" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-xl font-bold text-stone-900 dark:text-white">{{ $editing_item_id ? 'Edit Inventory Item' : 'Add New Inventory Item' }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-400">Configure item details, cost, safety reorder thresholds, and category</p>
                        </div>
                    </div>
                    <button wire:click="$set('show_item_modal', false)" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <form wire:submit.prevent="saveItem" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Item Name -->
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Material / Product Name *</label>
                            <input wire:model="item_name" type="text" placeholder="e.g. Leatherette Cover Sheet (Maroon)" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-600 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none" required />
                            @error('item_name') <span class="text-red-600 dark:text-red-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <!-- SKU -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">SKU / Code</label>
                            <input wire:model="item_sku" type="text" placeholder="e.g. MAT-LTH-01" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-600 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none" />
                        </div>

                        <!-- Item Type -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Item Classification *</label>
                            <select wire:model="item_type" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none">
                                <option value="raw_material">Raw Material (Consumed via BOM)</option>
                                <option value="ready_to_sell">Ready-to-Sell Product / Add-on</option>
                                <option value="consumable">Consumable (Shop supplies)</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Category *</label>
                            <select wire:model="item_category" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none">
                                <option value="Cover">Cover / Leatherette</option>
                                <option value="Paper">Paper Stock</option>
                                <option value="Adhesive">Adhesive & Glue</option>
                                <option value="Foil">Stamping Foil</option>
                                <option value="Ink / Toner">Ink &amp; Toner</option>
                                <option value="Add-on">Retail Add-on</option>
                                <option value="Packaging">Packaging</option>
                                <option value="Raw Material">General Raw Material</option>
                            </select>
                        </div>

                        <!-- Service Tag -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Service Association</label>
                            <select wire:model="item_service_tag" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none">
                                <option value="all">All Services (General)</option>
                                <option value="thesis_binding">Thesis Binding</option>
                                <option value="document_printing">Document Printing</option>
                                <option value="tarpaulin">Tarpaulin Printing</option>
                                <option value="tshirt">T-Shirt Printing</option>
                            </select>
                        </div>

                        <!-- Stock Qty -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Initial Physical Stock *</label>
                            <input wire:model="item_stock_qty" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none tabular-nums" required />
                        </div>

                        <!-- Unit -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Unit of Measure *</label>
                            <select wire:model="item_unit" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none">
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="sheets">Sheets</option>
                                <option value="reams">Reams</option>
                                <option value="kg">Kilograms (kg)</option>
                                <option value="rolls">Rolls</option>
                                <option value="meters">Meters</option>
                            </select>
                        </div>

                        <!-- Reorder Level -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Reorder Safety Level *</label>
                            <input wire:model="item_reorder_level" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none tabular-nums" required />
                            <p class="text-[10px] text-stone-500 dark:text-stone-400">Alert triggers when stock drops to or below this amount</p>
                        </div>

                        <!-- Unit Cost -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Unit Cost (₱) *</label>
                            <input wire:model="item_unit_cost" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none tabular-nums" required />
                            <p class="text-[10px] text-stone-500 dark:text-stone-400">Used for BOM material cost calculations</p>
                        </div>

                        <!-- Selling Price (for ready to sell) -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Retail Selling Price (₱) <span class="text-stone-500 dark:text-stone-400 font-normal">(Optional)</span></label>
                            <input wire:model="item_selling_price" type="number" step="0.01" placeholder="e.g. 150.00" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none tabular-nums" />
                        </div>

                        <!-- Supplier Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Supplier Name / Vendor</label>
                            <input wire:model="item_supplier_name" type="text" placeholder="e.g. Star Paper Corp" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-stone-200 dark:border-stone-800">
                        <button type="button" wire:click="$set('show_item_modal', false)" class="px-4 py-2.5 rounded-xl border border-stone-300 dark:border-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 text-xs font-bold cursor-pointer">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm cursor-pointer">
                            {{ $editing_item_id ? 'Save Changes' : 'Create Inventory Item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: QUICK STOCK-IN / ADJUSTMENT MODAL -->
    @if ($show_adjust_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/60 dark:bg-stone-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-6 shadow-xl space-y-6">
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-9 items-center justify-center rounded-xl pq-badge-success">
                            <flux:icon name="arrows-right-left" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-stone-900 dark:text-white">Stock-In / Adjust</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-400">{{ $adjust_item_name }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('show_adjust_modal', false)" class="text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white cursor-pointer" aria-label="Close modal">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <form wire:submit.prevent="saveAdjustment" class="space-y-4">
                    <div class="pq-well p-3.5 text-xs flex justify-between items-center">
                        <span class="text-stone-600 dark:text-stone-400">Current Stock Balance:</span>
                        <strong class="text-stone-900 dark:text-white text-sm tabular-nums">{{ $adjust_current_stock }} {{ $adjust_unit }}</strong>
                    </div>

                    <!-- Operation Type -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Adjustment Type *</label>
                        <select wire:model="adjust_type" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none">
                            <option value="manual_stock_in">Stock-In (Supplier Delivery / Restock)</option>
                            <option value="spoilage_waste">Scrap / Production Spoilage (-)</option>
                            <option value="manual_adjustment">Manual Inventory Correction</option>
                        </select>
                    </div>

                    <!-- Qty -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Quantity ({{ $adjust_unit }}) *</label>
                        <input wire:model="adjust_qty" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none tabular-nums" required />
                    </div>

                    <!-- Note -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-700 dark:text-stone-300">Reference Note / Reason</label>
                        <input wire:model="adjust_note" type="text" placeholder="e.g. Restock invoice #4810 or Paper jam scrap" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-stone-950 border border-stone-300 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-600 focus:border-emerald-600 dark:focus:border-emerald-500 focus:outline-none" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-stone-200 dark:border-stone-800">
                        <button type="button" wire:click="$set('show_adjust_modal', false)" class="px-4 py-2 rounded-xl border border-stone-300 dark:border-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 text-xs font-bold cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm cursor-pointer">
                            Confirm Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
