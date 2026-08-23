<?php

use App\Models\InventoryItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
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

<div class="h-screen w-screen overflow-hidden no-scrollbar bg-stone-950 text-stone-100 font-sans antialiased flex flex-col md:flex-row relative selection:bg-amber-500 selection:text-white">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 left-1/3 size-[500px] rounded-full bg-emerald-500/10 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-teal-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $user?->printShop;

        $itemsQuery = $shop ? $shop->inventoryItems() : InventoryItem::query();

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

        // Recent Movements
        $recentMovements = StockMovement::whereIn('inventory_item_id', $allItems->pluck('id'))
            ->with(['inventoryItem', 'user'])
            ->latest()
            ->take(20)
            ->get();
    @endphp

    <!-- Mobile Top Header Bar -->
    <div class="md:hidden flex items-center justify-between p-4 bg-stone-900 border-b border-stone-800 z-30">
        <div class="flex items-center gap-2">
            <span class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-stone-950 font-bold shadow">
                <flux:icon name="archive-box" class="size-5 text-stone-950" />
            </span>
            <span class="font-extrabold text-white text-sm">Inventory Hub</span>
        </div>
        <button wire:click="$toggle('mobile_menu_open')" class="p-1.5 rounded-lg bg-stone-800 text-stone-300">
            <flux:icon name="bars-3" class="size-6" />
        </button>
    </div>

    <!-- Sticky Vertical Left Navigation Sidebar -->
    <aside class="w-full md:w-64 bg-stone-900/90 border-r border-stone-800 flex flex-col justify-between h-screen overflow-y-auto no-scrollbar relative z-20 shrink-0 {{ $mobile_menu_open ? 'block' : 'hidden md:flex' }}">
        <div class="p-5 space-y-6">
            <!-- App Header & Back Link -->
            <div>
                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[11px] font-bold text-stone-400 hover:text-amber-400 mb-3 transition-colors">
                    <flux:icon name="arrow-left" class="size-3.5" />
                    Back to App Launcher
                </a>
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 text-stone-950 font-black shadow-lg shadow-emerald-500/20">
                        <flux:icon name="archive-box" class="size-6" />
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-sm font-extrabold text-white tracking-tight">Inventory Hub</h2>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-400 text-[9px] font-black uppercase border border-emerald-500/30">CORE</span>
                        </div>
                        <p class="text-[10px] text-stone-400">Shop Supplies & Stock Controls</p>
                    </div>
                </div>
            </div>

            <!-- Main Tab Navigation Links -->
            <nav class="space-y-1.5">
                <div class="px-3 pb-1 text-[10px] font-bold text-stone-400 uppercase tracking-wider">INVENTORY TOOLS</div>

                <!-- Overview Tab -->
                <button
                    wire:click="$set('active_tab', 'overview')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all {{ $active_tab === 'overview' ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-300 border border-emerald-500/30 shadow-sm' : 'text-stone-400 hover:text-stone-200 hover:bg-stone-800/60' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="chart-pie" class="size-4 text-emerald-400" />
                        <span>Overview & Analytics</span>
                    </div>
                    @if ($lowStockItems->count() > 0 || $outOfStockItems->count() > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/40">
                            {{ $lowStockItems->count() + $outOfStockItems->count() }}
                        </span>
                    @endif
                </button>

                <!-- Materials & Products Tab -->
                <button
                    wire:click="$set('active_tab', 'materials')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all {{ $active_tab === 'materials' ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-300 border border-emerald-500/30 shadow-sm' : 'text-stone-400 hover:text-stone-200 hover:bg-stone-800/60' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="cube" class="size-4 text-teal-400" />
                        <span>Materials & Products</span>
                    </div>
                    <span class="text-[10px] text-stone-400">{{ $totalItemsCount }}</span>
                </button>

                <!-- Stock Movements Tab -->
                <button
                    wire:click="$set('active_tab', 'movements')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all {{ $active_tab === 'movements' ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-300 border border-emerald-500/30 shadow-sm' : 'text-stone-400 hover:text-stone-200 hover:bg-stone-800/60' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="arrows-right-left" class="size-4 text-cyan-400" />
                        <span>Stock Movements Log</span>
                    </div>
                    <span class="text-[10px] text-stone-400">Audit</span>
                </button>

                <!-- Restock PO Generator Tab -->
                <button
                    wire:click="$set('active_tab', 'restock_po')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all {{ $active_tab === 'restock_po' ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-300 border border-emerald-500/30 shadow-sm' : 'text-stone-400 hover:text-stone-200 hover:bg-stone-800/60' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="shopping-cart" class="size-4 text-amber-400" />
                        <span>Restock Requisition (PO)</span>
                    </div>
                    @if ($lowStockItems->count() > 0)
                        <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                    @endif
                </button>
            </nav>

            <!-- Linked Printing Apps Shortcuts -->
            <div class="pt-4 border-t border-stone-800/80 space-y-2">
                <div class="px-3 text-[10px] font-bold text-stone-400 uppercase tracking-wider">LINKED SERVICES</div>
                <a href="{{ route('owner.thesis-binding') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-400 hover:text-amber-300 hover:bg-stone-800/50 transition-all">
                    <flux:icon name="book-open" class="size-3.5 text-amber-500" />
                    <span>Thesis Binding BOM</span>
                </a>
                <a href="{{ route('owner.web-builder') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-stone-400 hover:text-violet-300 hover:bg-stone-800/50 transition-all">
                    <flux:icon name="globe-alt" class="size-3.5 text-violet-400" />
                    <span>Storefront Builder</span>
                </a>
            </div>
        </div>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-stone-800 bg-stone-950/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="size-6 rounded-full bg-amber-500 text-stone-950 text-[10px] font-black flex items-center justify-center">
                    {{ $user?->initials() }}
                </span>
                <span class="text-xs font-bold text-stone-300 truncate max-w-[120px]">{{ $user?->name }}</span>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate class="text-stone-500 hover:text-amber-400" title="Back to Launcher">
                <flux:icon name="squares-2x2" class="size-4" />
            </a>
        </div>
    </aside>

    <!-- Main Workspace Body -->
    <main class="flex-1 h-screen overflow-y-auto no-scrollbar p-4 sm:p-8 space-y-6 relative z-10">
        <!-- Toast Notification Banner -->
        @if ($statusMessage)
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex items-center justify-between animate-fadeIn">
                <div class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 text-emerald-400" />
                    <span>{{ $statusMessage }}</span>
                </div>
                <button wire:click="$set('statusMessage', null)" class="text-emerald-400 hover:text-white">✕</button>
            </div>
        @endif

        <!-- TAB 1: OVERVIEW & ANALYTICS -->
        @if ($active_tab === 'overview')
            <div class="space-y-6 animate-fadeIn">
                <!-- Top Welcome Banner & Action -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-black text-white tracking-tight">Inventory Overview & Burn Rate</h1>
                        <p class="text-xs text-stone-400">Real-time material valuation, consumption rates, and automated restock alerts</p>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button
                            wire:click="openAddItemModal"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-stone-950 text-xs font-extrabold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition-all cursor-pointer"
                        >
                            <flux:icon name="plus" class="size-4 text-stone-950" />
                            <span>Add New Material / Product</span>
                        </button>
                    </div>
                </div>

                <!-- 4 KPI Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Inventory Valuation (Cost) -->
                    <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800 space-y-2">
                        <div class="flex items-center justify-between text-stone-400 text-xs font-semibold">
                            <span>Total Material Valuation</span>
                            <flux:icon name="banknotes" class="size-4 text-emerald-400" />
                        </div>
                        <div class="text-2xl font-black text-white">
                            ₱{{ number_format($totalInventoryCostValue, 2) }}
                        </div>
                        <p class="text-[11px] text-stone-500">Based on unit cost of physical stock</p>
                    </div>

                    <!-- Ready-to-Sell Product Asset Value -->
                    <div class="p-5 rounded-2xl bg-stone-900/80 border border-stone-800 space-y-2">
                        <div class="flex items-center justify-between text-stone-400 text-xs font-semibold">
                            <span>Retail Add-ons Potential</span>
                            <flux:icon name="tag" class="size-4 text-cyan-400" />
                        </div>
                        <div class="text-2xl font-black text-cyan-300">
                            ₱{{ number_format($totalReadyToSellValue, 2) }}
                        </div>
                        <p class="text-[11px] text-stone-500">{{ $allItems->where('item_type', InventoryItem::TYPE_READY_TO_SELL)->count() }} ready-to-sell products</p>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="p-5 rounded-2xl bg-stone-900/80 border {{ $lowStockItems->count() > 0 ? 'border-amber-500/40 bg-amber-500/5' : 'border-stone-800' }} space-y-2">
                        <div class="flex items-center justify-between text-stone-400 text-xs font-semibold">
                            <span>Low Stock Alerts</span>
                            <flux:icon name="exclamation-triangle" class="size-4 text-amber-400" />
                        </div>
                        <div class="text-2xl font-black {{ $lowStockItems->count() > 0 ? 'text-amber-400' : 'text-white' }}">
                            {{ $lowStockItems->count() }}
                        </div>
                        <p class="text-[11px] text-stone-500">Items below safety reorder level</p>
                    </div>

                    <!-- Out of Stock Warnings -->
                    <div class="p-5 rounded-2xl bg-stone-900/80 border {{ $outOfStockItems->count() > 0 ? 'border-red-500/40 bg-red-500/5' : 'border-stone-800' }} space-y-2">
                        <div class="flex items-center justify-between text-stone-400 text-xs font-semibold">
                            <span>Out of Stock</span>
                            <flux:icon name="no-symbol" class="size-4 text-red-400" />
                        </div>
                        <div class="text-2xl font-black {{ $outOfStockItems->count() > 0 ? 'text-red-400' : 'text-white' }}">
                            {{ $outOfStockItems->count() }}
                        </div>
                        <p class="text-[11px] text-stone-500">Items needing immediate restock</p>
                    </div>
                </div>

                <!-- Critical Restock Alert Banner if any low/out of stock -->
                @if ($lowStockItems->count() > 0 || $outOfStockItems->count() > 0)
                    <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-500/15 via-stone-900 to-stone-900 border border-amber-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0">
                                <flux:icon name="bell-alert" class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-amber-300">Automated Restock Threshold Alert</h3>
                                <p class="text-xs text-stone-400">
                                    {{ $lowStockItems->count() + $outOfStockItems->count() }} supplies reached critical threshold (e.g. {{ $lowStockItems->pluck('name')->merge($outOfStockItems->pluck('name'))->take(2)->join(', ') }}).
                                </p>
                            </div>
                        </div>
                        <button
                            wire:click="$set('active_tab', 'restock_po')"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-black shrink-0 transition-all cursor-pointer shadow-md shadow-amber-500/20"
                        >
                            Review Restock PO Order
                        </button>
                    </div>
                @endif

                <!-- Dynamic Burn Rate & Velocity Engine Showcase -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-white">Dynamic Material Consumption Velocity (Burn Rate)</h3>
                                <p class="text-xs text-stone-400">Calculated short-term burn rate from active job order completions</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
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
                                <div class="p-3.5 rounded-2xl bg-stone-950/60 border border-stone-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                    <div class="space-y-1 max-w-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-stone-200">{{ $vItem->name }}</span>
                                            <span class="text-[10px] text-stone-500 font-mono">({{ $vItem->sku ?? 'NO-SKU' }})</span>
                                        </div>
                                        <div class="text-[11px] text-stone-400">
                                            Burn: <strong class="text-emerald-400">{{ $dailyBurn }} {{ $vItem->unit }}/day</strong> &bull; Reorder Point: {{ $vItem->reorder_level }} {{ $vItem->unit }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                        <div class="text-right">
                                            <div class="text-xs font-black text-white">{{ $vItem->stock_qty }} {{ $vItem->unit }}</div>
                                            <div class="text-[10px] {{ $daysRemaining <= 3 ? 'text-red-400 font-bold' : 'text-stone-500' }}">
                                                ~{{ $daysRemaining }} days remaining
                                            </div>
                                        </div>
                                        <button
                                            wire:click="openAdjustModal({{ $vItem->id }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 text-stone-300 text-[11px] font-bold transition-colors cursor-pointer"
                                        >
                                            Stock-In
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Quick Requisition & Audit Widget -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-4 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-cyan-500/20 text-cyan-400">
                                    <flux:icon name="document-chart-bar" class="size-4" />
                                </span>
                                <h3 class="text-sm font-extrabold text-white">Quick Restock Summary</h3>
                            </div>
                            <p class="text-xs text-stone-400 leading-relaxed">
                                The system dynamically groups all supplies below safety stock and calculates the exact replenishment quantity needed for peak season production runs.
                            </p>

                            <div class="p-3.5 rounded-2xl bg-stone-950/70 border border-stone-800 space-y-2">
                                <div class="text-[11px] font-bold text-stone-300">Items Needing Restock:</div>
                                <div class="text-xl font-black text-amber-400">
                                    {{ $lowStockItems->count() + $outOfStockItems->count() }} items
                                </div>
                                <div class="text-[11px] text-stone-500">
                                    Estimated Restock Cost: <strong class="text-stone-300">₱{{ number_format($lowStockItems->sum(fn($i) => ($i->reorder_level * 2 - $i->stock_qty) * $i->unit_cost), 2) }}</strong>
                                </div>
                            </div>
                        </div>

                        <button
                            wire:click="$set('active_tab', 'restock_po')"
                            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-stone-950 text-xs font-black text-center shadow-lg shadow-emerald-500/20 cursor-pointer"
                        >
                            Open 1-Click Purchase Order
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: MATERIALS & PRODUCTS CATALOG -->
        @if ($active_tab === 'materials')
            <div class="space-y-6 animate-fadeIn">
                <!-- Header with Action -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-black text-white tracking-tight">Materials & Products Catalog</h1>
                        <p class="text-xs text-stone-400">Manage all raw printing materials, paper types, adhesives, and ready-to-sell add-on products</p>
                    </div>
                    <button
                        wire:click="openAddItemModal"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-stone-950 text-xs font-extrabold shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition-all cursor-pointer"
                    >
                        <flux:icon name="plus" class="size-4 text-stone-950" />
                        <span>Add New Material / Product</span>
                    </button>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="p-4 rounded-2xl bg-stone-900/90 border border-stone-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <flux:icon name="magnifying-glass" class="size-4 text-stone-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            wire:model.live.debounce.150ms="search"
                            type="text"
                            placeholder="Search by name, SKU, or supplier..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 placeholder-stone-500 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <!-- Item Type Filter -->
                    <div>
                        <select
                            wire:model.live="selected_item_type"
                            class="w-full px-3 py-2 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:outline-none focus:border-emerald-500"
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
                            class="w-full px-3 py-2 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:outline-none focus:border-emerald-500"
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
                            class="w-full px-3 py-2 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="all">All Services</option>
                            <option value="thesis_binding">Thesis Binding</option>
                            <option value="document_printing">Document Printing</option>
                        </select>
                    </div>
                </div>

                <!-- Inventory Items DataTable -->
                <div class="rounded-3xl bg-stone-900/80 border border-stone-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-stone-300">
                            <thead class="bg-stone-950/80 border-b border-stone-800 text-[11px] font-extrabold uppercase text-stone-400">
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
                            <tbody class="divide-y divide-stone-800/60">
                                @forelse ($items as $item)
                                    <tr class="hover:bg-stone-800/40 transition-colors">
                                        <!-- Name & Supplier -->
                                        <td class="p-4">
                                            <div class="font-bold text-white text-sm">{{ $item->name }}</div>
                                            <div class="text-[11px] text-stone-500">
                                                Supplier: {{ $item->supplier_name ?? 'Not specified' }} &bull; Service: <span class="text-amber-400/90 font-medium">{{ $item->service_tag ?? 'All' }}</span>
                                            </div>
                                        </td>

                                        <!-- SKU & Category -->
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded bg-stone-800 font-mono text-[10px] text-stone-300 border border-stone-700">
                                                {{ $item->sku ?? 'NO-SKU' }}
                                            </span>
                                            <div class="text-[11px] text-stone-400 mt-1">{{ $item->category }}</div>
                                        </td>

                                        <!-- Type -->
                                        <td class="p-4">
                                            @if ($item->item_type === InventoryItem::TYPE_RAW_MATERIAL)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/30">Raw Material</span>
                                            @elseif ($item->item_type === InventoryItem::TYPE_READY_TO_SELL)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/30">Ready-to-Sell</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-500/10 text-stone-400 border border-stone-500/30">Consumable</span>
                                            @endif
                                        </td>

                                        <!-- Stock & Unit -->
                                        <td class="p-4">
                                            <div class="font-extrabold text-white text-sm">
                                                {{ number_format($item->stock_qty, $item->stock_qty == (int)$item->stock_qty ? 0 : 2) }} <span class="text-stone-400 text-xs">{{ $item->unit }}</span>
                                            </div>
                                            <div class="text-[10px] text-stone-500">
                                                Safety Level: {{ $item->reorder_level }} {{ $item->unit }}
                                            </div>
                                        </td>

                                        <!-- Cost / Price -->
                                        <td class="p-4">
                                            <div class="text-xs text-stone-300 font-medium">
                                                Cost: <strong>₱{{ number_format($item->unit_cost, 2) }}</strong>
                                            </div>
                                            @if ($item->selling_price)
                                                <div class="text-[11px] text-emerald-400 font-bold">
                                                    Sell: ₱{{ number_format($item->selling_price, 2) }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="p-4">
                                            @if ($item->isOutOfStock())
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-500/20 text-red-400 border border-red-500/40 animate-pulse">
                                                    Out of Stock
                                                </span>
                                            @elseif ($item->isLowStock())
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/40">
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
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
                                                    class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 transition-all cursor-pointer"
                                                >
                                                    <flux:icon name="plus" class="size-3.5" />
                                                </button>
                                                <button
                                                    wire:click="openEditItemModal({{ $item->id }})"
                                                    title="Edit Item Details"
                                                    class="p-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 text-stone-300 transition-all cursor-pointer"
                                                >
                                                    <flux:icon name="pencil-square" class="size-3.5" />
                                                </button>
                                                <button
                                                    wire:click="deleteItem({{ $item->id }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $item->name }}' from inventory?"
                                                    title="Delete"
                                                    class="p-1.5 rounded-lg bg-stone-800 hover:bg-red-500/20 hover:text-red-400 text-stone-400 transition-all cursor-pointer"
                                                >
                                                    <flux:icon name="trash" class="size-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-stone-500 text-xs">
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
            <div class="space-y-6 animate-fadeIn">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-black text-white tracking-tight">Stock Movements & Audit Trail</h1>
                        <p class="text-xs text-stone-400">Complete immutable record of all material deliveries, job order deductions, and waste adjustments</p>
                    </div>
                </div>

                <!-- Audit Log Table -->
                <div class="rounded-3xl bg-stone-900/80 border border-stone-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-stone-300">
                            <thead class="bg-stone-950/80 border-b border-stone-800 text-[11px] font-extrabold uppercase text-stone-400">
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
                            <tbody class="divide-y divide-stone-800/60">
                                @forelse ($recentMovements as $mov)
                                    <tr class="hover:bg-stone-800/40 transition-colors">
                                        <td class="p-4 font-mono text-[11px] text-stone-400">
                                            {{ $mov->created_at ? $mov->created_at->format('M d, Y • h:i A') : 'N/A' }}
                                        </td>
                                        <td class="p-4 font-bold text-white">
                                            {{ $mov->inventoryItem ? $mov->inventoryItem->name : 'Deleted Item' }}
                                        </td>
                                        <td class="p-4">
                                            @if ($mov->movement_type === StockMovement::TYPE_MANUAL_STOCK_IN)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Stock-In Delivery</span>
                                            @elseif ($mov->movement_type === StockMovement::TYPE_PRODUCTION_DEDUCTION)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/30">Job Deduction</span>
                                            @elseif ($mov->movement_type === StockMovement::TYPE_SPOILAGE_WASTE)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/30">Spoilage / Waste</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Adjustment</span>
                                            @endif
                                        </td>
                                        <td class="p-4 font-black {{ $mov->quantity >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }} {{ $mov->inventoryItem?->unit }}
                                        </td>
                                        <td class="p-4 font-mono text-stone-300">
                                            {{ $mov->previous_stock }} &rarr; <strong class="text-white">{{ $mov->resulting_stock }}</strong>
                                        </td>
                                        <td class="p-4 text-stone-400">
                                            {{ $mov->reference_note ?? 'Standard operation' }}
                                        </td>
                                        <td class="p-4 text-stone-400">
                                            {{ $mov->user ? $mov->user->name : 'System Auto' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-stone-500 text-xs">
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
            <div class="space-y-6 animate-fadeIn">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-stone-800 pb-5">
                    <div>
                        <h1 class="text-2xl font-black text-white tracking-tight">1-Click Purchase Order & Supplier Requisition</h1>
                        <p class="text-xs text-stone-400">Automated replenishment calculation for depleted raw materials and ready-to-sell stock</p>
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
                    $poText .= "Generated via Printify Dynamic Inventory Engine";
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Depleted Items Table (2 Cols) -->
                    <div class="lg:col-span-2 rounded-3xl bg-stone-900/80 border border-stone-800 p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-extrabold text-white">Suggested Replenishment List</h3>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                {{ $depletedItems->count() }} items needing restock
                            </span>
                        </div>

                        <div class="divide-y divide-stone-800/80">
                            @forelse ($depletedItems as $dItem)
                                @php
                                    $recQty = max(10, ($dItem->reorder_level * 2) - $dItem->stock_qty);
                                    $estCost = $recQty * $dItem->unit_cost;
                                @endphp
                                <div class="py-3.5 flex items-center justify-between gap-4">
                                    <div>
                                        <div class="font-bold text-white text-sm">{{ $dItem->name }}</div>
                                        <div class="text-[11px] text-stone-500">
                                            Current: <span class="text-red-400 font-bold">{{ $dItem->stock_qty }} {{ $dItem->unit }}</span> &bull; Reorder Point: {{ $dItem->reorder_level }} &bull; Supplier: <strong class="text-stone-300">{{ $dItem->supplier_name ?? 'Any' }}</strong>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-extrabold text-emerald-400">Order: +{{ $recQty }} {{ $dItem->unit }}</div>
                                        <div class="text-[10px] text-stone-500">Est. ₱{{ number_format($estCost, 2) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center text-stone-500 text-xs">
                                    🎉 All inventory items are currently above their safety thresholds. No restock needed right now!
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Fast Supplier Communication Output Box -->
                    <div class="rounded-3xl bg-stone-900/80 border border-stone-800 p-6 space-y-4 flex flex-col justify-between" x-data="{ copied: false }">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400">
                                    <flux:icon name="chat-bubble-bottom-center-text" class="size-4" />
                                </span>
                                <h3 class="text-sm font-extrabold text-white">Supplier Message Requisition</h3>
                            </div>
                            <p class="text-[11px] text-stone-400 leading-relaxed">
                                Copy this pre-formatted purchase list to send directly to your paper and materials supplier via Messenger, Viber, or Email:
                            </p>

                            <textarea
                                readonly
                                id="poRequisitionText"
                                rows="9"
                                class="w-full p-3 rounded-2xl bg-stone-950 border border-stone-800 font-mono text-[10px] text-stone-300 focus:outline-none resize-none"
                            >{{ $poText }}</textarea>
                        </div>

                        <button
                            @click="navigator.clipboard.writeText(document.getElementById('poRequisitionText').value); copied = true; setTimeout(() => copied = false, 2500)"
                            class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-black flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg shadow-amber-500/20"
                        >
                            <flux:icon name="clipboard-document-check" class="size-4 text-stone-950" />
                            <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Restock Order for Messenger/Email'"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <!-- MODAL 1: ADD / EDIT ITEM MODAL -->
    @if ($show_item_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-2xl rounded-3xl border border-stone-800 bg-stone-900 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <flux:icon name="{{ $editing_item_id ? 'pencil-square' : 'plus' }}" class="size-6" />
                        </span>
                        <div>
                            <h3 class="text-xl font-extrabold text-white">{{ $editing_item_id ? 'Edit Inventory Item' : 'Add New Inventory Item' }}</h3>
                            <p class="text-xs text-stone-400">Configure item details, cost, safety reorder thresholds, and category</p>
                        </div>
                    </div>
                    <button wire:click="$set('show_item_modal', false)" class="text-stone-400 hover:text-white text-lg">✕</button>
                </div>

                <form wire:submit.prevent="saveItem" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Item Name -->
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-xs font-bold text-stone-300">Material / Product Name *</label>
                            <input wire:model="item_name" type="text" placeholder="e.g. Leatherette Cover Sheet (Maroon)" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 placeholder-stone-600 focus:border-emerald-500 focus:outline-none" required />
                            @error('item_name') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <!-- SKU -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">SKU / Code</label>
                            <input wire:model="item_sku" type="text" placeholder="e.g. MAT-LTH-01" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 placeholder-stone-600 focus:border-emerald-500 focus:outline-none" />
                        </div>

                        <!-- Item Type -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Item Classification *</label>
                            <select wire:model="item_type" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:border-emerald-500 focus:outline-none">
                                <option value="raw_material">Raw Material (Consumed via BOM)</option>
                                <option value="ready_to_sell">Ready-to-Sell Product / Add-on</option>
                                <option value="consumable">Consumable (Shop supplies)</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Category *</label>
                            <select wire:model="item_category" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:border-emerald-500 focus:outline-none">
                                <option value="Cover">Cover / Leatherette</option>
                                <option value="Paper">Paper Stock</option>
                                <option value="Adhesive">Adhesive & Glue</option>
                                <option value="Foil">Stamping Foil</option>
                                <option value="Add-on">Retail Add-on</option>
                                <option value="Packaging">Packaging</option>
                                <option value="Raw Material">General Raw Material</option>
                            </select>
                        </div>

                        <!-- Service Tag -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Service Association</label>
                            <select wire:model="item_service_tag" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:border-emerald-500 focus:outline-none">
                                <option value="all">All Services (General)</option>
                                <option value="thesis_binding">Thesis Binding</option>
                                <option value="document_printing">Document Printing</option>
                                <option value="tarpaulin">Tarpaulin Printing</option>
                                <option value="tshirt">T-Shirt Printing</option>
                            </select>
                        </div>

                        <!-- Stock Qty -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Initial Physical Stock *</label>
                            <input wire:model="item_stock_qty" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" required />
                        </div>

                        <!-- Unit -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Unit of Measure *</label>
                            <select wire:model="item_unit" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:border-emerald-500 focus:outline-none">
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
                            <label class="text-xs font-bold text-stone-300">Reorder Safety Level *</label>
                            <input wire:model="item_reorder_level" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" required />
                            <p class="text-[10px] text-stone-500">Alert triggers when stock drops to or below this amount</p>
                        </div>

                        <!-- Unit Cost -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Unit Cost (₱) *</label>
                            <input wire:model="item_unit_cost" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" required />
                            <p class="text-[10px] text-stone-500">Used for BOM material cost calculations</p>
                        </div>

                        <!-- Selling Price (for ready to sell) -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Retail Selling Price (₱) <span class="text-stone-500 font-normal">(Optional)</span></label>
                            <input wire:model="item_selling_price" type="number" step="0.01" placeholder="e.g. 150.00" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" />
                        </div>

                        <!-- Supplier Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Supplier Name / Vendor</label>
                            <input wire:model="item_supplier_name" type="text" placeholder="e.g. Star Paper Corp" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-stone-800">
                        <button type="button" wire:click="$set('show_item_modal', false)" class="px-4 py-2.5 rounded-xl border border-stone-800 text-stone-300 hover:text-white text-xs font-bold">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-stone-950 text-xs font-black shadow-lg shadow-emerald-500/20 cursor-pointer">
                            {{ $editing_item_id ? 'Save Changes' : 'Create Inventory Item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: QUICK STOCK-IN / ADJUSTMENT MODAL -->
    @if ($show_adjust_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-md rounded-3xl border border-stone-800 bg-stone-900 p-6 shadow-2xl space-y-6">
                <div class="flex items-center justify-between border-b border-stone-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                            <flux:icon name="arrows-right-left" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-white">Stock-In / Adjust</h3>
                            <p class="text-xs text-stone-400">{{ $adjust_item_name }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('show_adjust_modal', false)" class="text-stone-400 hover:text-white text-base">✕</button>
                </div>

                <form wire:submit.prevent="saveAdjustment" class="space-y-4">
                    <div class="p-3.5 rounded-2xl bg-stone-950 border border-stone-800 text-xs flex justify-between items-center">
                        <span class="text-stone-400">Current Stock Balance:</span>
                        <strong class="text-white text-sm">{{ $adjust_current_stock }} {{ $adjust_unit }}</strong>
                    </div>

                    <!-- Operation Type -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-300">Adjustment Type *</label>
                        <select wire:model="adjust_type" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-200 focus:border-emerald-500 focus:outline-none">
                            <option value="manual_stock_in">Stock-In (Supplier Delivery / Restock)</option>
                            <option value="spoilage_waste">Scrap / Production Spoilage (-)</option>
                            <option value="manual_adjustment">Manual Inventory Correction</option>
                        </select>
                    </div>

                    <!-- Qty -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-300">Quantity ({{ $adjust_unit }}) *</label>
                        <input wire:model="adjust_qty" type="number" step="0.01" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 focus:border-emerald-500 focus:outline-none" required />
                    </div>

                    <!-- Note -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-stone-300">Reference Note / Reason</label>
                        <input wire:model="adjust_note" type="text" placeholder="e.g. Restock invoice #4810 or Paper jam scrap" class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 placeholder-stone-600 focus:border-emerald-500 focus:outline-none" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-stone-800">
                        <button type="button" wire:click="$set('show_adjust_modal', false)" class="px-4 py-2 rounded-xl border border-stone-800 text-stone-300 hover:text-white text-xs font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-stone-950 text-xs font-black shadow-lg shadow-emerald-500/20 cursor-pointer">
                            Confirm Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
