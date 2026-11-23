<?php

use App\Models\DocumentPrintingConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Order Document Printing')] class extends Component {
    use WithFileUploads;

    #[\Livewire\Attributes\Locked]
    public int $shopId = 0;

    public function selectedShop(): PrintShop
    {
        $shop = PrintShop::where('approval_status', 'approved')->where('is_setup_completed', true)->findOrFail($this->shopId);
        $service = $shop->services()->where('service_key', 'document_printing')->firstOrFail();
        abort_unless($service->acceptsOrders(), 422, 'This service is not accepting orders. Please browse other shops.');
        return $shop;
    }

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public int $step = 1; // 1 = Configure, 2 = Checkout, 3 = Confirmation

    // Dynamic Shop Config Rates
    #[\Livewire\Attributes\Locked]
    public float $page_price_bw_short = 1.50;
    #[\Livewire\Attributes\Locked]
    public float $page_price_bw_a4 = 1.50;
    #[\Livewire\Attributes\Locked]
    public float $page_price_bw_long = 2.00;
    #[\Livewire\Attributes\Locked]
    public float $page_price_color_short = 5.00;
    #[\Livewire\Attributes\Locked]
    public float $page_price_color_a4 = 5.00;
    #[\Livewire\Attributes\Locked]
    public float $page_price_color_long = 6.00;
    #[\Livewire\Attributes\Locked]
    public float $paper_stock_80gsm_price = 0.50;
    #[\Livewire\Attributes\Locked]
    public float $paper_stock_100gsm_price = 1.50;
    #[\Livewire\Attributes\Locked]
    public int $duplex_discount_percent = 10;
    #[\Livewire\Attributes\Locked]
    public float $staple_price = 2.00;
    #[\Livewire\Attributes\Locked]
    public float $folder_fastener_price = 15.00;
    #[\Livewire\Attributes\Locked]
    public float $ring_bind_base_price = 45.00;
    #[\Livewire\Attributes\Locked]
    public float $booklet_staple_price = 20.00;
    #[\Livewire\Attributes\Locked]
    public float $rush_fee_amount = 50.00;

    // Customer Selection Matrix
    public $document_file;
    public string $document_name = '';
    public int $total_pages = 10;
    public string $color_mode = 'bw'; // 'bw', 'color', 'mixed'
    public string $custom_color_pages = ''; // e.g. "1, 3, 5-8"
    public int $color_pages_count = 0;
    public int $bw_pages_count = 10;

    public string $paper_size = 'Letter'; // 'Letter', 'A4', 'Legal'
    public string $paper_stock = '70gsm'; // '70gsm', '80gsm', '100gsm'
    public string $print_sides = 'simplex'; // 'simplex', 'duplex'
    public string $finishing_type = 'loose'; // 'loose', 'staple', 'folder', 'ring_bind', 'booklet'
    public string $ring_back_cover_color = 'Blue';
    public int $copies_count = 1;
    public bool $is_rush = false;
    public string $special_instructions = '';

    // Checkout Fields
    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $payment_method = 'gcash'; // 'gcash', 'maya', 'cash_counter'
    public string $payment_reference_no = '';
    public $payment_proof;
    #[\Livewire\Attributes\Locked]
    public ?string $uploaded_file_path = null;
    #[\Livewire\Attributes\Locked]
    public ?string $uploaded_payment_proof_path = null;

    // Generated Order
    #[\Livewire\Attributes\Locked]
    public ?Order $created_order = null;

    public function mount(int $shop): void
    {
        $this->shopId = $shop;
        $user = auth()->user();
        if ($user) {
            $this->customer_name = $user->name;
            $this->customer_email = $user->email;
        }

        $shop = $this->selectedShop();
        if ($shop) {
            $config = DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();
            if ($config) {
                $this->page_price_bw_short = $config->page_price_bw_short;
                $this->page_price_bw_a4 = $config->page_price_bw_a4;
                $this->page_price_bw_long = $config->page_price_bw_long;
                $this->page_price_color_short = $config->page_price_color_short;
                $this->page_price_color_a4 = $config->page_price_color_a4;
                $this->page_price_color_long = $config->page_price_color_long;
                $this->paper_stock_80gsm_price = $config->paper_stock_80gsm_price;
                $this->paper_stock_100gsm_price = $config->paper_stock_100gsm_price;
                $this->duplex_discount_percent = $config->duplex_discount_percent;
                $this->staple_price = $config->staple_price;
                $this->folder_fastener_price = $config->folder_fastener_price;
                $this->ring_bind_base_price = $config->ring_bind_base_price;
                $this->booklet_staple_price = $config->booklet_staple_price;
                $this->rush_fee_amount = $config->rush_fee_amount;
            }
        }

        $this->recalculatePages();
    }

    public function updatedTotalPages(): void
    {
        $this->total_pages = max(1, (int) $this->total_pages);
        $this->recalculatePages();
    }

    public function updatedColorMode(): void
    {
        $this->recalculatePages();
    }

    public function updatedCustomColorPages(): void
    {
        $this->recalculatePages();
    }

    public function updatedDocumentFile(): void
    {
        if ($this->document_file) {
            $this->document_name = $this->document_file->getClientOriginalName();
            $this->uploaded_file_path = $this->document_file->store('customer_documents', 'local');
        }
    }

    public function updatedPaymentProof(): void
    {
        if ($this->payment_proof) {
            $this->uploaded_payment_proof_path = $this->payment_proof->store('orders/payments', 'local');
        }
    }

    /**
     * Parse custom color pages and update page breakdown.
     */
    public function recalculatePages(): void
    {
        if ($this->color_mode === 'bw') {
            $this->color_pages_count = 0;
            $this->bw_pages_count = $this->total_pages;
        } elseif ($this->color_mode === 'color') {
            $this->color_pages_count = $this->total_pages;
            $this->bw_pages_count = 0;
        } else {
            // Mixed Mode: Parse ranges like "1, 3, 5-8"
            $colorPages = [];
            $parts = explode(',', $this->custom_color_pages);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (str_contains($trimmed, '-')) {
                    $range = explode('-', $trimmed);
                    if (count($range) === 2) {
                        $start = (int) trim($range[0]);
                        $end = (int) trim($range[1]);
                        if ($start > 0 && $end >= $start) {
                            for ($i = $start; $i <= min($this->total_pages, $end); $i++) {
                                $colorPages[$i] = true;
                            }
                        }
                    }
                } elseif (is_numeric($trimmed)) {
                    $num = (int) $trimmed;
                    if ($num > 0 && $num <= $this->total_pages) {
                        $colorPages[$num] = true;
                    }
                }
            }

            $this->color_pages_count = count($colorPages);
            $this->bw_pages_count = max(0, $this->total_pages - $this->color_pages_count);
        }
    }

    /**
     * Compute exact total price.
     *
     * @return array{
     *     bw_rate: float,
     *     color_rate: float,
     *     bw_subtotal: float,
     *     color_subtotal: float,
     *     pages_gross: float,
     *     duplex_discount: float,
     *     paper_stock_fee: float,
     *     finishing_fee: float,
     *     per_copy_total: float,
     *     copies_subtotal: float,
     *     rush_fee: float,
     *     final_total: float,
     *     physical_sheets_per_copy: int,
     *     total_physical_sheets: int
     * }
     */
    public function calculatePricing(): array
    {
        $bwRate = match ($this->paper_size) {
            'A4' => $this->page_price_bw_a4,
            'Legal', 'Long' => $this->page_price_bw_long,
            default => $this->page_price_bw_short,
        };

        $colorRate = match ($this->paper_size) {
            'A4' => $this->page_price_color_a4,
            'Legal', 'Long' => $this->page_price_color_long,
            default => $this->page_price_color_short,
        };

        $bwSubtotal = $this->bw_pages_count * $bwRate;
        $colorSubtotal = $this->color_pages_count * $colorRate;
        $pagesGross = $bwSubtotal + $colorSubtotal;

        // Duplex discount (applied to page printing cost)
        $duplexDiscount = 0.0;
        if ($this->print_sides === 'duplex') {
            $duplexDiscount = $pagesGross * ($this->duplex_discount_percent / 100);
        }

        // Physical paper sheets count
        $physicalSheetsPerCopy = $this->print_sides === 'duplex'
            ? (int) ceil($this->total_pages / 2)
            : $this->total_pages;

        // Paper stock surcharge
        $stockUnitFee = match ($this->paper_stock) {
            '80gsm' => $this->paper_stock_80gsm_price,
            '100gsm' => $this->paper_stock_100gsm_price,
            default => 0.0,
        };
        $paperStockFee = $physicalSheetsPerCopy * $stockUnitFee;

        // Finishing fee per copy
        $finishingFee = match ($this->finishing_type) {
            'staple' => $this->staple_price,
            'folder' => $this->folder_fastener_price,
            'ring_bind' => $this->ring_bind_base_price,
            'booklet' => $this->booklet_staple_price,
            default => 0.0,
        };

        $perCopyTotal = ($pagesGross - $duplexDiscount) + $paperStockFee + $finishingFee;
        $copiesSubtotal = $perCopyTotal * max(1, $this->copies_count);
        $rushFee = $this->is_rush ? $this->rush_fee_amount : 0.0;
        $finalTotal = $copiesSubtotal + $rushFee;

        return [
            'bw_rate' => $bwRate,
            'color_rate' => $colorRate,
            'bw_subtotal' => round($bwSubtotal, 2),
            'color_subtotal' => round($colorSubtotal, 2),
            'pages_gross' => round($pagesGross, 2),
            'duplex_discount' => round($duplexDiscount, 2),
            'paper_stock_fee' => round($paperStockFee, 2),
            'finishing_fee' => round($finishingFee, 2),
            'per_copy_total' => round($perCopyTotal, 2),
            'copies_subtotal' => round($copiesSubtotal, 2),
            'rush_fee' => round($rushFee, 2),
            'final_total' => round($finalTotal, 2),
            'physical_sheets_per_copy' => $physicalSheetsPerCopy,
            'total_physical_sheets' => $physicalSheetsPerCopy * max(1, $this->copies_count),
        ];
    }

    public function proceedToCheckout(): void
    {
        $maxUploadKb = PlatformSetting::current()->max_upload_mb * 1024;

        $this->validate([
            'total_pages' => 'required|integer|min:1|max:5000',
            'copies_count' => 'required|integer|min:1|max:500',
            'paper_size' => 'required|in:Letter,A4,Legal',
            'paper_stock' => 'required|in:70gsm,80gsm,100gsm',
            'print_sides' => 'required|in:simplex,duplex',
            'finishing_type' => 'required|in:loose,staple,folder,ring_bind,booklet',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx|max:'.$maxUploadKb,
        ]);

        $this->step = 2;
    }

    public function submitOrder(): void
    {
        $this->proceedToCheckout();
        $this->recalculatePages();
        $this->validate([
            'customer_name' => 'required|string|min:2',
            'customer_email' => 'required|email',
            'payment_method' => 'required|in:gcash,maya,cash_counter',
        ]);

        $shop = $this->selectedShop();
        if (! $shop) {
            return;
        }

        $normalizedRef = strtoupper(trim($this->payment_reference_no));
        if ($this->payment_method !== 'cash_counter' && $normalizedRef !== '') {
            $duplicateRef = Payment::query()
                ->where('print_shop_id', $shop->id)
                ->where('reference_no', $normalizedRef)
                ->exists();

            if ($duplicateRef) {
                throw ValidationException::withMessages([
                    'payment_reference_no' => 'This payment reference number has already been submitted for this print shop.',
                ]);
            }
        }

        $user = auth()->user();
        $pricing = $this->calculatePricing();

        // Handle file storage if uploaded
        $filePath = $this->uploaded_file_path;
        if (! $filePath && $this->document_file) {
            $filePath = $this->document_file->store('customer_documents', 'local');
        }

        $paymentProofPath = $this->uploaded_payment_proof_path;
        if (! $paymentProofPath && $this->payment_proof) {
            $paymentProofPath = $this->payment_proof->store('orders/payments', 'local');
        }

        // Generate Order
        $orderNumber = 'DOC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $isCounter = $this->payment_method === 'cash_counter';
        $order = Order::create([
            'order_number' => $orderNumber,
            'tracking_code' => Order::generateTrackingCode(),
            'print_shop_id' => $shop->id,
            'customer_id' => $user?->id ?? 1,
            'service_key' => 'document_printing',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => $isCounter ? Order::PAYMENT_UNPAID : Order::PAYMENT_PENDING_VERIFICATION,
            'payment_option' => $isCounter ? Order::PAYMENT_OPTION_COUNTER_OFFLINE : Order::PAYMENT_OPTION_FULL_100,
            'subtotal_amount' => $pricing['copies_subtotal'],
            'rush_fee_amount' => $pricing['rush_fee'],
            'total_amount' => $pricing['final_total'],
            'amount_paid' => 0.0,
            'balance_due' => $pricing['final_total'],
            'refund_due' => 0.0,
            'refund_status' => Order::REFUND_NONE,
            'is_rush' => $this->is_rush,
            'production_stage' => Order::STAGE_QUEUE,
            'payment_reference_no' => $normalizedRef !== '' ? $normalizedRef : $this->payment_reference_no,
            'payment_proof_path' => $paymentProofPath,
            'target_completion_date' => $this->is_rush ? Carbon::now()->addHours(3) : Carbon::now()->addHours(24),
            'staff_notes' => $this->special_instructions,
        ]);

        // Create OrderItem
        OrderItem::create([
            'order_id' => $order->id,
            'service_key' => 'document_printing',
            'binding_type' => $this->finishing_type === 'ring_bind' ? 'ring_bind' : 'none',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'is_paper_received' => true,
            'estimated_spine_thickness_mm' => round($pricing['physical_sheets_per_copy'] * 0.1, 1),
            'bw_pages_count' => $this->bw_pages_count,
            'color_pages_count' => $this->color_pages_count,
            'total_pages_count' => $this->total_pages,
            'cover_color' => $this->ring_back_cover_color,
            'paper_size' => $this->paper_size,
            'copies_count' => $this->copies_count,
            'document_file_path' => $filePath,
            'document_original_name' => $this->document_name ?: ($filePath ? basename($filePath) : 'Document.pdf'),
            'unit_price' => $pricing['per_copy_total'],
            'total_price' => $pricing['final_total'],
            'custom_fields_data' => [
                'service_key' => 'document_printing',
                'color_mode' => $this->color_mode,
                'custom_color_pages' => $this->custom_color_pages,
                'paper_stock' => $this->paper_stock,
                'print_sides' => $this->print_sides,
                'finishing_type' => $this->finishing_type,
                'ring_back_cover_color' => $this->ring_back_cover_color,
                'physical_sheets_per_copy' => $pricing['physical_sheets_per_copy'],
                'total_physical_sheets' => $pricing['total_physical_sheets'],
            ],
        ]);

        if (! $isCounter && $normalizedRef !== '') {
            Payment::create([
                'order_id' => $order->id,
                'print_shop_id' => $shop->id,
                'submitted_by' => $user?->id,
                'payment_type' => Payment::TYPE_FULL_100,
                'payment_method' => $this->payment_method,
                'amount' => $pricing['final_total'],
                'reference_no' => $normalizedRef,
                'proof_path' => $paymentProofPath,
                'status' => Payment::STATUS_PENDING,
                'submitted_at' => now(),
            ]);
        }

        $this->created_order = $order;
        $this->step = 3;

        Flux::toast(
            text: "Order {$orderNumber} submitted successfully! Your digital job ticket is active.",
            heading: 'Order Placed',
            variant: 'success'
        );
    }
}; ?>

