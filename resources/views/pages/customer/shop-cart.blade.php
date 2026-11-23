<?php

use App\Exceptions\InsufficientStockException;
use App\Models\DocumentPrintingConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use App\Models\ThesisBindingConfig;
use App\Services\OrderCheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Multi-Item Cart & Checkout')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $shopId = 0;

    #[Locked]
    public array $active_service_keys = [];

    #[Locked]
    public int $max_upload_mb = 25;

    #[Locked]
    public int $max_binding_sheets = 250;

    #[Locked]
    public bool $require_thesis_pdf = false;

    #[Locked]
    public bool $allow_customer_supplied_paper = true;

    #[Locked]
    public array $available_cover_colors = [];

    #[Locked]
    public array $available_foil_colors = [];

    #[Locked]
    public array $available_paper_sizes = [];

    #[Locked]
    public array $required_custom_fields = [];

    // Step tracker: 1 = Cart Builder, 2 = Payment Proof Submission, 3 = Submitted Confirmation
    public int $step = 1;

    // Draft item builder state
    public string $draft_service_key = 'thesis_binding';

    // Thesis draft inputs
    public string $thesis_fulfillment_type = OrderItem::FULFILLMENT_FULL_PACKAGE;
    public string $thesis_binding_type = 'hardbound';
    public int $thesis_bw_pages = 80;
    public int $thesis_color_pages = 20;
    public int $thesis_preprinted_total_pages = 100;
    public string $thesis_paper_size = 'A4';
    public string $thesis_print_sides = 'simplex';
    public string $thesis_cover_color = 'Maroon';
    public string $thesis_foil_color = 'Gold';
    public int $thesis_copies_count = 1;
    public array $thesis_selected_addon_ids = [];
    public array $thesis_custom_fields_data = [];

    // Document printing draft inputs
    public int $doc_total_pages = 20;
    public string $doc_color_mode = 'bw';
    public string $doc_custom_color_pages = '';
    public string $doc_paper_size = 'A4';
    public string $doc_paper_stock = '70gsm';
    public string $doc_print_sides = 'simplex';
    public string $doc_finishing_type = 'loose';
    public string $doc_ring_back_cover_color = 'Blue';
    public int $doc_copies_count = 1;

    public mixed $draft_document_file = null;

    /** @var array<int, array<string, mixed>> */
    public array $cart_items = [];

    // Order-wide options
    public bool $is_rush = false;
    public string $order_notes = '';
    public string $payment_option = Order::PAYMENT_OPTION_FULL_100;

    // Stock shortage feedback
    public ?string $stock_error_message = null;

    /** @var array<int, array{id: int, name: string, available_qty: float, unit: string, category: string}> */
    public array $stock_fallback_suggestions = [];

    // Step 2: Created order & payment proof submission
    #[Locked]
    public ?int $created_order_id = null;

    public string $payment_method = 'gcash';
    public float $payment_amount = 0.0;
    public string $payment_reference_no = '';
    public mixed $payment_proof_file = null;

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public function selectedShop(): PrintShop
    {
        return PrintShop::where('approval_status', PrintShop::STATUS_APPROVED)
            ->where('is_setup_completed', true)
            ->findOrFail($this->shopId);
    }

    public function mount(int $shop): void
    {
        $this->shopId = $shop;
        $printShop = $this->selectedShop();

        $this->active_service_keys = $printShop->services()
            ->whereIn('service_key', ['thesis_binding', 'document_printing'])
            ->where('is_active', true)
            ->get()
            ->filter->acceptsOrders()
            ->pluck('service_key')
            ->values()
            ->all();

        abort_if($this->active_service_keys === [], 422, 'This print shop has no online printing services currently accepting orders.');

        $this->draft_service_key = in_array('thesis_binding', $this->active_service_keys, true)
            ? 'thesis_binding'
            : $this->active_service_keys[0];

        $this->max_upload_mb = PlatformSetting::current()->max_upload_mb;

        /** @var ThesisBindingConfig|null $thesisConfig */
        $thesisConfig = $printShop->thesisBindingConfig;
        $this->max_binding_sheets = $thesisConfig !== null ? (int) $thesisConfig->max_binding_sheets : 250;
        $this->require_thesis_pdf = (bool) ($thesisConfig?->require_pdf_upload ?? false);
        $this->allow_customer_supplied_paper = (bool) ($thesisConfig?->allow_customer_supplied_paper ?? true);
        $this->available_cover_colors = $thesisConfig?->cover_colors ?? ThesisBindingConfig::defaultCoverColors();
        $this->available_foil_colors = $thesisConfig?->foil_colors ?? ThesisBindingConfig::defaultFoilColors();
        $this->available_paper_sizes = $thesisConfig?->paper_sizes ?? ThesisBindingConfig::defaultPaperSizes();
        $this->required_custom_fields = $thesisConfig?->custom_cover_fields ?? ThesisBindingConfig::defaultCustomCoverFields();

        $this->thesis_cover_color = $this->available_cover_colors[0] ?? 'Maroon';
        $this->thesis_foil_color = $this->available_foil_colors[0] ?? 'Gold';
        $this->thesis_paper_size = $this->available_paper_sizes[0] ?? 'A4';

        foreach ($this->required_custom_fields as $field) {
            $this->thesis_custom_fields_data[$field] = '';
        }
    }

    public function selectDraftService(string $serviceKey): void
    {
        if (in_array($serviceKey, $this->active_service_keys, true)) {
            $this->draft_service_key = $serviceKey;
            $this->resetValidation();
        }
    }

    public function addItemToCart(): void
    {
        $this->resetValidation();
        $this->stock_error_message = null;
        $this->stock_fallback_suggestions = [];

        $shop = $this->selectedShop();
        $checkoutService = app(OrderCheckoutService::class);

        $stagedPath = null;
        $originalName = null;

        if ($this->draft_document_file instanceof UploadedFile) {
            $checkoutService->validateUploadFile($this->draft_document_file, false, 'draft_document_file');
            $stored = $this->draft_document_file->store('orders/cart-staging', 'local');
            if (is_string($stored)) {
                $stagedPath = $stored;
                $originalName = $this->draft_document_file->getClientOriginalName();
            }
        }

        if ($this->draft_service_key === 'thesis_binding') {
            $this->validate([
                'thesis_copies_count' => ['required', 'integer', 'min:1', 'max:50'],
                'thesis_bw_pages' => ['required', 'integer', 'min:0', 'max:2000'],
                'thesis_color_pages' => ['required', 'integer', 'min:0', 'max:2000'],
                'thesis_preprinted_total_pages' => ['required', 'integer', 'min:1', 'max:2000'],
            ]);

            $isCoverOnly = $this->thesis_fulfillment_type === OrderItem::FULFILLMENT_COVER_ONLY;
            if (! $isCoverOnly && $this->require_thesis_pdf && $stagedPath === null) {
                throw ValidationException::withMessages([
                    'draft_document_file' => 'Please attach your manuscript PDF for thesis binding.',
                ]);
            }

            if ($this->thesis_binding_type === 'hardbound' || $isCoverOnly) {
                foreach ($this->required_custom_fields as $field) {
                    if (trim((string) ($this->thesis_custom_fields_data[$field] ?? '')) === '') {
                        throw ValidationException::withMessages([
                            "thesis_custom_fields_data.{$field}" => "Please enter {$field} for cover stamping.",
                        ]);
                    }
                }
            }

            $payload = [
                'service_key' => 'thesis_binding',
                'fulfillment_type' => $this->thesis_fulfillment_type,
                'binding_type' => $isCoverOnly ? 'hardbound' : $this->thesis_binding_type,
                'bw_pages' => $this->thesis_bw_pages,
                'color_pages' => $this->thesis_color_pages,
                'preprinted_total_pages' => $this->thesis_preprinted_total_pages,
                'paper_size' => $this->thesis_paper_size,
                'print_sides' => $this->thesis_print_sides,
                'cover_color' => $this->thesis_cover_color,
                'foil_color' => $this->thesis_foil_color,
                'copies_count' => $this->thesis_copies_count,
                'selected_addon_ids' => $this->thesis_selected_addon_ids,
                'custom_fields_data' => $this->thesis_custom_fields_data,
                'existing_file_path' => $stagedPath,
                'document_original_name' => $originalName,
            ];

            $checkoutService->validateBindingLimits($shop, $payload);
            $pricing = $checkoutService->calculateThesisItemPricing($shop, $payload);

            $payload['summary_title'] = 'Thesis Binding — '.ucfirst((string) $payload['binding_type']).($isCoverOnly ? ' (Cover Only)' : ' (Full Package)');
            $payload['summary_specs'] = "{$pricing['bw_pages_count']} B&W / {$pricing['color_pages_count']} Color ({$pricing['physical_sheets_per_copy']} sheets/copy, {$this->thesis_print_sides}) · {$this->thesis_paper_size} · {$this->thesis_cover_color}";
            $payload['preview_total'] = $pricing['total_price'];
            $payload['preview_rush_fee'] = $pricing['rush_fee'];
        } else {
            $this->validate([
                'doc_total_pages' => ['required', 'integer', 'min:1', 'max:5000'],
                'doc_copies_count' => ['required', 'integer', 'min:1', 'max:500'],
                'doc_paper_size' => ['required', 'in:Letter,A4,Legal'],
                'doc_paper_stock' => ['required', 'in:70gsm,80gsm,100gsm'],
                'doc_print_sides' => ['required', 'in:simplex,duplex'],
                'doc_finishing_type' => ['required', 'in:loose,staple,folder,ring_bind,booklet'],
            ]);

            $payload = [
                'service_key' => 'document_printing',
                'total_pages' => $this->doc_total_pages,
                'color_mode' => $this->doc_color_mode,
                'custom_color_pages' => $this->doc_custom_color_pages,
                'paper_size' => $this->doc_paper_size,
                'paper_stock' => $this->doc_paper_stock,
                'print_sides' => $this->doc_print_sides,
                'finishing_type' => $this->doc_finishing_type,
                'ring_back_cover_color' => $this->doc_ring_back_cover_color,
                'copies_count' => $this->doc_copies_count,
                'existing_file_path' => $stagedPath,
                'document_original_name' => $originalName,
            ];

            $pricing = $checkoutService->calculateDocumentItemPricing($shop, $payload);

            $payload['summary_title'] = 'Document Printing — '.ucwords(str_replace('_', ' ', $this->doc_finishing_type));
            $payload['summary_specs'] = "{$pricing['bw_pages_count']} B&W / {$pricing['color_pages_count']} Color · {$this->doc_paper_size} {$this->doc_paper_stock} ({$this->doc_print_sides}) · {$pricing['total_physical_sheets']} sheets total";
            $payload['preview_total'] = $pricing['total_price'];
            $payload['preview_rush_fee'] = $pricing['rush_fee'];
        }

        $this->cart_items[] = $payload;
        $this->draft_document_file = null;
        $this->thesis_selected_addon_ids = [];
    }

    public function removeCartItem(int $index): void
    {
        if (! isset($this->cart_items[$index])) {
            return;
        }

        $path = $this->cart_items[$index]['existing_file_path'] ?? null;
        if (is_string($path) && str_starts_with($path, 'orders/cart-staging/')) {
            Storage::disk('local')->delete($path);
        }

        unset($this->cart_items[$index]);
        $this->cart_items = array_values($this->cart_items);
        $this->stock_error_message = null;
        $this->stock_fallback_suggestions = [];
    }

    public function placeOrder(): void
    {
        $this->resetValidation();
        $this->stock_error_message = null;
        $this->stock_fallback_suggestions = [];

        $user = auth()->user();
        $shop = $this->selectedShop();
        if (! $user) {
            return;
        }

        try {
            $order = app(OrderCheckoutService::class)->placeOrder(
                shop: $shop,
                customer: $user,
                cartItems: $this->cart_items,
                isRush: $this->is_rush,
                paymentOption: $this->payment_option,
                notes: trim($this->order_notes) !== '' ? trim($this->order_notes) : null,
            );
        } catch (InsufficientStockException $e) {
            $this->stock_error_message = $e->getMessage();
            $this->stock_fallback_suggestions = $e->fallbackSuggestions;

            return;
        }

        $this->created_order_id = $order->id;
        $this->payment_amount = $this->payment_option === Order::PAYMENT_OPTION_DOWNPAYMENT_50
            ? $order->minimumUpfrontAmount()
            : round((float) $order->total_amount, 2);
        $this->step = 2;
    }

    public function selectPaymentOption(string $option): void
    {
        if (! in_array($option, [Order::PAYMENT_OPTION_DOWNPAYMENT_50, Order::PAYMENT_OPTION_FULL_100], true)) {
            return;
        }

        $this->payment_option = $option;

        if ($this->created_order_id) {
            $order = Order::where('customer_id', auth()->id())->find($this->created_order_id);
            if ($order) {
                $this->payment_amount = $option === Order::PAYMENT_OPTION_DOWNPAYMENT_50
                    ? $order->minimumUpfrontAmount()
                    : round((float) $order->total_amount, 2);
            }
        }
    }

    public function submitPaymentProof(): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if (! $user || ! $this->created_order_id) {
            return;
        }

        $order = Order::where('customer_id', $user->id)->findOrFail($this->created_order_id);
        $uploadedFile = $this->payment_proof_file instanceof UploadedFile ? $this->payment_proof_file : null;

        app(PaymentService::class)->submitProof(
            order: $order,
            customer: $user,
            paymentOption: $this->payment_option,
            paymentMethod: $this->payment_method,
            amount: (float) $this->payment_amount,
            referenceNo: $this->payment_reference_no,
            proofFile: $uploadedFile,
        );

        $this->payment_proof_file = null;
        $this->step = 3;
    }
}; ?>

