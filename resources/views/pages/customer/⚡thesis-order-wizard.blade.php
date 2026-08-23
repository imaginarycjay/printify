<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\ThesisBindingConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public int $step = 1; // 1 = Configure, 2 = Checkout & Payment, 3 = Confirmation

    // Dynamic Shop Config & Rates
    public float $hardbound_base_price = 350.00;
    public float $softbound_base_price = 150.00;
    public bool $allow_customer_supplied_paper = true;
    public float $hardbound_cover_only_price = 300.00;
    public float $page_price_bw = 1.50;
    public float $page_price_color = 5.00;
    public float $rush_fee = 150.00;
    public array $available_cover_colors = [];
    public array $available_foil_colors = [];
    public array $available_paper_sizes = [];
    public array $required_custom_fields = [];
    public int $standard_lead_time_days = 4;
    public int $rush_lead_time_days = 1;
    public bool $require_pdf_upload = true;

    // Customer Selection State
    public string $fulfillment_type = 'full_package'; // 'full_package' or 'cover_only'
    public string $binding_type = 'hardbound';
    public int $bw_pages = 80;
    public int $color_pages = 20;
    public int $preprinted_total_pages = 100;
    public string $paper_size = 'A4';
    public string $cover_color = 'Maroon';
    public string $foil_color = 'Gold';
    public int $copies_count = 1;
    public bool $is_rush = false;

    // Dynamic Cover Fields Form
    public array $custom_fields_data = [];

    // File Uploads
    public mixed $manuscript_file = null;
    public mixed $payment_proof_file = null;

    // Retail Add-ons Selection
    public array $selected_addon_ids = [];

    // Payment Reference
    public string $payment_reference_no = '';

    // Completed Order Info
    public ?int $created_order_id = null;
    public ?string $created_order_number = null;

    public function mount(): void
    {
        $shop = PrintShop::first();
        if (! $shop) {
            return;
        }

        /** @var ThesisBindingConfig|null $config */
        $config = $shop->thesisBindingConfig;
        if (! $config) {
            $config = ThesisBindingConfig::create([
                'print_shop_id' => $shop->id,
                'is_active' => true,
                'hardbound_base_price' => 350.00,
                'softbound_base_price' => 150.00,
                'allow_customer_supplied_paper' => true,
                'hardbound_cover_only_price' => 300.00,
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
            ]);
        }

        $this->hardbound_base_price = (float) $config->hardbound_base_price;
        $this->softbound_base_price = (float) $config->softbound_base_price;
        $this->allow_customer_supplied_paper = (bool) ($config->allow_customer_supplied_paper ?? true);
        $this->hardbound_cover_only_price = (float) ($config->hardbound_cover_only_price ?? 300.00);
        $this->page_price_bw = (float) $config->page_price_bw;
        $this->page_price_color = (float) $config->page_price_color;
        $this->rush_fee = (float) $config->rush_fee;
        $this->available_cover_colors = $config->cover_colors ?? ThesisBindingConfig::defaultCoverColors();
        $this->available_foil_colors = $config->foil_colors ?? ThesisBindingConfig::defaultFoilColors();
        $this->available_paper_sizes = $config->paper_sizes ?? ThesisBindingConfig::defaultPaperSizes();
        $this->required_custom_fields = $config->custom_cover_fields ?? ThesisBindingConfig::defaultCustomCoverFields();
        $this->standard_lead_time_days = (int) $config->standard_lead_time_days;
        $this->rush_lead_time_days = (int) $config->rush_lead_time_days;
        $this->require_pdf_upload = (bool) $config->require_pdf_upload;

        // Set defaults
        $this->cover_color = $this->available_cover_colors[0] ?? 'Maroon';
        $this->foil_color = $this->available_foil_colors[0] ?? 'Gold';
        $this->paper_size = $this->available_paper_sizes[0] ?? 'A4';

        // Initialize custom cover fields
        foreach ($this->required_custom_fields as $field) {
            $this->custom_fields_data[$field] = '';
        }
    }

    public function calculatePricing(): array
    {
        $isCoverOnly = ($this->fulfillment_type === 'cover_only');
        $base = $isCoverOnly
            ? $this->hardbound_cover_only_price
            : ($this->binding_type === 'hardbound' ? $this->hardbound_base_price : $this->softbound_base_price);
        $bwTotal = $isCoverOnly ? 0.0 : ($this->bw_pages * $this->page_price_bw);
        $colorTotal = $isCoverOnly ? 0.0 : ($this->color_pages * $this->page_price_color);
        $unitPrice = $base + $bwTotal + $colorTotal;
        $subtotal = $unitPrice * $this->copies_count;

        // Add-ons
        $shop = PrintShop::first();
        $addonsTotal = 0.0;
        $selectedAddonsDetails = [];

        if ($shop && !empty($this->selected_addon_ids)) {
            $addons = $shop->inventoryItems()->whereIn('id', $this->selected_addon_ids)->get();
            foreach ($addons as $addon) {
                $price = $addon->selling_price ?? 0.0;
                $addonsTotal += $price * $this->copies_count;
                $selectedAddonsDetails[] = [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $price,
                    'qty' => $this->copies_count,
                ];
            }
        }

        $rushTotal = $this->is_rush ? $this->rush_fee : 0.0;
        $grandTotal = $subtotal + $addonsTotal + $rushTotal;

        $targetDate = $this->is_rush
            ? Carbon::now()->addDays($this->rush_lead_time_days)
            : Carbon::now()->addDays($this->standard_lead_time_days);

        $totalPages = $isCoverOnly ? $this->preprinted_total_pages : ($this->bw_pages + $this->color_pages);
        $estimatedSpineMm = round($totalPages * 0.1, 1);

        return [
            'base_price' => $base,
            'bw_total' => $bwTotal,
            'color_total' => $colorTotal,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'addons_total' => $addonsTotal,
            'selected_addons_details' => $selectedAddonsDetails,
            'rush_total' => $rushTotal,
            'grand_total' => $grandTotal,
            'target_completion_date' => $targetDate,
            'estimated_spine_mm' => $estimatedSpineMm,
        ];
    }

    public function proceedToCheckout(): void
    {
        if ($this->fulfillment_type === 'cover_only') {
            $this->binding_type = 'hardbound';
            $rules = [
                'preprinted_total_pages' => 'required|integer|min:10|max:1000',
                'copies_count' => 'required|integer|min:1|max:50',
                'paper_size' => 'required|string',
                'cover_color' => 'required|string',
                'foil_color' => 'required|string',
                'manuscript_file' => 'nullable|file|mimes:pdf|max:51200',
            ];

            foreach ($this->required_custom_fields as $field) {
                $rules["custom_fields_data.{$field}"] = 'required|string|min:2|max:255';
            }

            $this->validate($rules, [
                'preprinted_total_pages.required' => 'Please enter total pages for spine sizing.',
                'custom_fields_data.*.required' => 'This field is required for your hardbound cover foil stamping.',
                'cover_color.required' => 'Please select a cover leatherette color.',
                'foil_color.required' => 'Please select a foil stamping color.',
            ]);
        } else {
            $rules = [
                'binding_type' => 'required|in:hardbound,softbound',
                'bw_pages' => 'required|integer|min:0|max:1000',
                'color_pages' => 'required|integer|min:0|max:500',
                'copies_count' => 'required|integer|min:1|max:50',
                'paper_size' => 'required|string',
            ];

            if ($this->binding_type === 'hardbound') {
                $rules['cover_color'] = 'required|string';
                $rules['foil_color'] = 'required|string';

                foreach ($this->required_custom_fields as $field) {
                    $rules["custom_fields_data.{$field}"] = 'required|string|min:2|max:255';
                }
            } else {
                $this->cover_color = 'Clear PVC / Standard Cardstock';
            }

            if ($this->require_pdf_upload) {
                $rules['manuscript_file'] = 'required|file|mimes:pdf|max:51200'; // 50MB max
            }

            $this->validate($rules, [
                'manuscript_file.required' => 'Please upload your print-ready thesis manuscript PDF file.',
                'manuscript_file.mimes' => 'The manuscript file must be a valid PDF document (.pdf).',
                'custom_fields_data.*.required' => 'This field is required for your thesis hardbound cover foil stamping.',
                'cover_color.required' => 'Please select a cover leatherette color.',
                'foil_color.required' => 'Please select a foil stamping color.',
            ]);
        }

        $this->step = 2;
    }

    public function submitOrder(): void
    {
        $this->validate([
            'payment_reference_no' => 'required|string|min:6|max:50',
            'payment_proof_file' => 'nullable|image|max:10240', // 10MB max
        ], [
            'payment_reference_no.required' => 'Please enter your GCash transaction reference number.',
        ]);

        $user = auth()->user();
        $shop = PrintShop::first();

        if (! $user || ! $shop) {
            return;
        }

        $pricing = $this->calculatePricing();
        $isCoverOnly = ($this->fulfillment_type === 'cover_only');
        $totalPages = $isCoverOnly ? $this->preprinted_total_pages : ($this->bw_pages + $this->color_pages);

        DB::transaction(function () use ($user, $shop, $pricing, $isCoverOnly, $totalPages) {
            // Store manuscript PDF
            $manuscriptPath = null;
            $manuscriptName = null;
            if ($this->manuscript_file) {
                $manuscriptPath = $this->manuscript_file->store('orders/manuscripts', 'public');
                $manuscriptName = $this->manuscript_file->getClientOriginalName();
            }

            // Store payment proof screenshot
            $paymentProofPath = null;
            if ($this->payment_proof_file) {
                $paymentProofPath = $this->payment_proof_file->store('orders/payments', 'public');
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'print_shop_id' => $shop->id,
                'customer_id' => $user->id,
                'service_key' => 'thesis_binding',
                'order_status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => Order::PAYMENT_PENDING_VERIFICATION,
                'subtotal_amount' => $pricing['subtotal'] + $pricing['addons_total'],
                'rush_fee_amount' => $pricing['rush_total'],
                'total_amount' => $pricing['grand_total'],
                'is_rush' => $this->is_rush,
                'target_completion_date' => $pricing['target_completion_date'],
                'payment_proof_path' => $paymentProofPath,
                'payment_reference_no' => trim($this->payment_reference_no),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'binding_type' => $this->binding_type,
                'fulfillment_type' => $this->fulfillment_type,
                'is_paper_received' => false,
                'estimated_spine_thickness_mm' => $pricing['estimated_spine_mm'],
                'bw_pages_count' => $isCoverOnly ? 0 : $this->bw_pages,
                'color_pages_count' => $isCoverOnly ? 0 : $this->color_pages,
                'total_pages_count' => $totalPages,
                'cover_color' => $this->binding_type === 'hardbound' ? $this->cover_color : 'Clear PVC / Standard Cardstock',
                'foil_color' => $this->binding_type === 'hardbound' ? $this->foil_color : null,
                'paper_size' => $this->paper_size,
                'copies_count' => $this->copies_count,
                'custom_fields_data' => $this->binding_type === 'hardbound' ? $this->custom_fields_data : null,
                'selected_addons' => $pricing['selected_addons_details'],
                'document_file_path' => $manuscriptPath,
                'document_original_name' => $manuscriptName,
                'unit_price' => $pricing['unit_price'],
                'total_price' => $pricing['subtotal'] + $pricing['addons_total'],
            ]);

            $this->created_order_id = $order->id;
            $this->created_order_number = $order->order_number;
        });

        $this->step = 3;
    }
}; ?>