<div class="min-h-screen w-full pq-page relative pb-24">
    @php
        $shop = $this->selectedShop();
        $pricing = $this->calculatePricing();
    @endphp

    <!-- Top Header Navigation -->
    <header class="w-full pq-topbar sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <x-app-back-link :href="route('shops.show', $this->shopId)" :label="$shop->name" :navigate="true" />
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight truncate">Order Document Printing</h1>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 truncate">{{ $shop ? $shop->name : 'PressQueue' }} &bull; High-Speed Document Xerox, Duplex &amp; Ring Binding</p>
                </div>
            </div>

            <!-- Step Indicator & Theme Toggle -->
            <div class="flex items-center gap-3 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="size-7 rounded-full flex items-center justify-center text-xs font-bold tabular-nums {{ $step >= 1 ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }}">1</span>
                    <span class="w-4 h-0.5 {{ $step >= 2 ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-800' }}"></span>
                    <span class="size-7 rounded-full flex items-center justify-center text-xs font-bold tabular-nums {{ $step >= 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }}">2</span>
                    <span class="w-4 h-0.5 {{ $step >= 3 ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-800' }}"></span>
                    <span class="size-7 rounded-full flex items-center justify-center text-xs font-bold tabular-nums {{ $step >= 3 ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }}">3</span>
                </div>
                <x-theme-toggle />
            </div>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="w-full max-w-5xl mx-auto px-4 sm:px-6 pt-6 relative z-20">
        
        <!-- STEP 1: CONFIGURE DOCUMENT & SPECS -->
        @if ($step === 1)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- Left 2 Cols: Form Controls -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. Document Upload Card -->
                    <div class="pq-card p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <flux:icon name="cloud-arrow-up" class="size-4 text-blue-600 dark:text-blue-400" />
                                1. Upload File (PDF / Word)
                            </h3>
                            @if ($document_file)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold pq-badge-success">
                                    File Attached
                                </span>
                            @endif
                        </div>

                        <div class="p-6 border-2 border-dashed border-slate-300 dark:border-slate-800 hover:border-blue-500/60 rounded-2xl text-center bg-slate-50 dark:bg-slate-950/60 transition-colors">
                            <input 
                                type="file" 
                                wire:model="document_file" 
                                id="doc_upload"
                                class="hidden" 
                                accept=".pdf,.doc,.docx,.ppt,.pptx"
                            />
                            <label for="doc_upload" class="cursor-pointer flex flex-col items-center justify-center space-y-2">
                                <flux:icon name="document" class="size-8 text-blue-600 dark:text-blue-400" />
                                <div class="text-xs text-slate-700 dark:text-slate-300">
                                    <span class="font-bold text-blue-600 dark:text-blue-400 hover:underline">Click to browse file</span> or drag and drop
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">PDF, DOCX, PPTX supported (Max 50MB)</p>
                            </label>

                            @if ($document_name)
                                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-800 dark:text-slate-200">
                                    <flux:icon name="check-circle" class="size-4 text-emerald-600 dark:text-emerald-400" />
                                    <span>{{ $document_name }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Manual/Confirmed Page Count -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Total Page Count</label>
                                <input 
                                    wire:model.live.debounce.300ms="total_pages" 
                                    type="number" 
                                    min="1" 
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white font-mono font-bold outline-none tabular-nums"
                                />
                                <span class="text-[10px] text-slate-500 dark:text-slate-400">Total number of pages in the document</span>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Number of Copies</label>
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button"
                                        wire:click="$set('copies_count', {{ max(1, $copies_count - 1) }})"
                                        class="size-9 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-white font-bold hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                    >-</button>
                                    <input 
                                        wire:model.live.debounce.300ms="copies_count" 
                                        type="number" 
                                        min="1" 
                                        class="w-full text-center bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl py-2 text-sm text-slate-900 dark:text-white font-mono font-bold outline-none tabular-nums"
                                    />
                                    <button 
                                        type="button"
                                        wire:click="$set('copies_count', {{ $copies_count + 1 }})"
                                        class="size-9 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-white font-bold hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                    >+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Color Mode & Page Breakdown -->
                    <div class="pq-card p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="swatch" class="size-4 text-purple-600 dark:text-purple-400" />
                            2. Color Mode &amp; Page Breakdown
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button 
                                type="button"
                                wire:click="$set('color_mode', 'bw')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $color_mode === 'bw' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5">
                                    <flux:icon name="adjustments-horizontal" class="size-3.5 text-slate-700 dark:text-slate-300" />
                                    <span>Pure B&amp;W</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">All {{ $total_pages }} pages in monochrome (₱{{ number_format($pricing['bw_rate'], 2) }}/pg)</p>
                            </button>

                            <button 
                                type="button"
                                wire:click="$set('color_mode', 'color')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $color_mode === 'color' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5">
                                    <flux:icon name="swatch" class="size-3.5 text-blue-600 dark:text-blue-400" />
                                    <span>Full Color</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 tabular-nums">All {{ $total_pages }} pages in vibrant color (₱{{ number_format($pricing['color_rate'], 2) }}/pg)</p>
                            </button>

                            <button 
                                type="button"
                                wire:click="$set('color_mode', 'mixed')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $color_mode === 'mixed' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5">
                                    <flux:icon name="paint-brush" class="size-3.5 text-indigo-600 dark:text-indigo-400" />
                                    <span>Custom Mixed</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Specific colored pages + rest B&amp;W</p>
                            </button>
                        </div>

                        @if ($color_mode === 'mixed')
                            <div class="pq-well p-4 space-y-2">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Enter Colored Page Numbers / Ranges</label>
                                <input 
                                    wire:model.live.debounce.300ms="custom_color_pages" 
                                    type="text" 
                                    placeholder="e.g. 1, 5, 10-15"
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 outline-none"
                                />
                                <div class="flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-400 font-medium tabular-nums">
                                    <span>Calculated: <strong class="text-blue-700 dark:text-blue-400">{{ $color_pages_count }} Colored</strong> and <strong class="text-slate-900 dark:text-slate-200">{{ $bw_pages_count }} B&amp;W</strong> pages</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- 3. Paper Size, Weight & Duplex -->
                    <div class="pq-card p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="document-duplicate" class="size-4 text-emerald-600 dark:text-emerald-400" />
                            3. Paper Size, Weight &amp; Sides
                        </h3>

                        <!-- Paper Size -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Paper Size</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['Letter' => 'Short (8.5x11")', 'A4' => 'A4 (8.3x11.7")', 'Legal' => 'Long (8.5x13")'] as $val => $lbl)
                                    <button 
                                        type="button"
                                        wire:click="$set('paper_size', '{{ $val }}')"
                                        class="py-2 px-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer {{ $paper_size === $val ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                                    >
                                        {{ $lbl }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Print Sides (Simplex vs Duplex) -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Printing Sides</label>
                                @if ($print_sides === 'duplex')
                                    <span class="text-[10px] font-bold pq-badge-success px-2 py-0.5 rounded-full inline-flex items-center gap-1 tabular-nums">
                                        <flux:icon name="sparkles" class="size-3" />
                                        <span>Saves {{ ceil($total_pages / 2) }} paper sheets ({{ $duplex_discount_percent }}% OFF)</span>
                                    </span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    type="button"
                                    wire:click="$set('print_sides', 'simplex')"
                                    class="p-3 rounded-xl border text-left transition-colors cursor-pointer {{ $print_sides === 'simplex' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                                >
                                    <p class="font-bold text-xs flex items-center gap-1.5">
                                        <flux:icon name="document" class="size-3.5" />
                                        <span>Single-Sided (Simplex)</span>
                                    </p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">1 page printed per sheet ({{ $total_pages }} sheets)</p>
                                </button>

                                <button 
                                    type="button"
                                    wire:click="$set('print_sides', 'duplex')"
                                    class="p-3 rounded-xl border text-left transition-colors cursor-pointer {{ $print_sides === 'duplex' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                                >
                                    <p class="font-bold text-xs flex items-center gap-1.5">
                                        <flux:icon name="document-duplicate" class="size-3.5" />
                                        <span>Back-to-Back (Duplex)</span>
                                    </p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">2 pages per sheet ({{ ceil($total_pages / 2) }} sheets only)</p>
                                </button>
                            </div>
                        </div>

                        <!-- Paper Stock Weight -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Paper Stock Weight</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button 
                                    type="button"
                                    wire:click="$set('paper_stock', '70gsm')"
                                    class="py-2 px-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer {{ $paper_stock === '70gsm' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    70gsm (Standard)
                                </button>
                                <button 
                                    type="button"
                                    wire:click="$set('paper_stock', '80gsm')"
                                    class="py-2 px-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer tabular-nums {{ $paper_stock === '80gsm' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    80gsm (+₱{{ number_format($paper_stock_80gsm_price, 2) }})
                                </button>
                                <button 
                                    type="button"
                                    wire:click="$set('paper_stock', '100gsm')"
                                    class="py-2 px-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer tabular-nums {{ $paper_stock === '100gsm' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    100gsm (+₱{{ number_format($paper_stock_100gsm_price, 2) }})
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Finishing & Binding Selection -->
                    <div class="pq-card p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <flux:icon name="sparkles" class="size-4 text-blue-600 dark:text-blue-400" />
                            4. Finishing &amp; Binding Services
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button 
                                type="button"
                                wire:click="$set('finishing_type', 'loose')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $finishing_type === 'loose' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5">
                                    <flux:icon name="document" class="size-3.5" />
                                    <span>Loose Sheets</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">No binding / Paper clipped (Free)</p>
                            </button>

                            <button 
                                type="button"
                                wire:click="$set('finishing_type', 'staple')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $finishing_type === 'staple' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5 tabular-nums">
                                    <flux:icon name="paper-clip" class="size-3.5" />
                                    <span>Corner Stapling (+₱{{ number_format($staple_price, 2) }})</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Heavy-duty staple on upper-left</p>
                            </button>

                            <button 
                                type="button"
                                wire:click="$set('finishing_type', 'folder')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $finishing_type === 'folder' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5 tabular-nums">
                                    <flux:icon name="folder" class="size-3.5" />
                                    <span>Sliding Folder &amp; Fastener (+₱{{ number_format($folder_fastener_price, 2) }})</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Clear sliding folder with plastic clip</p>
                            </button>

                            <button 
                                type="button"
                                wire:click="$set('finishing_type', 'ring_bind')"
                                class="p-3.5 rounded-2xl border text-left transition-colors cursor-pointer {{ $finishing_type === 'ring_bind' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:text-slate-200' }}"
                            >
                                <p class="font-bold text-xs flex items-center gap-1.5 tabular-nums">
                                    <flux:icon name="book-open" class="size-3.5" />
                                    <span>Plastic Ring Binding (+₱{{ number_format($ring_bind_base_price, 2) }})</span>
                                </p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">PVC Clear front cover + Morocco back board</p>
                            </button>
                        </div>

                        @if ($finishing_type === 'ring_bind')
                            <div class="pq-well p-4 space-y-2">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Ring Binding Back Cover Color</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach (['Blue', 'Black', 'Green', 'Maroon', 'Red'] as $color)
                                        <button 
                                            type="button"
                                            wire:click="$set('ring_back_cover_color', '{{ $color }}')"
                                            class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer {{ $ring_back_cover_color === $color ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-800' }}"
                                        >
                                            {{ $color }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right 1 Col: Live Floating Quotation Card -->
                <div class="space-y-6">
                    <div class="pq-card p-6 space-y-5 sticky top-20">
                        <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Live Price Quotation</span>
                            <h3 class="text-3xl font-black text-emerald-700 dark:text-emerald-400 font-mono mt-1 tabular-nums">
                                ₱{{ number_format($pricing['final_total'], 2) }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 tabular-nums">
                                For {{ $copies_count }} {{ $copies_count === 1 ? 'copy' : 'copies' }} (₱{{ number_format($pricing['per_copy_total'], 2) }}/copy)
                            </p>
                        </div>

                        <!-- Itemized Breakdown -->
                        <div class="space-y-2.5 text-xs text-slate-700 dark:text-slate-300 tabular-nums">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 dark:text-slate-400">B&amp;W Pages ({{ $bw_pages_count }} pgs):</span>
                                <span class="font-mono font-bold">₱{{ number_format($pricing['bw_subtotal'], 2) }}</span>
                            </div>

                            @if ($color_pages_count > 0)
                                <div class="flex items-center justify-between text-blue-700 dark:text-blue-400">
                                    <span>Colored Pages ({{ $color_pages_count }} pgs):</span>
                                    <span class="font-mono font-bold">₱{{ number_format($pricing['color_subtotal'], 2) }}</span>
                                </div>
                            @endif

                            @if ($pricing['duplex_discount'] > 0)
                                <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-400">
                                    <span>Duplex Discount ({{ $duplex_discount_percent }}%):</span>
                                    <span class="font-mono font-bold">-₱{{ number_format($pricing['duplex_discount'], 2) }}</span>
                                </div>
                            @endif

                            @if ($pricing['paper_stock_fee'] > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">Paper Stock ({{ $paper_stock }}):</span>
                                    <span class="font-mono font-bold">+₱{{ number_format($pricing['paper_stock_fee'], 2) }}</span>
                                </div>
                            @endif

                            @if ($pricing['finishing_fee'] > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">Finishing ({{ ucfirst($finishing_type) }}):</span>
                                    <span class="font-mono font-bold">+₱{{ number_format($pricing['finishing_fee'], 2) }}</span>
                                </div>
                            @endif

                            <!-- Physical Paper Summary -->
                            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-400">
                                <span>Physical Sheets Feed:</span>
                                <span class="font-bold text-slate-900 dark:text-slate-200">{{ $pricing['total_physical_sheets'] }} sheets</span>
                            </div>

                            <!-- Rush Order Option -->
                            <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                                <label class="pq-well flex items-center justify-between cursor-pointer p-2.5">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="is_rush" class="rounded bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-amber-500 focus:ring-0">
                                        <span class="text-xs font-bold text-amber-700 dark:text-amber-400 inline-flex items-center gap-1">
                                            <flux:icon name="bolt" class="size-3.5" />
                                            <span>Rush Order (+₱{{ number_format($rush_fee_amount, 2) }})</span>
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">Ready in 3h</span>
                                </label>
                            </div>
                        </div>

                        <!-- Proceed Button -->
                        <button 
                            type="button"
                            wire:click="proceedToCheckout"
                            class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2"
                        >
                            <span>Proceed to Checkout</span>
                            <flux:icon name="arrow-right" class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- STEP 2: CHECKOUT & PAYMENT -->
        @if ($step === 2)
            <div class="max-w-2xl mx-auto space-y-6">
                <div class="pq-card p-6 space-y-6">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                            <flux:icon name="credit-card" class="size-5 text-blue-600 dark:text-blue-400" />
                            Customer Information &amp; Payment Method
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 tabular-nums">Review your order summary of ₱{{ number_format($pricing['final_total'], 2) }} and select payment</p>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Your Full Name</label>
                                <input 
                                    wire:model="customer_name" 
                                    type="text" 
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Email Address</label>
                                <input 
                                    wire:model="customer_email" 
                                    type="email" 
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none"
                                />
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Select Payment Method</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button 
                                    type="button"
                                    wire:click="$set('payment_method', 'gcash')"
                                    class="p-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer flex items-center justify-center gap-1.5 {{ $payment_method === 'gcash' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    <flux:icon name="qr-code" class="size-4 text-blue-600 dark:text-blue-400" />
                                    <span>GCash QR</span>
                                </button>
                                <button 
                                    type="button"
                                    wire:click="$set('payment_method', 'maya')"
                                    class="p-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer flex items-center justify-center gap-1.5 {{ $payment_method === 'maya' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    <flux:icon name="qr-code" class="size-4 text-emerald-600 dark:text-emerald-400" />
                                    <span>Maya QR</span>
                                </button>
                                <button 
                                    type="button"
                                    wire:click="$set('payment_method', 'cash_counter')"
                                    class="p-3 rounded-xl border text-center transition-colors text-xs font-bold cursor-pointer flex items-center justify-center gap-1.5 {{ $payment_method === 'cash_counter' ? 'bg-blue-50 dark:bg-blue-500/20 border-blue-600 dark:border-blue-500 text-blue-950 dark:text-white' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}"
                                >
                                    <flux:icon name="banknotes" class="size-4 text-slate-700 dark:text-slate-300" />
                                    <span>Cash on Counter</span>
                                </button>
                            </div>
                        </div>

                        @if ($payment_method !== 'cash_counter')
                            <div class="pq-well p-4 space-y-3 text-center">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-300 tabular-nums">Scan QR Code &amp; Send ₱{{ number_format($pricing['final_total'], 2) }}</span>
                                <div class="size-36 mx-auto bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl flex items-center justify-center font-mono text-[10px] text-slate-500 dark:text-slate-400">
                                    [ QR Code Preview ]
                                </div>
                                <div class="space-y-1 text-left max-w-sm mx-auto">
                                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Payment Reference Number</label>
                                    <input 
                                        wire:model="payment_reference_no" 
                                        type="text" 
                                        placeholder="e.g. 1002 9384 1928"
                                        class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl px-3 py-1.5 text-xs text-slate-900 dark:text-white outline-none"
                                    />
                                </div>
                            </div>
                        @endif

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Special Instructions for Staff (Optional)</label>
                            <textarea 
                                wire:model="special_instructions"
                                rows="2"
                                placeholder="e.g. Staple on top center, or specific ring binder color requests..."
                                class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 focus:border-blue-600 dark:focus:border-blue-500 rounded-xl p-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 outline-none"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button 
                            type="button"
                            wire:click="$set('step', 1)"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <flux:icon name="arrow-left" class="size-3.5" />
                            <span>Back to Config</span>
                        </button>

                        <button 
                            type="button"
                            wire:click="submitOrder"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center gap-2 tabular-nums"
                        >
                            <flux:icon name="check" class="size-4" />
                            <span>Confirm &amp; Place Order (₱{{ number_format($pricing['final_total'], 2) }})</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- STEP 3: ORDER CONFIRMATION & DIGITAL JOB TICKET -->
        @if ($step === 3 && $created_order)
            <div class="max-w-xl mx-auto space-y-6 text-center">
                <div class="pq-card p-8 space-y-6">
                    <div class="size-16 rounded-2xl pq-badge-success mx-auto flex items-center justify-center">
                        <flux:icon name="check-badge" class="size-9" />
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Order Placed Successfully!</h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Your document printing job is now in the shop production queue</p>
                    </div>

                    <!-- Digital Order Ticket -->
                    <div class="pq-well p-5 text-left space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <span class="text-xs text-slate-600 dark:text-slate-400">Order Number</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $created_order->order_number }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Service</span>
                            <span class="font-bold text-blue-700 dark:text-blue-400">Document Printing</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Specifications</span>
                            <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $paper_size }} &bull; {{ $paper_stock }} &bull; {{ ucfirst($print_sides) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Finishing</span>
                            <span class="text-slate-800 dark:text-slate-200 font-medium">{{ ucfirst($finishing_type) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Total Amount</span>
                            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 text-sm tabular-nums">₱{{ number_format($created_order->total_amount, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400">Payment Status</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $created_order->paymentStatusBadgeColor() }}">
                                {{ $created_order->paymentStatusLabel() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Production Status</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold pq-badge-info">
                                {{ $created_order->stageLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a 
                            href="{{ route('customer.dashboard') }}" 
                            wire:navigate
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-white transition-colors"
                        >
                            <span>Back to Customer Dashboard</span>
                            <flux:icon name="arrow-right" class="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