<div class="min-h-screen w-full pq-page relative pb-24">
    @php
        $user = auth()->user();
        $shop = $this->selectedShop();
        $checkoutService = app(OrderCheckoutService::class);
        $thesisAddons = $shop->inventoryItems()->readyToSell()->forService('thesis_binding')->get();

        $draftPreviewPrice = 0.0;
        if ($draft_service_key === 'thesis_binding') {
            $draftPreviewPrice = $checkoutService->calculateThesisItemPricing($shop, [
                'fulfillment_type' => $thesis_fulfillment_type,
                'binding_type' => $thesis_binding_type,
                'bw_pages' => $thesis_bw_pages,
                'color_pages' => $thesis_color_pages,
                'preprinted_total_pages' => $thesis_preprinted_total_pages,
                'print_sides' => $thesis_print_sides,
                'copies_count' => $thesis_copies_count,
                'selected_addon_ids' => $thesis_selected_addon_ids,
            ])['total_price'];
        } else {
            $draftPreviewPrice = $checkoutService->calculateDocumentItemPricing($shop, [
                'total_pages' => $doc_total_pages,
                'color_mode' => $doc_color_mode,
                'custom_color_pages' => $doc_custom_color_pages,
                'paper_size' => $doc_paper_size,
                'paper_stock' => $doc_paper_stock,
                'print_sides' => $doc_print_sides,
                'finishing_type' => $doc_finishing_type,
                'copies_count' => $doc_copies_count,
            ])['total_price'];
        }

        $cartSubtotal = collect($cart_items)->sum(fn (array $item): float => (float) ($item['preview_total'] ?? 0));
        $estimatedRushFee = $is_rush ? (float) (collect($cart_items)->max('preview_rush_fee') ?? 0) : 0.0;
        $cartGrandTotal = round($cartSubtotal + $estimatedRushFee, 2);

        $createdOrder = $created_order_id
            ? Order::where('customer_id', auth()->id())->with(['items', 'payments', 'printShop'])->find($created_order_id)
            : null;
    @endphp

    <header class="w-full pq-topbar sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <x-app-back-link :href="route('shops.show', $shop->id)" :label="$shop->name" :navigate="true" />
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight truncate">{{ $shop->name }} — Unified Cart &amp; Checkout</h1>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 truncate">Combine thesis binding and document printing into one tracked order with atomic stock reservation.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-bold shrink-0">
                <span class="px-2.5 py-1 rounded-full tabular-nums {{ $step === 1 ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">1. Build Cart</span>
                <span class="px-2.5 py-1 rounded-full tabular-nums {{ $step === 2 ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">2. Pay &amp; Verify</span>
                <span class="px-2.5 py-1 rounded-full tabular-nums {{ $step === 3 ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800' }}">3. Receipt</span>
                <x-theme-toggle />
            </div>
        </div>
    </header>

    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 pt-8 relative z-20">
        @if ($step === 1)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Service Configurator -->
                <div class="lg:col-span-7 space-y-6">
                    @if ($stock_error_message)
                        <div class="p-4 rounded-2xl border border-red-300 dark:border-red-500/40 bg-red-50 dark:bg-red-500/10 text-red-900 dark:text-red-200 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-sm text-red-800 dark:text-red-300">
                                <flux:icon name="exclamation-triangle" class="size-4 shrink-0" />
                                <span>Insufficient Material Stock</span>
                            </div>
                            <p class="text-xs">{{ $stock_error_message }}</p>
                            @if ($stock_fallback_suggestions !== [])
                                <div class="text-xs text-amber-800 dark:text-amber-200 pt-1">
                                    <span class="font-bold">Available Alternatives:</span>
                                    <ul class="list-disc pl-5 mt-1 space-y-0.5">
                                        @foreach ($stock_fallback_suggestions as $suggestionIdx => $suggestion)
                                            <li wire:key="fallback-{{ $suggestion['id'] ?? $suggestionIdx }}">
                                                {{ $suggestion['name'] ?? 'Alternative Material' }}
                                                ({{ number_format((float) ($suggestion['available_qty'] ?? 0), 0) }} {{ $suggestion['unit'] ?? 'units' }} available &mdash; {{ $suggestion['category'] ?? 'Material' }})
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    @error('cart')
                        <div class="p-3 rounded-xl pq-badge-danger text-xs font-semibold">{{ $message }}</div>
                    @enderror

                    <div class="pq-card p-6 space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Add Print Job to Cart</h2>
                                <p class="text-xs text-slate-600 dark:text-slate-400 tabular-nums">Max upload {{ $max_upload_mb }} MB &bull; Max binding thickness {{ $max_binding_sheets }} physical sheets</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if (in_array('thesis_binding', $active_service_keys, true))
                                    <button
                                        type="button"
                                        wire:click="selectDraftService('thesis_binding')"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer {{ $draft_service_key === 'thesis_binding' ? 'bg-indigo-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                                    >
                                        Thesis Binding
                                    </button>
                                @endif
                                @if (in_array('document_printing', $active_service_keys, true))
                                    <button
                                        type="button"
                                        wire:click="selectDraftService('document_printing')"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer {{ $draft_service_key === 'document_printing' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-800 hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                                    >
                                        Document Printing
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($draft_service_key === 'thesis_binding')
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fulfillment Mode</label>
                                        <select wire:model.live="thesis_fulfillment_type" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="{{ OrderItem::FULFILLMENT_FULL_PACKAGE }}">Full Print + Binding</option>
                                            @if ($allow_customer_supplied_paper)
                                                <option value="{{ OrderItem::FULFILLMENT_COVER_ONLY }}">Cover &amp; Binding Only (Supply Paper)</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Binding Style</label>
                                        <select wire:model.live="thesis_binding_type" @disabled($thesis_fulfillment_type === OrderItem::FULFILLMENT_COVER_ONLY) class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="hardbound">Hardbound</option>
                                            <option value="softbound">Softbound</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    @if ($thesis_fulfillment_type === OrderItem::FULFILLMENT_COVER_ONLY)
                                        <div class="col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Supplied Pages Count</label>
                                            <input type="number" min="1" max="2000" wire:model.live="thesis_preprinted_total_pages" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                            @error('preprinted_total_pages') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @else
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">B&amp;W Pages</label>
                                            <input type="number" min="0" max="2000" wire:model.live="thesis_bw_pages" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Color Pages</label>
                                            <input type="number" min="0" max="2000" wire:model.live="thesis_color_pages" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Print Sides</label>
                                        <select wire:model.live="thesis_print_sides" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="simplex">Simplex (1-Sided)</option>
                                            <option value="duplex">Duplex (Back-to-Back)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Copies</label>
                                        <input type="number" min="1" max="50" wire:model.live="thesis_copies_count" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                    </div>
                                </div>
                                @error('binding_limit') <p class="text-xs text-red-600 dark:text-red-400 font-semibold">{{ $message }}</p> @enderror
                                @error('total_pages') <p class="text-xs text-red-600 dark:text-red-400 font-semibold">{{ $message }}</p> @enderror

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Paper Size</label>
                                        <select wire:model.live="thesis_paper_size" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            @foreach ($available_paper_sizes as $size)
                                                <option wire:key="thesis-size-{{ $size }}" value="{{ $size }}">{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cover Color</label>
                                        <select wire:model.live="thesis_cover_color" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            @foreach ($available_cover_colors as $color)
                                                <option wire:key="thesis-color-{{ $color }}" value="{{ $color }}">{{ $color }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Foil Stamping</label>
                                        <select wire:model.live="thesis_foil_color" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            @foreach ($available_foil_colors as $foil)
                                                <option wire:key="thesis-foil-{{ $foil }}" value="{{ $foil }}">{{ $foil }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @if ($thesis_binding_type === 'hardbound' && $required_custom_fields !== [])
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                                        @foreach ($required_custom_fields as $field)
                                            <div wire:key="cover-field-{{ \Illuminate\Support\Str::slug($field) }}">
                                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ $field }}</label>
                                                <input type="text" wire:model="thesis_custom_fields_data.{{ $field }}" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-white" />
                                                @error("thesis_custom_fields_data.{$field}") <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($thesisAddons->isNotEmpty())
                                    <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Optional Add-Ons</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach ($thesisAddons as $addon)
                                                <label wire:key="addon-opt-{{ $addon->id }}" class="pq-well flex items-center justify-between gap-2 p-2.5 text-xs cursor-pointer">
                                                    <span class="flex items-center gap-2">
                                                        <input type="checkbox" wire:model.live="thesis_selected_addon_ids" value="{{ $addon->id }}" class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 accent-blue-600 text-blue-600" />
                                                        <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $addon->name }}</span>
                                                    </span>
                                                    <span class="font-bold text-blue-700 dark:text-blue-400 tabular-nums">₱{{ number_format((float) $addon->selling_price, 2) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Total Pages</label>
                                        <input type="number" min="1" max="5000" wire:model.live="doc_total_pages" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Color Mode</label>
                                        <select wire:model.live="doc_color_mode" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="bw">All Black &amp; White</option>
                                            <option value="color">All Full Color</option>
                                            <option value="mixed">Selective Color Pages</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Print Sides</label>
                                        <select wire:model.live="doc_print_sides" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="simplex">Simplex (1-Sided)</option>
                                            <option value="duplex">Duplex (2-Sided)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Copies</label>
                                        <input type="number" min="1" max="500" wire:model.live="doc_copies_count" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                                    </div>
                                </div>

                                @if ($doc_color_mode === 'mixed')
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Color Page Numbers (e.g. 1, 3, 5-8)</label>
                                        <input type="text" wire:model.live="doc_custom_color_pages" placeholder="e.g. 1, 4-6" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400" />
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Paper Size</label>
                                        <select wire:model.live="doc_paper_size" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="A4">A4</option>
                                            <option value="Letter">Letter (Short)</option>
                                            <option value="Legal">Legal (Long)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Paper Stock</label>
                                        <select wire:model.live="doc_paper_stock" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="70gsm">70gsm Standard</option>
                                            <option value="80gsm">80gsm Thick</option>
                                            <option value="100gsm">100gsm Specialty</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Finishing</label>
                                        <select wire:model.live="doc_finishing_type" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                            <option value="loose">Loose Sheets</option>
                                            <option value="staple">Corner Staple</option>
                                            <option value="folder">Sliding Folder &amp; Fastener</option>
                                            <option value="ring_bind">Plastic Ring Binding</option>
                                            <option value="booklet">Booklet Fold &amp; Staple</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Shared File Upload -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Attach Document / Manuscript PDF
                                <span class="text-slate-500 dark:text-slate-400 font-normal">(PDF/DOCX, up to {{ $max_upload_mb }} MB)</span>
                            </label>
                            <input type="file" wire:model="draft_document_file" class="block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-800 dark:file:text-slate-200 file:font-bold cursor-pointer" />
                            @error('draft_document_file') <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <div>
                                <span class="text-[11px] text-slate-600 dark:text-slate-400 block">Estimated Item Subtotal</span>
                                <span class="text-lg font-black text-blue-700 dark:text-blue-400 tabular-nums">₱{{ number_format($draftPreviewPrice, 2) }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="addItemToCart"
                                wire:loading.attr="disabled"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm cursor-pointer"
                            >
                                + Add Item to Cart
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Cart Summary & Place Order -->
                <div class="lg:col-span-5 space-y-5">
                    <div class="pq-card p-6 space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider tabular-nums">Your Cart ({{ count($cart_items) }})</h2>
                            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $shop->name }}</span>
                        </div>

                        @if ($cart_items === [])
                            <div class="p-6 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400">
                                Configure a thesis binding or document printing job on the left, then click <strong class="text-slate-800 dark:text-slate-300">+ Add Item to Cart</strong>.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($cart_items as $index => $cartItem)
                                    <div wire:key="cart-item-{{ $index }}" class="pq-well p-3.5 space-y-1.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $cartItem['summary_title'] ?? 'Print Item' }}</div>
                                                <div class="text-[11px] text-slate-600 dark:text-slate-400">{{ $cartItem['summary_specs'] ?? '' }}</div>
                                                @if (! empty($cartItem['document_original_name']))
                                                    <div class="text-[10px] text-blue-700 dark:text-blue-400 mt-0.5">File: {{ $cartItem['document_original_name'] }}</div>
                                                @endif
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">₱{{ number_format((float) ($cartItem['preview_total'] ?? 0), 2) }}</div>
                                                <button type="button" wire:click="removeCartItem({{ $index }})" class="text-[10px] font-bold text-red-700 dark:text-red-400 hover:underline mt-1 cursor-pointer">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-800 text-xs">
                            <label class="pq-well flex items-center justify-between gap-3 p-3 cursor-pointer">
                                <span>
                                    <span class="font-bold text-slate-900 dark:text-white block">Rush Turnaround Priority</span>
                                    <span class="text-[11px] text-slate-600 dark:text-slate-400">Prioritized queue handling when available</span>
                                </span>
                                <input type="checkbox" wire:model.live="is_rush" class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-amber-500" />
                            </label>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Preferred Payment Plan</label>
                                <select wire:model.live="payment_option" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                                    <option value="{{ Order::PAYMENT_OPTION_FULL_100 }}">100% Full Upfront Payment</option>
                                    <option value="{{ Order::PAYMENT_OPTION_DOWNPAYMENT_50 }}">50% Downpayment + Balance on Pickup</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Special Instructions (Optional)</label>
                                <textarea wire:model="order_notes" rows="2" placeholder="Optional instructions for the print shop..." class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                            </div>
                        </div>

                        <div class="pq-well p-4 space-y-1.5 text-xs tabular-nums">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Items Subtotal</span>
                                <span>₱{{ number_format($cartSubtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Rush Processing Fee</span>
                                <span>₱{{ number_format($estimatedRushFee, 2) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-800 text-sm font-bold text-slate-900 dark:text-white">
                                <span>Estimated Grand Total</span>
                                <span class="text-blue-700 dark:text-blue-400">₱{{ number_format($cartGrandTotal, 2) }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="placeOrder"
                            wire:loading.attr="disabled"
                            @disabled($cart_items === [])
                            class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold uppercase tracking-wider shadow-sm cursor-pointer"
                        >
                            Place Order &amp; Reserve Stock &rarr;
                        </button>
                    </div>
                </div>
            </div>
        @elseif ($step === 2 && $createdOrder)
            <div class="pq-card max-w-2xl mx-auto p-6 sm:p-8 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase pq-badge-brand">Stock Reserved — Step 2 of 3</span>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2">Submit Payment Proof</h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Order <strong class="font-mono text-blue-700 dark:text-blue-400">{{ $createdOrder->order_number }}</strong>
                            &bull; Tracking Code <strong class="font-mono text-slate-900 dark:text-white">{{ $createdOrder->tracking_code }}</strong>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] text-slate-600 dark:text-slate-400 block">Order Total</span>
                        <span class="text-xl font-black text-emerald-700 dark:text-emerald-400 tabular-nums">₱{{ number_format((float) $createdOrder->total_amount, 2) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        type="button"
                        wire:click="selectPaymentOption('{{ Order::PAYMENT_OPTION_DOWNPAYMENT_50 }}')"
                        class="p-4 rounded-2xl border text-left transition-colors cursor-pointer {{ $payment_option === Order::PAYMENT_OPTION_DOWNPAYMENT_50 ? 'border-blue-600 bg-blue-50 dark:bg-blue-500/10' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950' }}"
                    >
                        <div class="text-xs font-bold text-slate-900 dark:text-white">50% Downpayment</div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 tabular-nums">Minimum required upfront: ₱{{ number_format($createdOrder->minimumUpfrontAmount(), 2) }}</div>
                    </button>
                    <button
                        type="button"
                        wire:click="selectPaymentOption('{{ Order::PAYMENT_OPTION_FULL_100 }}')"
                        class="p-4 rounded-2xl border text-left transition-colors cursor-pointer {{ $payment_option === Order::PAYMENT_OPTION_FULL_100 ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-500/10' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950' }}"
                    >
                        <div class="text-xs font-bold text-slate-900 dark:text-white">100% Full Payment</div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 tabular-nums">Settle full amount now: ₱{{ number_format((float) $createdOrder->total_amount, 2) }}</div>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Method</label>
                        <select wire:model="payment_method" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                            <option value="bank_transfer">Bank Transfer / InstaPay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Amount Paid (PHP)</label>
                        <input type="number" step="0.01" wire:model="payment_amount" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white tabular-nums" />
                        @error('amount') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Transaction Reference Number</label>
                    <input type="text" wire:model="payment_reference_no" placeholder="e.g. GCASH-90881234" class="w-full rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 font-mono" />
                    @error('reference_no') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Upload Payment Proof Screenshot / PDF (Optional)</label>
                    <input type="file" wire:model="payment_proof_file" class="block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-800 dark:file:text-slate-200 file:font-bold cursor-pointer" />
                    @error('proof_file') <p class="text-[11px] text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <a href="{{ route('customer.dashboard') }}" wire:navigate class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                        Submit Later in Dashboard
                    </a>
                    <button
                        type="button"
                        wire:click="submitPaymentProof"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm cursor-pointer"
                    >
                        Submit Payment Proof
                    </button>
                </div>
            </div>
        @elseif ($step === 3 && $createdOrder)
            <div class="pq-card !border-emerald-300 dark:!border-emerald-500/40 max-w-xl mx-auto p-8 text-center space-y-5">
                <div class="size-14 rounded-full pq-badge-success flex items-center justify-center mx-auto">
                    <flux:icon name="check-badge" class="size-8" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Order &amp; Payment Proof Submitted!</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                        Your proof is awaiting shop verification before entering production.
                    </p>
                </div>

                <div class="pq-well p-4 space-y-1.5 text-xs text-left tabular-nums">
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Order Number</span>
                        <strong class="font-mono text-blue-700 dark:text-blue-400">{{ $createdOrder->order_number }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Tracking Code</span>
                        <strong class="font-mono text-slate-900 dark:text-white">{{ $createdOrder->tracking_code }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Order Total</span>
                        <strong class="text-slate-900 dark:text-white">₱{{ number_format((float) $createdOrder->total_amount, 2) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Balance Due After Approval</span>
                        <strong class="text-amber-700 dark:text-amber-300">₱{{ number_format(max(0, (float) $createdOrder->total_amount - (float) $createdOrder->amount_paid), 2) }}</strong>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <a href="{{ route('orders.receipt', $createdOrder) }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-800 dark:text-white">
                        Official Receipt
                    </a>
                    <a href="{{ route('orders.receipt', ['order' => $createdOrder, 'format' => 'pdf']) }}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white">
                        Download PDF Receipt
                    </a>
                    <a href="{{ route('customer.dashboard') }}" wire:navigate class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white">
                        Go to My Orders
                    </a>
                </div>
            </div>
        @endif
    </main>
</div>
