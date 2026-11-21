<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\DocumentPrintingConfig;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderCheckoutService
{
    public function __construct(
        private InventoryDeductionService $inventoryDeductionService,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Compute authoritative server-side pricing for a Document Printing line item.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     bw_pages_count: int,
     *     color_pages_count: int,
     *     total_pages: int,
     *     physical_sheets_per_copy: int,
     *     total_physical_sheets: int,
     *     per_copy_total: float,
     *     total_price: float,
     *     rush_fee: float
     * }
     */
    public function calculateDocumentItemPricing(PrintShop $shop, array $input): array
    {
        $config = DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();

        $totalPages = max(1, (int) ($input['total_pages'] ?? 1));
        $copiesCount = max(1, (int) ($input['copies_count'] ?? 1));
        $colorMode = (string) ($input['color_mode'] ?? 'bw');
        $paperSize = (string) ($input['paper_size'] ?? 'A4');
        $paperStock = (string) ($input['paper_stock'] ?? '70gsm');
        $printSides = (string) ($input['print_sides'] ?? 'simplex');
        $finishingType = (string) ($input['finishing_type'] ?? 'loose');

        [$bwPages, $colorPages] = $this->resolveDocumentColorBreakdown(
            $totalPages,
            $colorMode,
            (string) ($input['custom_color_pages'] ?? ''),
            $input['color_pages_count'] ?? null,
        );

        $bwRate = $config ? $config->getBwRate($paperSize) : match (strtolower($paperSize)) {
            'long', 'legal', 'folio' => 2.00,
            default => 1.50,
        };
        $colorRate = $config ? $config->getColorRate($paperSize) : match (strtolower($paperSize)) {
            'long', 'legal', 'folio' => 6.00,
            default => 5.00,
        };

        $pagesGross = ($bwPages * $bwRate) + ($colorPages * $colorRate);
        $duplexDiscountPercent = $config ? $config->duplex_discount_percent : 10;
        $duplexDiscount = $printSides === 'duplex' ? ($pagesGross * ($duplexDiscountPercent / 100)) : 0.0;

        $physicalSheetsPerCopy = $printSides === 'duplex'
            ? (int) ceil($totalPages / 2)
            : $totalPages;

        $stockUnitFee = $config ? $config->getPaperStockFee($paperStock) : match (strtolower($paperStock)) {
            '80gsm', '80' => 0.50,
            '100gsm', '100', 'specialty' => 1.50,
            default => 0.0,
        };
        $paperStockFee = $physicalSheetsPerCopy * $stockUnitFee;

        $finishingFee = match ($finishingType) {
            'staple' => $config ? $config->staple_price : 2.00,
            'folder' => $config ? $config->folder_fastener_price : 15.00,
            'ring_bind' => $config ? $config->ring_bind_base_price : 45.00,
            'booklet' => $config ? $config->booklet_staple_price : 20.00,
            default => 0.0,
        };

        $perCopyTotal = round(($pagesGross - $duplexDiscount) + $paperStockFee + $finishingFee, 2);
        $totalPrice = round($perCopyTotal * $copiesCount, 2);

        return [
            'bw_pages_count' => $bwPages,
            'color_pages_count' => $colorPages,
            'total_pages' => $totalPages,
            'physical_sheets_per_copy' => $physicalSheetsPerCopy,
            'total_physical_sheets' => $physicalSheetsPerCopy * $copiesCount,
            'per_copy_total' => $perCopyTotal,
            'total_price' => $totalPrice,
            'rush_fee' => $config ? (float) $config->rush_fee_amount : 50.00,
        ];
    }

    /**
     * Compute authoritative server-side pricing for a Thesis Binding line item.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     bw_pages_count: int,
     *     color_pages_count: int,
     *     total_pages: int,
     *     physical_sheets_per_copy: int,
     *     unit_price: float,
     *     addons_total: float,
     *     selected_addons_details: array<int, array{id: int, name: string, price: float, qty: int}>,
     *     total_price: float,
     *     estimated_spine_mm: float,
     *     rush_fee: float
     * }
     */
    public function calculateThesisItemPricing(PrintShop $shop, array $input): array
    {
        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();

        $fulfillmentType = (string) ($input['fulfillment_type'] ?? OrderItem::FULFILLMENT_FULL_PACKAGE);
        $isCoverOnly = $fulfillmentType === OrderItem::FULFILLMENT_COVER_ONLY;
        $bindingType = $isCoverOnly ? 'hardbound' : (string) ($input['binding_type'] ?? 'hardbound');
        $copiesCount = max(1, (int) ($input['copies_count'] ?? 1));
        $printSides = (string) ($input['print_sides'] ?? 'simplex');

        $bwPages = $isCoverOnly ? 0 : max(0, (int) ($input['bw_pages'] ?? 0));
        $colorPages = $isCoverOnly ? 0 : max(0, (int) ($input['color_pages'] ?? 0));
        $preprintedPages = max(1, (int) ($input['preprinted_total_pages'] ?? 100));
        $totalPages = $isCoverOnly ? $preprintedPages : max(1, $bwPages + $colorPages);

        $hardboundBase = $config ? (float) $config->hardbound_base_price : 350.00;
        $softboundBase = $config ? (float) $config->softbound_base_price : 150.00;
        $coverOnlyBase = $config ? (float) ($config->hardbound_cover_only_price ?? 300.00) : 300.00;
        $bwRate = $config ? (float) $config->page_price_bw : 1.50;
        $colorRate = $config ? (float) $config->page_price_color : 5.00;

        $basePrice = $isCoverOnly
            ? $coverOnlyBase
            : ($bindingType === 'hardbound' ? $hardboundBase : $softboundBase);

        $unitPrice = round($basePrice + ($bwPages * $bwRate) + ($colorPages * $colorRate), 2);
        $subtotal = round($unitPrice * $copiesCount, 2);

        $addonsTotal = 0.0;
        $selectedAddonsDetails = [];
        $rawAddonIds = $input['selected_addon_ids'] ?? [];
        if (is_array($rawAddonIds) && $rawAddonIds !== []) {
            $addonIds = array_values(array_filter(array_map('intval', $rawAddonIds)));
            if ($addonIds !== []) {
                $addons = $shop->inventoryItems()->whereIn('id', $addonIds)->get();
                foreach ($addons as $addon) {
                    $price = (float) ($addon->selling_price ?? 0.0);
                    $addonsTotal += $price * $copiesCount;
                    $selectedAddonsDetails[] = [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => $price,
                        'qty' => $copiesCount,
                    ];
                }
            }
        }

        $physicalSheetsPerCopy = $printSides === 'duplex'
            ? (int) ceil($totalPages / 2)
            : $totalPages;

        return [
            'bw_pages_count' => $bwPages,
            'color_pages_count' => $colorPages,
            'total_pages' => $totalPages,
            'physical_sheets_per_copy' => $physicalSheetsPerCopy,
            'unit_price' => $unitPrice,
            'addons_total' => round($addonsTotal, 2),
            'selected_addons_details' => $selectedAddonsDetails,
            'total_price' => round($subtotal + $addonsTotal, 2),
            'estimated_spine_mm' => round($physicalSheetsPerCopy * 0.1, 1),
            'rush_fee' => $config ? (float) $config->rush_fee : 150.00,
        ];
    }

    /**
     * Validate shop-configured maximum binding sheet constraints (accounting for duplex).
     *
     * @param  array<string, mixed>  $itemInput
     *
     * @throws ValidationException
     */
    public function validateBindingLimits(PrintShop $shop, array $itemInput): void
    {
        $serviceKey = (string) ($itemInput['service_key'] ?? 'thesis_binding');
        if ($serviceKey !== 'thesis_binding') {
            return;
        }

        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        $maxSheets = $config !== null ? (int) $config->max_binding_sheets : 250;
        if ($maxSheets <= 0) {
            return;
        }

        $isCoverOnly = ($itemInput['fulfillment_type'] ?? OrderItem::FULFILLMENT_FULL_PACKAGE) === OrderItem::FULFILLMENT_COVER_ONLY;
        $totalPages = $isCoverOnly
            ? max(1, (int) ($itemInput['preprinted_total_pages'] ?? ($itemInput['total_pages'] ?? 1)))
            : max(1, (int) ($itemInput['bw_pages'] ?? 0) + (int) ($itemInput['color_pages'] ?? 0));

        $printSides = (string) ($itemInput['print_sides'] ?? 'simplex');
        $sheetsPerCopy = $printSides === 'duplex'
            ? (int) ceil($totalPages / 2)
            : $totalPages;

        if ($sheetsPerCopy > $maxSheets) {
            throw ValidationException::withMessages([
                'binding_limit' => "Physical sheet count ({$sheetsPerCopy} sheets) exceeds this shop's maximum binding capacity of {$maxSheets} sheets.",
            ]);
        }
    }

    /**
     * Enforce global platform upload size and PDF MIME bounds.
     *
     * @throws ValidationException
     */
    public function validateUploadFile(?UploadedFile $file, bool $required = false, string $field = 'document_file'): void
    {
        $maxUploadMb = max(1, PlatformSetting::current()->max_upload_mb);
        $rules = [$required ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:'.($maxUploadMb * 1024)];

        Validator::make(
            [$field => $file],
            [$field => $rules],
            [
                "{$field}.required" => 'Please upload your print-ready PDF file.',
                "{$field}.mimes" => 'Uploaded document must be a valid PDF file (.pdf).',
                "{$field}.max" => "Uploaded document exceeds the platform limit of {$maxUploadMb} MB.",
            ],
        )->validate();
    }

    /**
     * Place a single- or multi-item order under one parent Order, storing pending_payment and reserving BOM stock atomically.
     *
     * @param  array<int, array<string, mixed>>  $cartItems
     *
     * @throws InsufficientStockException
     * @throws ValidationException
     */
    public function placeOrder(
        PrintShop $shop,
        User $customer,
        array $cartItems,
        bool $isRush = false,
        string $paymentOption = Order::PAYMENT_OPTION_FULL_100,
        ?string $notes = null,
    ): Order {
        abort_unless($shop->isApproved() && $shop->is_setup_completed, 422, 'Selected shop is not accepting orders.');

        if ($cartItems === []) {
            throw ValidationException::withMessages([
                'cart' => 'Please add at least one print item to your cart before placing an order.',
            ]);
        }

        if (! in_array($paymentOption, [Order::PAYMENT_OPTION_DOWNPAYMENT_50, Order::PAYMENT_OPTION_FULL_100, Order::PAYMENT_OPTION_COUNTER_OFFLINE], true)) {
            $paymentOption = Order::PAYMENT_OPTION_FULL_100;
        }

        $distinctServices = collect($cartItems)
            ->map(fn (array $item): string => (string) ($item['service_key'] ?? 'document_printing'))
            ->unique()
            ->values();

        foreach ($distinctServices as $serviceKey) {
            $offering = $shop->services()->where('service_key', $serviceKey)->first();
            abort_unless($offering !== null && $offering->acceptsOrders(), 422, "Service [{$serviceKey}] is currently unavailable or paused.");
        }

        $thesisConfig = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        $preparedItems = [];
        $subtotalAmount = 0.0;
        $applicableRushFee = 0.0;

        foreach ($cartItems as $index => $rawItem) {
            $serviceKey = (string) ($rawItem['service_key'] ?? 'document_printing');
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = ($rawItem['document_file'] ?? null) instanceof UploadedFile ? $rawItem['document_file'] : null;
            $existingPath = is_string($rawItem['existing_file_path'] ?? null) ? $rawItem['existing_file_path'] : null;

            if ($serviceKey === 'thesis_binding') {
                $this->validateBindingLimits($shop, $rawItem);
                $isCoverOnly = ($rawItem['fulfillment_type'] ?? OrderItem::FULFILLMENT_FULL_PACKAGE) === OrderItem::FULFILLMENT_COVER_ONLY;
                $requirePdf = ! $isCoverOnly && $thesisConfig !== null && (bool) $thesisConfig->require_pdf_upload && $existingPath === null;
                $this->validateUploadFile($uploadedFile, $requirePdf, "items.{$index}.document_file");

                $pricing = $this->calculateThesisItemPricing($shop, $rawItem);
                $fulfillmentType = (string) ($rawItem['fulfillment_type'] ?? OrderItem::FULFILLMENT_FULL_PACKAGE);
                $bindingType = $isCoverOnly ? 'hardbound' : (string) ($rawItem['binding_type'] ?? 'hardbound');
                $printSides = (string) ($rawItem['print_sides'] ?? 'simplex');

                $preparedItems[] = [
                    'service_key' => 'thesis_binding',
                    'binding_type' => $bindingType,
                    'fulfillment_type' => $fulfillmentType,
                    'is_paper_received' => false,
                    'estimated_spine_thickness_mm' => $pricing['estimated_spine_mm'],
                    'bw_pages_count' => $pricing['bw_pages_count'],
                    'color_pages_count' => $pricing['color_pages_count'],
                    'total_pages_count' => $pricing['total_pages'],
                    'cover_color' => $bindingType === 'hardbound' ? (string) ($rawItem['cover_color'] ?? 'Maroon') : 'Clear PVC / Standard Cardstock',
                    'foil_color' => $bindingType === 'hardbound' ? (string) ($rawItem['foil_color'] ?? 'Gold') : null,
                    'paper_size' => (string) ($rawItem['paper_size'] ?? 'A4'),
                    'copies_count' => max(1, (int) ($rawItem['copies_count'] ?? 1)),
                    'custom_fields_data' => array_merge(
                        is_array($rawItem['custom_fields_data'] ?? null) ? $rawItem['custom_fields_data'] : [],
                        ['service_key' => 'thesis_binding', 'print_sides' => $printSides],
                    ),
                    'selected_addons' => $pricing['selected_addons_details'],
                    'uploaded_file' => $uploadedFile,
                    'existing_file_path' => $existingPath,
                    'document_original_name' => $rawItem['document_original_name'] ?? null,
                    'unit_price' => $pricing['unit_price'],
                    'total_price' => $pricing['total_price'],
                ];

                $subtotalAmount += $pricing['total_price'];
                $applicableRushFee = max($applicableRushFee, $pricing['rush_fee']);
            } else {
                $this->validateUploadFile($uploadedFile, false, "items.{$index}.document_file");
                $pricing = $this->calculateDocumentItemPricing($shop, $rawItem);
                $finishingType = (string) ($rawItem['finishing_type'] ?? 'loose');
                $printSides = (string) ($rawItem['print_sides'] ?? 'simplex');

                $preparedItems[] = [
                    'service_key' => 'document_printing',
                    'binding_type' => $finishingType === 'ring_bind' ? 'ring_bind' : 'none',
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'is_paper_received' => true,
                    'estimated_spine_thickness_mm' => round($pricing['physical_sheets_per_copy'] * 0.1, 1),
                    'bw_pages_count' => $pricing['bw_pages_count'],
                    'color_pages_count' => $pricing['color_pages_count'],
                    'total_pages_count' => $pricing['total_pages'],
                    'cover_color' => (string) ($rawItem['ring_back_cover_color'] ?? 'Blue'),
                    'foil_color' => null,
                    'paper_size' => (string) ($rawItem['paper_size'] ?? 'A4'),
                    'copies_count' => max(1, (int) ($rawItem['copies_count'] ?? 1)),
                    'custom_fields_data' => [
                        'service_key' => 'document_printing',
                        'color_mode' => (string) ($rawItem['color_mode'] ?? 'bw'),
                        'custom_color_pages' => (string) ($rawItem['custom_color_pages'] ?? ''),
                        'paper_stock' => (string) ($rawItem['paper_stock'] ?? '70gsm'),
                        'print_sides' => $printSides,
                        'finishing_type' => $finishingType,
                        'ring_back_cover_color' => (string) ($rawItem['ring_back_cover_color'] ?? 'Blue'),
                        'physical_sheets_per_copy' => $pricing['physical_sheets_per_copy'],
                        'total_physical_sheets' => $pricing['total_physical_sheets'],
                    ],
                    'selected_addons' => null,
                    'uploaded_file' => $uploadedFile,
                    'existing_file_path' => $existingPath,
                    'document_original_name' => $rawItem['document_original_name'] ?? null,
                    'unit_price' => $pricing['per_copy_total'],
                    'total_price' => $pricing['total_price'],
                ];

                $subtotalAmount += $pricing['total_price'];
                $applicableRushFee = max($applicableRushFee, $pricing['rush_fee']);
            }
        }

        $rushTotal = $isRush ? round($applicableRushFee, 2) : 0.0;
        $subtotalAmount = round($subtotalAmount, 2);
        $grandTotal = round($subtotalAmount + $rushTotal, 2);

        $leadDays = $distinctServices->contains('thesis_binding')
            ? ($isRush
                ? ($thesisConfig !== null ? (int) $thesisConfig->rush_lead_time_days : 1)
                : ($thesisConfig !== null ? (int) $thesisConfig->standard_lead_time_days : 4))
            : ($isRush ? 1 : 2);

        $newlyStoredFiles = [];

        try {
            return DB::transaction(function () use (
                $shop,
                $customer,
                $distinctServices,
                $preparedItems,
                $paymentOption,
                $subtotalAmount,
                $rushTotal,
                $grandTotal,
                $isRush,
                $leadDays,
                $notes,
                &$newlyStoredFiles,
            ): Order {
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'tracking_code' => Order::generateTrackingCode(),
                    'print_shop_id' => $shop->id,
                    'customer_id' => $customer->id,
                    'service_key' => $distinctServices->count() === 1 ? $distinctServices->first() : null,
                    'order_status' => Order::STATUS_PENDING_PAYMENT,
                    'payment_status' => Order::PAYMENT_UNPAID,
                    'payment_option' => $paymentOption,
                    'subtotal_amount' => $subtotalAmount,
                    'rush_fee_amount' => $rushTotal,
                    'total_amount' => $grandTotal,
                    'amount_paid' => 0.0,
                    'balance_due' => $grandTotal,
                    'refund_due' => 0.0,
                    'refund_status' => Order::REFUND_NONE,
                    'is_rush' => $isRush,
                    'production_stage' => Order::STAGE_QUEUE,
                    'target_completion_date' => Carbon::now()->addDays(max(1, $leadDays)),
                    'staff_notes' => $notes,
                ]);

                foreach ($preparedItems as $itemData) {
                    $filePath = $itemData['existing_file_path'];
                    $originalName = $itemData['document_original_name'];

                    if ($itemData['uploaded_file'] instanceof UploadedFile) {
                        $folder = $itemData['service_key'] === 'thesis_binding' ? 'orders/manuscripts' : 'orders/documents';
                        $stored = $itemData['uploaded_file']->store($folder, 'local');
                        if (is_string($stored)) {
                            $filePath = $stored;
                            $newlyStoredFiles[] = $stored;
                        }
                        $originalName = $itemData['uploaded_file']->getClientOriginalName();
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'service_key' => $itemData['service_key'],
                        'binding_type' => $itemData['binding_type'],
                        'fulfillment_type' => $itemData['fulfillment_type'],
                        'is_paper_received' => $itemData['is_paper_received'],
                        'estimated_spine_thickness_mm' => $itemData['estimated_spine_thickness_mm'],
                        'bw_pages_count' => $itemData['bw_pages_count'],
                        'color_pages_count' => $itemData['color_pages_count'],
                        'total_pages_count' => $itemData['total_pages_count'],
                        'cover_color' => $itemData['cover_color'],
                        'foil_color' => $itemData['foil_color'],
                        'paper_size' => $itemData['paper_size'],
                        'copies_count' => $itemData['copies_count'],
                        'custom_fields_data' => $itemData['custom_fields_data'],
                        'selected_addons' => $itemData['selected_addons'],
                        'document_file_path' => $filePath,
                        'document_original_name' => $originalName,
                        'unit_price' => $itemData['unit_price'],
                        'total_price' => $itemData['total_price'],
                        'production_stage' => Order::STAGE_QUEUE,
                        'prepress_status' => OrderItem::PREPRESS_PENDING,
                        'is_spec_locked' => false,
                    ]);
                }

                $this->inventoryDeductionService->reserveForOrderItems($order, $customer);

                $this->auditLogger->log(
                    action: 'order.placed',
                    auditable: $order,
                    printShopId: $shop->id,
                    actor: $customer,
                    metadata: [
                        'order_number' => $order->order_number,
                        'tracking_code' => $order->tracking_code,
                        'item_count' => count($preparedItems),
                        'services' => $distinctServices->all(),
                    ],
                );

                return $order->load('items');
            });
        } catch (InsufficientStockException $exception) {
            foreach ($newlyStoredFiles as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /**
     * Resolve B&W and Color page counts for document printing.
     *
     * @return array{0: int, 1: int}
     */
    protected function resolveDocumentColorBreakdown(
        int $totalPages,
        string $colorMode,
        string $customColorPages,
        mixed $explicitColorCount = null,
    ): array {
        if ($colorMode === 'bw') {
            return [$totalPages, 0];
        }

        if ($colorMode === 'color') {
            return [0, $totalPages];
        }

        if ($customColorPages !== '') {
            $colorPages = [];
            foreach (explode(',', $customColorPages) as $part) {
                $trimmed = trim($part);
                if (str_contains($trimmed, '-')) {
                    $range = explode('-', $trimmed);
                    if (count($range) === 2) {
                        $start = (int) trim($range[0]);
                        $end = (int) trim($range[1]);
                        if ($start > 0 && $end >= $start) {
                            for ($i = $start; $i <= min($totalPages, $end); $i++) {
                                $colorPages[$i] = true;
                            }
                        }
                    }
                } elseif (is_numeric($trimmed)) {
                    $num = (int) $trimmed;
                    if ($num > 0 && $num <= $totalPages) {
                        $colorPages[$num] = true;
                    }
                }
            }

            $colorCount = count($colorPages);

            return [max(0, $totalPages - $colorCount), $colorCount];
        }

        if (is_numeric($explicitColorCount)) {
            $colorCount = min($totalPages, max(0, (int) $explicitColorCount));

            return [max(0, $totalPages - $colorCount), $colorCount];
        }

        return [$totalPages, 0];
    }
}