<div class="min-h-screen w-full bg-stone-950 text-stone-100 font-sans antialiased relative selection:bg-amber-500 selection:text-white pb-24">
    <!-- Ambient Background Glow -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = App\Models\PrintShop::first();
        $pricing = $this->calculatePricing();

        // Check Inventory Stock Levels for Cover Colors
        $inventoryMaterials = $shop ? $shop->inventoryItems()->get() : collect();
        $addonsList = $shop ? $shop->inventoryItems()->readyToSell()->forService('thesis_binding')->get() : collect();
    @endphp

    <!-- Sticky Navigation Topbar -->
    <header class="w-full max-w-5xl mx-auto px-4 py-4 flex items-center justify-between border-b border-stone-800/80 relative z-20">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
            <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform">
                <flux:icon name="book-open" class="size-5 text-stone-950" />
            </span>
            <div>
                <h1 class="text-sm font-extrabold text-white tracking-tight leading-tight">{{ $shop ? $shop->name : 'Printify' }}</h1>
                <span class="text-[10px] text-amber-400 font-bold">Thesis Binding Order Portal</span>
            </div>
        </a>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-xs font-bold text-stone-400 hover:text-white flex items-center gap-1">
                <flux:icon name="arrow-left" class="size-3.5" />
                <span>Back to Dashboard</span>
            </a>
            <div class="size-7 rounded-full bg-amber-500 text-stone-950 text-xs font-extrabold flex items-center justify-center">
                {{ $user?->initials() }}
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="w-full max-w-5xl mx-auto px-4 pt-6 space-y-8 relative z-20">
        <!-- Multi-Step Breadcrumb Progress Bar -->
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 w-full bg-stone-800 z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-amber-500 z-0 transition-all duration-300 {{ $step === 1 ? 'w-0' : ($step === 2 ? 'w-1/2' : 'w-full') }}"></div>

                <!-- Step 1 Indicator -->
                <div class="relative z-10 flex flex-col items-center gap-1">
                    <span class="size-7 sm:size-8 rounded-full flex items-center justify-center text-xs font-black {{ $step >= 1 ? 'bg-amber-500 text-stone-950 shadow-lg shadow-amber-500/30' : 'bg-stone-800 text-stone-400' }}">1</span>
                    <span class="text-[9px] sm:text-[11px] font-bold text-center {{ $step >= 1 ? 'text-white' : 'text-stone-500' }}">
                        1. <span class="hidden sm:inline">Configure & </span>Upload
                    </span>
                </div>

                <!-- Step 2 Indicator -->
                <div class="relative z-10 flex flex-col items-center gap-1">
                    <span class="size-7 sm:size-8 rounded-full flex items-center justify-center text-xs font-black {{ $step >= 2 ? 'bg-amber-500 text-stone-950 shadow-lg shadow-amber-500/30' : 'bg-stone-800 text-stone-400' }}">2</span>
                    <span class="text-[9px] sm:text-[11px] font-bold text-center {{ $step >= 2 ? 'text-white' : 'text-stone-500' }}">
                        2. GCash<span class="hidden sm:inline"> Checkout</span>
                    </span>
                </div>

                <!-- Step 3 Indicator -->
                <div class="relative z-10 flex flex-col items-center gap-1">
                    <span class="size-7 sm:size-8 rounded-full flex items-center justify-center text-xs font-black {{ $step >= 3 ? 'bg-emerald-500 text-stone-950 shadow-lg shadow-emerald-500/30' : 'bg-stone-800 text-stone-400' }}">3</span>
                    <span class="text-[9px] sm:text-[11px] font-bold text-center {{ $step >= 3 ? 'text-emerald-400' : 'text-stone-500' }}">
                        3. Confirmed
                    </span>
                </div>
            </div>
        </div>

        <!-- STEP 1: CONFIGURE SPECIFICATIONS, COVER & MANUSCRIPT -->
        @if ($step === 1)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-fadeIn">
                <!-- Left 2 Cols: Interactive Configuration Form -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Service Fulfillment Mode Switcher -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-4 shadow-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                                <flux:icon name="sparkles" class="size-4" />
                                1. Service Fulfillment Mode
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-800 text-stone-300">
                                Step 1 of 3
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                wire:click="$set('fulfillment_type', 'full_package')"
                                class="p-4 rounded-2xl border text-left transition-all space-y-2 {{ $fulfillment_type === 'full_package' ? 'bg-amber-500/15 border-amber-500 text-white shadow-md shadow-amber-500/10' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black">📄 Full Package (Print & Bind)</span>
                                    <flux:icon name="{{ $fulfillment_type === 'full_package' ? 'check-circle' : 'plus-circle' }}" class="size-4 {{ $fulfillment_type === 'full_package' ? 'text-amber-400' : 'text-stone-600' }}" />
                                </div>
                                <p class="text-[11px] text-stone-300">We print your manuscript PDF pages and assemble the complete hardbound/softbound cover.</p>
                                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300">Complete Printing Service</span>
                            </button>

                            @if ($allow_customer_supplied_paper)
                                <button
                                    type="button"
                                    wire:click="$set('fulfillment_type', 'cover_only')"
                                    class="p-4 rounded-2xl border text-left transition-all space-y-2 {{ $fulfillment_type === 'cover_only' ? 'bg-amber-500/15 border-amber-500 text-white shadow-md shadow-amber-500/10' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black">📦 Cover & Binding Only (Dala ang Papel)</span>
                                        <flux:icon name="{{ $fulfillment_type === 'cover_only' ? 'check-circle' : 'plus-circle' }}" class="size-4 {{ $fulfillment_type === 'cover_only' ? 'text-amber-400' : 'text-stone-600' }}" />
                                    </div>
                                    <p class="text-[11px] text-stone-300">You supply your pre-printed pages; we build the custom hardbound cover, foil stamp & bind.</p>
                                    <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300">₱0.00 Print Charges</span>
                                </button>
                            @endif
                        </div>

                        @if ($fulfillment_type === 'cover_only')
                            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-3">
                                <flux:icon name="information-circle" class="size-5 shrink-0 text-amber-400" />
                                <p class="leading-relaxed text-[11px]">
                                    <strong>Walk-in Paper Drop-off Required:</strong> Bring your printed & arranged manuscript pages to our counter with your Order Number upon checkout.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Card 2: Binding Specifications & Dimensions -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-5 shadow-xl">
                        <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="book-open" class="size-4" />
                            2. {{ $fulfillment_type === 'cover_only' ? 'Hardbound Sizing & Dimensions' : 'Binding Type & Paper Standards' }}
                        </h3>

                        @if ($fulfillment_type === 'full_package')
                            <!-- Hardbound vs Softbound Toggle Buttons -->
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    wire:click="$set('binding_type', 'hardbound')"
                                    class="p-4 rounded-2xl border text-left transition-all space-y-1 {{ $binding_type === 'hardbound' ? 'bg-amber-500/15 border-amber-500 text-white shadow-md shadow-amber-500/10' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black">Hardbound Compilation</span>
                                        <flux:icon name="{{ $binding_type === 'hardbound' ? 'check-circle' : 'plus-circle' }}" class="size-4 {{ $binding_type === 'hardbound' ? 'text-amber-400' : 'text-stone-600' }}" />
                                    </div>
                                    <div class="text-lg font-black text-white">₱{{ number_format($hardbound_base_price, 2) }} <span class="text-[10px] font-normal text-stone-400">base</span></div>
                                    <p class="text-[10px] text-stone-400">Heavy duty chipboard, leatherette wrap & hot foil stamping</p>
                                </button>

                                <button
                                    type="button"
                                    wire:click="$set('binding_type', 'softbound')"
                                    class="p-4 rounded-2xl border text-left transition-all space-y-1 {{ $binding_type === 'softbound' ? 'bg-amber-500/15 border-amber-500 text-white shadow-md shadow-amber-500/10' : 'bg-stone-950 border-stone-800 text-stone-400 hover:text-white' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black">Softbound / Bookbinding</span>
                                        <flux:icon name="{{ $binding_type === 'softbound' ? 'check-circle' : 'plus-circle' }}" class="size-4 {{ $binding_type === 'softbound' ? 'text-amber-400' : 'text-stone-600' }}" />
                                    </div>
                                    <div class="text-lg font-black text-white">₱{{ number_format($softbound_base_price, 2) }} <span class="text-[10px] font-normal text-stone-400">base</span></div>
                                    <p class="text-[10px] text-stone-400">Flexible coated cardstock cover with thermal binding spine</p>
                                </button>
                            </div>
                        @else
                            <div class="p-3.5 rounded-2xl bg-stone-950 border border-stone-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-white block">Hardbound Leatherette Cover + Hot Foil Stamping</span>
                                    <span class="text-[10px] text-stone-400">Chipboard hard cover custom tailored to your paper thickness</span>
                                </div>
                                <span class="text-sm font-black text-amber-400 font-mono">₱{{ number_format($hardbound_cover_only_price, 2) }} <span class="text-[10px] font-normal text-stone-500">/ book</span></span>
                            </div>
                        @endif

                        <!-- Paper Sizes Grid -->
                        <div class="space-y-1.5 pt-2">
                            <label class="text-xs font-bold text-stone-300">Paper Dimension Standard</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($available_paper_sizes as $size)
                                    <button
                                        type="button"
                                        wire:click="$set('paper_size', '{{ $size }}')"
                                        class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center {{ $paper_size === $size ? 'bg-amber-500 text-stone-950 border-amber-500 shadow' : 'bg-stone-950 border-stone-800 text-stone-300 hover:text-white' }}"
                                    >
                                        {{ $size }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Page Counts & Volume -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-5 shadow-xl">
                        <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="document-text" class="size-4" />
                            3. {{ $fulfillment_type === 'cover_only' ? 'Pre-Printed Page Volume & Spine Sizing' : 'Manuscript Page Counts & Copies' }}
                        </h3>

                        @if ($fulfillment_type === 'full_package')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- B&W Pages Input -->
                                <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-stone-300">Black & White Pages</span>
                                        <span class="text-stone-400 font-mono">₱{{ number_format($page_price_bw, 2) }}/page</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input wire:model.live="bw_pages" type="number" min="0" max="1000" class="w-24 px-3 py-2 rounded-xl bg-stone-900 border border-stone-700 text-sm font-black text-white focus:outline-none focus:border-amber-500" />
                                        <input wire:model.live="bw_pages" type="range" min="0" max="300" class="w-full accent-amber-500" />
                                    </div>
                                </div>

                                <!-- Color Pages Input -->
                                <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-stone-300">Full-Color Pages</span>
                                        <span class="text-amber-400 font-mono">₱{{ number_format($page_price_color, 2) }}/page</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input wire:model.live="color_pages" type="number" min="0" max="500" class="w-24 px-3 py-2 rounded-xl bg-stone-900 border border-stone-700 text-sm font-black text-white focus:outline-none focus:border-amber-500" />
                                        <input wire:model.live="color_pages" type="range" min="0" max="150" class="w-full accent-amber-500" />
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Cover Only: Total Pre-printed Pages for Spine Sizing -->
                            <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <div>
                                        <span class="font-bold text-stone-300 block">Total Pre-Printed Pages Supplied</span>
                                        <span class="text-[10px] text-stone-500">Needed to measure exact spine thickness for the cover</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        Spine: ~{{ $pricing['estimated_spine_mm'] }} mm
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input wire:model.live="preprinted_total_pages" type="number" min="10" max="1000" class="w-28 px-3 py-2 rounded-xl bg-stone-900 border border-stone-700 text-sm font-black text-white focus:outline-none focus:border-amber-500" />
                                    <input wire:model.live="preprinted_total_pages" type="range" min="20" max="400" class="w-full accent-amber-500" />
                                </div>
                            </div>
                        @endif

                        <!-- Copies Multiplier -->
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-stone-950 border border-stone-800">
                            <div>
                                <span class="text-xs font-bold text-white block">Number of Book Copies</span>
                                <span class="text-[10px] text-stone-500">Duplicate physical bound books</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="$set('copies_count', {{ max(1, $copies_count - 1) }})" class="size-8 rounded-lg bg-stone-800 text-stone-300 hover:text-white font-black">-</button>
                                <span class="w-8 text-center text-sm font-black text-white">{{ $copies_count }}</span>
                                <button type="button" wire:click="$set('copies_count', {{ min(50, $copies_count + 1) }})" class="size-8 rounded-lg bg-stone-800 text-stone-300 hover:text-white font-black">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Color & Stamping Customization -->
                    @if ($binding_type === 'hardbound')
                        <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-5 shadow-xl transition-all">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                                    <flux:icon name="swatch" class="size-4" />
                                    4. Cover Colors & Hot Foil Stamping
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    Hardbound
                                </span>
                            </div>

                            <!-- Cover Colors with Inventory Stock Check -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-stone-300">Cover Leatherette Color</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    @foreach ($available_cover_colors as $color)
                                        @php
                                            $matchedMaterial = $inventoryMaterials->first(function ($mat) use ($color) {
                                                return str_contains(strtolower($mat->name), strtolower($color));
                                            });
                                            $isOutOfStock = $matchedMaterial ? $matchedMaterial->isOutOfStock() : false;
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="$set('cover_color', '{{ $color }}')"
                                            @disabled($isOutOfStock)
                                            class="p-3 rounded-2xl border text-left transition-all relative {{ $cover_color === $color ? 'bg-amber-500/20 border-amber-500 text-white' : ($isOutOfStock ? 'opacity-40 bg-stone-950 border-stone-800 text-stone-600 cursor-not-allowed' : 'bg-stone-950 border-stone-800 text-stone-300 hover:text-white') }}"
                                        >
                                            <div class="text-xs font-bold">{{ $color }}</div>
                                            @if ($isOutOfStock)
                                                <span class="text-[9px] font-black text-red-400 block mt-0.5">Out of Stock</span>
                                            @else
                                                <span class="text-[9px] text-emerald-400 block mt-0.5">Available</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Foil Color Stamping -->
                            <div class="space-y-2 pt-2 border-t border-stone-800/80">
                                <label class="text-xs font-bold text-stone-300">Foil Stamping Text Color</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($available_foil_colors as $fColor)
                                        <button
                                            type="button"
                                            wire:click="$set('foil_color', '{{ $fColor }}')"
                                            class="px-4 py-2 rounded-xl text-xs font-bold border transition-all {{ $foil_color === $fColor ? 'bg-amber-500 text-stone-950 border-amber-500 shadow' : 'bg-stone-950 border-stone-800 text-stone-300 hover:text-white' }}"
                                        >
                                            ✨ {{ $fColor }} Foil
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Greyed Out Card 3 for Softbound -->
                        <div class="p-6 rounded-3xl bg-stone-900/40 border border-dashed border-stone-800 space-y-3 opacity-60">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-stone-400 uppercase tracking-wider flex items-center gap-2">
                                    <flux:icon name="swatch" class="size-4 text-stone-500" />
                                    4. Cover Colors & Hot Foil Stamping
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-800 text-stone-400 border border-stone-700">
                                    Not Applicable
                                </span>
                            </div>
                            <p class="text-xs text-stone-400 leading-relaxed">
                                Softbound utilizes a <strong>transparent clear PVC acetate front sheet</strong> with standard cardstock backing. Leatherette colors and hot foil stamping dies are exclusive to <strong>Hardbound Compilations</strong>.
                            </p>
                        </div>
                    @endif

                    <!-- Card 5: Cover Fields (Hardbound) & PDF Manuscript Upload -->
                    <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-5 shadow-xl">
                        <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="pencil-square" class="size-4" />
                            5. {{ $binding_type === 'hardbound' ? 'Hardbound Cover Foil Text & Reference PDF' : 'Manuscript PDF Document' }}
                        </h3>

                        <!-- Dynamic Cover Fields (Only for Hardbound) -->
                        @if ($binding_type === 'hardbound')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($required_custom_fields as $field)
                                    <div class="space-y-1 {{ str_contains(strtolower($field), 'title') ? 'sm:col-span-2' : '' }}">
                                        <label class="text-xs font-bold text-stone-300">{{ $field }} *</label>
                                        <input
                                            wire:model="custom_fields_data.{{ $field }}"
                                            type="text"
                                            placeholder="Enter {{ strtolower($field) }}..."
                                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-950 border border-stone-800 text-xs text-stone-100 placeholder-stone-600 focus:border-amber-500 focus:outline-none"
                                            required
                                        />
                                        @error("custom_fields_data.{$field}")
                                            <span class="text-red-400 text-[10px]">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- PDF Manuscript File Uploader -->
                        <div class="space-y-2 pt-2 border-t border-stone-800/80">
                            <label class="text-xs font-bold text-stone-300 flex items-center justify-between">
                                <span>{{ $fulfillment_type === 'cover_only' ? 'Upload Digital PDF Copy (for double checking & spine sizing)' : 'Upload Manuscript PDF Document *' }}</span>
                                <span class="text-[10px] text-stone-500">PDF only, up to 50MB</span>
                            </label>

                            <div class="p-5 rounded-2xl bg-stone-950 border-2 border-dashed border-stone-800 hover:border-amber-500/50 transition-colors text-center space-y-2">
                                <flux:icon name="arrow-up-tray" class="size-8 mx-auto text-amber-400" />
                                <div class="text-xs text-stone-300 font-bold">
                                    @if ($manuscript_file)
                                        <span class="text-emerald-400 font-extrabold">{{ $manuscript_file->getClientOriginalName() }}</span>
                                    @else
                                        <span>Click to browse or drag & drop your thesis PDF</span>
                                    @endif
                                </div>
                                <input wire:model="manuscript_file" type="file" accept=".pdf" class="text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-stone-800 file:text-amber-400 hover:file:bg-stone-700 cursor-pointer" />
                            </div>
                            @error('manuscript_file') <span class="text-red-400 text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Card 6: Retail Add-ons Selection -->
                    @if ($addonsList->isNotEmpty())
                        <div class="p-6 rounded-3xl bg-stone-900/80 border border-stone-800 space-y-4 shadow-xl">
                            <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider flex items-center gap-2">
                                <flux:icon name="tag" class="size-4" />
                                6. Optional Accessories & Add-ons
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($addonsList as $addon)
                                    @php
                                        $isAddonOut = $addon->isOutOfStock();
                                        $isChecked = in_array($addon->id, $selected_addon_ids);
                                    @endphp
                                    <label class="p-3.5 rounded-2xl border transition-all flex items-center justify-between cursor-pointer {{ $isChecked ? 'bg-amber-500/15 border-amber-500' : ($isAddonOut ? 'opacity-40 bg-stone-950 border-stone-800 cursor-not-allowed' : 'bg-stone-950 border-stone-800 hover:border-stone-700') }}">
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selected_addon_ids"
                                                value="{{ $addon->id }}"
                                                @disabled($isAddonOut)
                                                class="rounded accent-amber-500 size-4 bg-stone-900 border-stone-700"
                                            />
                                            <div>
                                                <div class="text-xs font-bold text-white">{{ $addon->name }}</div>
                                                <div class="text-[10px] text-stone-500">₱{{ number_format($addon->selling_price ?? 0, 2) }} / pc</div>
                                            </div>
                                        </div>
                                        @if ($isAddonOut)
                                            <span class="text-[9px] font-black text-red-400">Out of Stock</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right 1 Col: Live Price Breakdown & Sticky Action Button -->
                <div class="space-y-6">
                    <div class="sticky top-6 p-6 rounded-3xl border border-amber-500/40 bg-gradient-to-b from-stone-900 to-stone-950 space-y-6 shadow-2xl">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-extrabold uppercase border border-amber-500/30">Live Quotation</span>
                            <h3 class="text-lg font-black text-white mt-1">Order Summary</h3>
                            <p class="text-[11px] text-stone-400">
                                {{ $fulfillment_type === 'cover_only' ? 'Cover & Binding Only (Customer Pages)' : 'Full Print & Binding Package' }}
                            </p>
                        </div>

                        <!-- Price Breakdown Rows -->
                        <div class="divide-y divide-stone-800/80 text-xs space-y-2.5">
                            <div class="flex justify-between pt-2">
                                <span class="text-stone-400">Base {{ $fulfillment_type === 'cover_only' ? 'Hardbound Cover' : ucfirst($binding_type) }}:</span>
                                <span class="font-bold text-white">₱{{ number_format($pricing['base_price'], 2) }}</span>
                            </div>
                            @if ($fulfillment_type === 'full_package')
                                <div class="flex justify-between pt-2">
                                    <span class="text-stone-400">B&W Pages ({{ $bw_pages }} &times; ₱{{ number_format($page_price_bw, 2) }}):</span>
                                    <span class="font-bold text-white">₱{{ number_format($pricing['bw_total'], 2) }}</span>
                                </div>
                                <div class="flex justify-between pt-2">
                                    <span class="text-stone-400">Color Pages ({{ $color_pages }} &times; ₱{{ number_format($page_price_color, 2) }}):</span>
                                    <span class="font-bold text-white">₱{{ number_format($pricing['color_total'], 2) }}</span>
                                </div>
                            @else
                                <div class="flex justify-between pt-2 text-emerald-400">
                                    <span>Printed Pages ({{ $preprinted_total_pages }} pages):</span>
                                    <span>₱0.00 (Supplied)</span>
                                </div>
                                <div class="flex justify-between pt-2 text-stone-400">
                                    <span>Est. Spine Width:</span>
                                    <span class="font-mono font-bold text-stone-200">~{{ $pricing['estimated_spine_mm'] }} mm</span>
                                </div>
                            @endif
                            <div class="flex justify-between pt-2">
                                <span class="text-stone-400">Book Copies:</span>
                                <span class="font-bold text-amber-400">&times; {{ $copies_count }}</span>
                            </div>
                            @if ($pricing['addons_total'] > 0)
                                <div class="flex justify-between pt-2 text-cyan-300">
                                    <span>Add-ons:</span>
                                    <span>+₱{{ number_format($pricing['addons_total'], 2) }}</span>
                                </div>
                            @endif
                            @if ($is_rush)
                                <div class="flex justify-between pt-2 text-amber-400">
                                    <span>Rush Order Fee:</span>
                                    <span>+₱{{ number_format($pricing['rush_total'], 2) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Grand Total -->
                        <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 flex items-center justify-between">
                            <span class="text-xs font-bold text-stone-300">Total Payable:</span>
                            <span class="text-2xl font-black text-amber-400">₱{{ number_format($pricing['grand_total'], 2) }}</span>
                        </div>

                        <!-- Rush Checkbox -->
                        <div class="p-3.5 rounded-2xl bg-stone-950/80 border border-stone-800 space-y-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model.live="is_rush" type="checkbox" class="rounded accent-amber-500 size-4" />
                                <span class="text-xs font-extrabold text-white">Expedited Rush Order (+₱{{ number_format($rush_fee, 2) }})</span>
                            </label>
                            <p class="text-[10px] text-stone-400 pl-6">
                                Expected Ready: <strong class="text-emerald-400">{{ $pricing['target_completion_date']->format('M d, Y (D)') }}</strong>
                            </p>
                        </div>

                        <!-- Action Button -->
                        <button
                            type="button"
                            wire:click="proceedToCheckout"
                            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-stone-950 text-xs font-black uppercase tracking-wider shadow-lg shadow-amber-500/20 transition-all cursor-pointer"
                        >
                            Proceed to GCash Checkout &rarr;
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- STEP 2: GCASH PAYMENT & RECEIPT UPLOAD -->
        @if ($step === 2)
            <div class="max-w-2xl mx-auto space-y-6 animate-fadeIn">
                <div class="p-8 rounded-3xl bg-stone-900/90 border border-stone-800 space-y-6 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-2xl bg-blue-500 text-white font-black shadow-lg shadow-blue-500/20">
                                ₱
                            </span>
                            <div>
                                <h3 class="text-lg font-black text-white">GCash Payment Verification</h3>
                                <p class="text-xs text-stone-400">Send exact amount to the print shop and submit reference number</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-stone-500 block">Total Due</span>
                            <span class="text-xl font-black text-amber-400">₱{{ number_format($pricing['grand_total'], 2) }}</span>
                        </div>
                    </div>

                    <!-- GCash Account Details Box -->
                    <div class="p-5 rounded-2xl bg-stone-950 border border-stone-800 space-y-3" x-data="{ copied: false }">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-400">GCash Merchant Details</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300">Official E-Wallet</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-stone-500 text-[10px] block">Account Name:</span>
                                <strong class="text-white">{{ $shop ? $shop->name : 'Apex Print Hub' }}</strong>
                            </div>
                            <div>
                                <span class="text-stone-500 text-[10px] block">GCash Number:</span>
                                <strong class="text-white font-mono">0917 888 4321</strong>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('09178884321'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="w-full py-2 rounded-xl bg-stone-900 hover:bg-stone-800 text-stone-300 text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
                        >
                            <flux:icon name="clipboard-document" class="size-3.5" />
                            <span x-text="copied ? 'Copied 09178884321!' : 'Copy GCash Number (09178884321)'"></span>
                        </button>
                    </div>

                    <!-- Payment Verification Form -->
                    <form wire:submit.prevent="submitOrder" class="space-y-4">
                        <!-- Reference Number -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">GCash 13-Digit Reference Number *</label>
                            <input
                                wire:model="payment_reference_no"
                                type="text"
                                placeholder="e.g. 1002 9481 0293 8"
                                class="w-full px-4 py-3 rounded-xl bg-stone-950 border border-stone-800 font-mono text-sm text-stone-100 placeholder-stone-600 focus:border-amber-500 focus:outline-none"
                                required
                            />
                            @error('payment_reference_no') <span class="text-red-400 text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>

                        <!-- Proof Screenshot -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-stone-300">Upload GCash Receipt Screenshot <span class="text-stone-500 font-normal">(Optional)</span></label>
                            <input wire:model="payment_proof_file" type="file" accept="image/*" class="w-full text-xs text-stone-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-stone-800 file:text-white hover:file:bg-stone-700 cursor-pointer" />
                            @error('payment_proof_file') <span class="text-red-400 text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-stone-800">
                            <button type="button" wire:click="$set('step', 1)" class="px-5 py-2.5 rounded-xl border border-stone-800 text-stone-300 hover:text-white text-xs font-bold">
                                &larr; Back to Edit Details
                            </button>
                            <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-stone-950 text-xs font-black shadow-lg shadow-emerald-500/20 hover:scale-[1.02] transition-transform cursor-pointer">
                                Confirm & Submit Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- STEP 3: ORDER CONFIRMED & TRACKING STEPPER -->
        @if ($step === 3)
            <div class="max-w-xl mx-auto space-y-6 text-center animate-fadeIn py-6">
                <div class="p-8 rounded-3xl bg-stone-900/90 border border-emerald-500/40 space-y-6 shadow-2xl">
                    <span class="flex size-16 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-600 text-stone-950 mx-auto shadow-xl shadow-emerald-500/30">
                        <flux:icon name="check" class="size-8 stroke-[3]" />
                    </span>

                    <div class="space-y-1">
                        <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase border border-emerald-500/30">Order Submitted</span>
                        <h2 class="text-2xl font-black text-white">Thank You! Order Placed</h2>
                        <p class="text-xs text-stone-400">Your thesis binding order has been logged and queued for payment verification</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-stone-950 border border-stone-800 text-left space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-stone-400">Order Tracking Code:</span>
                            <span class="font-mono font-black text-amber-400 text-sm">{{ $created_order_number }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-stone-400">Package Type:</span>
                            <span class="font-bold text-white">{{ $fulfillment_type === 'cover_only' ? 'Cover & Binding Only (Dala ang Papel)' : 'Full Print & Hardbound Package' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-stone-400">Committed Pickup Date:</span>
                            <span class="font-bold text-white">{{ $pricing['target_completion_date']->format('F d, Y') }}</span>
                        </div>
                    </div>

                    @if ($fulfillment_type === 'cover_only')
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-left text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <flux:icon name="archive-box-arrow-down" class="size-4 text-amber-400" />
                                Next Step: Drop off your physical paper
                            </div>
                            <p class="text-[11px] text-stone-300 leading-relaxed">
                                Please bring your {{ $preprinted_total_pages }} pre-printed and arranged manuscript pages to the print shop counter. Mention Order Tracking Code <strong>{{ $created_order_number }}</strong>.
                            </p>
                        </div>
                    @endif

                    <div class="pt-2">
                        <a href="{{ route('dashboard') }}" wire:navigate class="w-full block py-3.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-stone-950 text-xs font-black shadow-lg shadow-amber-500/20 transition-all">
                            View Order in Customer Portal &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>
