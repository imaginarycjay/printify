<?php

namespace Database\Seeders;

use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Printer;
use App\Models\PrintShop;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use App\Services\InventoryDeductionService;
use App\Services\OrderCheckoutService;
use App\Services\PaymentService;
use App\Services\ProductionQueueService;
use App\Services\SpecRevisionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PressQueueDemoSeeder extends Seeder
{
    /**
     * Populate realistic multi-tenant demo data across all four PressQueue roles.
     */
    public function run(): void
    {
        $this->ensureSamplePdfsExist();

        $passwordHash = Hash::make('password');

        $admin = $this->upsertUser(
            email: 'admin@pressqueue.test',
            name: 'PressQueue Platform Admin',
            role: User::ROLE_ADMIN,
            passwordHash: $passwordHash,
        );

        PlatformSetting::current()->update([
            'default_retention_days' => 14,
            'min_retention_days' => 7,
            'max_retention_days' => 90,
            'max_upload_mb' => 25,
            'updated_by' => $admin->id,
        ]);

        // Customers
        $customer1 = $this->upsertUser(
            email: 'customer@pressqueue.test',
            name: 'Alyssa Marie Cruz',
            role: User::ROLE_CUSTOMER,
            passwordHash: $passwordHash,
        );
        $customer2 = $this->upsertUser(
            email: 'customer2@pressqueue.test',
            name: 'Miguel Angelo Santos',
            role: User::ROLE_CUSTOMER,
            passwordHash: $passwordHash,
        );

        // =========================================================================
        // TENANT 1: Kabacan Academic Press (Normal workload: 3 active < 4 busy)
        // =========================================================================
        $owner1 = $this->upsertUser(
            email: 'owner@pressqueue.test',
            name: 'Marco Villanueva',
            role: User::ROLE_BUSINESS_OWNER,
            passwordHash: $passwordHash,
        );

        $shop1 = PrintShop::updateOrCreate(
            ['user_id' => $owner1->id],
            [
                'name' => 'Kabacan Academic Press',
                'is_setup_completed' => true,
                'approval_status' => PrintShop::STATUS_APPROVED,
                'rejection_reason' => null,
                'address' => 'Rizal Avenue, Poblacion, Kabacan, Cotabato',
                'contact' => '0917-555-0101',
                'description' => 'Full-service academic bindery and high-speed campus document hub specializing in hardbound theses, foil stamping, and duplex handouts.',
                'file_retention_days' => 14,
                'busy_threshold' => 4,
                'at_capacity_threshold' => 8,
                'operating_hours' => 'Mon-Sat 7:30 AM - 7:00 PM',
            ],
        );

        $staff1 = $this->upsertUser(
            email: 'staff@pressqueue.test',
            name: 'Elena Bautista',
            role: User::ROLE_PRODUCTION_STAFF,
            passwordHash: $passwordHash,
            staffShopId: $shop1->id,
        );
        $staff2 = $this->upsertUser(
            email: 'staff2@pressqueue.test',
            name: 'Jomar Castillo',
            role: User::ROLE_PRODUCTION_STAFF,
            passwordHash: $passwordHash,
            staffShopId: $shop1->id,
        );

        $this->ensureShopServices($shop1, [
            'thesis_binding' => ['is_active' => true, 'capacity' => 20, 'pause_reason' => null, 'paused_until' => null],
            'document_printing' => ['is_active' => true, 'capacity' => 40, 'pause_reason' => null, 'paused_until' => null],
        ]);

        $printerXerox = Printer::updateOrCreate(
            ['print_shop_id' => $shop1->id, 'name' => 'Xerox AltaLink C8155 (Bay 1)'],
            ['supported_sizes' => ['A4', 'Letter (Short)', 'Legal (Long)'], 'status' => Printer::STATUS_ONLINE, 'is_wide_format' => false],
        );
        $printerEpson = Printer::updateOrCreate(
            ['print_shop_id' => $shop1->id, 'name' => 'Epson WorkForce Pro WF-C878R (Bay 2)'],
            ['supported_sizes' => ['A4', 'Letter (Short)'], 'status' => Printer::STATUS_ONLINE, 'is_wide_format' => false],
        );
        Printer::updateOrCreate(
            ['print_shop_id' => $shop1->id, 'name' => 'Canon imagePROGRAF PRO-2100 (Plotter)'],
            ['supported_sizes' => ['A2', 'A1', 'A0'], 'status' => Printer::STATUS_MAINTENANCE, 'is_wide_format' => true],
        );

        $a4Paper1 = InventoryItem::firstOrCreate(
            ['print_shop_id' => $shop1->id, 'sku' => 'KAP-PAP-A4'],
            [
                'name' => 'A4 Bond Paper 80gsm (Sub-20)',
                'category' => 'Paper & Board',
                'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                'service_tag' => 'all',
                'stock_qty' => 4500.0,
                'reserved_qty' => 0.0,
                'unit' => 'sheets',
                'reorder_level' => 500.0,
                'unit_cost' => 0.65,
                'supplier_name' => 'Mindanao Paper Supply Co.',
            ],
        );
        $legalPaperLow = InventoryItem::firstOrCreate(
            ['print_shop_id' => $shop1->id, 'sku' => 'KAP-PAP-LEG'],
            [
                'name' => 'Legal Bond Paper 80gsm (Low Stock)',
                'category' => 'Paper & Board',
                'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                'service_tag' => 'document_printing',
                'stock_qty' => 95.0,
                'reserved_qty' => 15.0,
                'unit' => 'sheets',
                'reorder_level' => 120.0,
                'unit_cost' => 0.80,
                'supplier_name' => 'Mindanao Paper Supply Co.',
            ],
        );
        $maroonCover1 = InventoryItem::firstOrCreate(
            ['print_shop_id' => $shop1->id, 'sku' => 'KAP-COV-MAR'],
            [
                'name' => 'Leatherette Hardcover Board - Maroon',
                'category' => 'Binding Supplies',
                'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
                'service_tag' => 'thesis_binding',
                'stock_qty' => 180.0,
                'reserved_qty' => 0.0,
                'unit' => 'boards',
                'reorder_level' => 25.0,
                'unit_cost' => 38.00,
                'supplier_name' => 'Metro Bindery Corp.',
            ],
        );
        $tonerInk1 = InventoryItem::firstOrCreate(
            ['print_shop_id' => $shop1->id, 'sku' => 'KAP-TON-CMYK'],
            [
                'name' => 'Xerox CMYK High-Yield Toner',
                'category' => 'Ink / Toner',
                'item_type' => InventoryItem::TYPE_CONSUMABLE,
                'service_tag' => 'all',
                'stock_qty' => 250.0,
                'reserved_qty' => 0.0,
                'unit' => 'ml',
                'reorder_level' => 30.0,
                'unit_cost' => 18.00,
                'supplier_name' => 'Davao Imaging Distributors',
            ],
        );

        $thesisConfig1 = ThesisBindingConfig::updateOrCreate(
            ['print_shop_id' => $shop1->id],
            [
                'is_active' => true,
                'hardbound_base_price' => 380.00,
                'softbound_base_price' => 160.00,
                'allow_customer_supplied_paper' => true,
                'hardbound_cover_only_price' => 320.00,
                'page_price_bw' => 2.00,
                'page_price_color' => 5.50,
                'rush_fee' => 150.00,
                'cover_colors' => ['Maroon', 'Navy Blue', 'Forest Green', 'Black'],
                'foil_colors' => ['Gold', 'Silver'],
                'paper_sizes' => ['A4', 'Letter (Short)', 'Legal (Long)'],
                'auto_deduct_inventory' => true,
                'daily_production_quota' => 20,
                'max_binding_sheets' => 250,
                'standard_lead_time_days' => 4,
                'rush_lead_time_days' => 1,
                'require_pdf_upload' => false,
                'custom_cover_fields' => ['Thesis Title', 'Author', 'Degree / Department'],
            ],
        );

        ThesisBindingBomItem::firstOrCreate(
            ['thesis_binding_config_id' => $thesisConfig1->id, 'inventory_item_id' => $a4Paper1->id],
            ['binding_type' => 'both', 'usage_qty' => 1.0, 'unit' => 'sheets'],
        );
        ThesisBindingBomItem::firstOrCreate(
            ['thesis_binding_config_id' => $thesisConfig1->id, 'inventory_item_id' => $maroonCover1->id],
            ['binding_type' => 'hardbound', 'usage_qty' => 1.0, 'unit' => 'boards'],
        );

        DocumentPrintingConfig::updateOrCreate(
            ['print_shop_id' => $shop1->id],
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
                'bom_a4_paper_item_id' => $a4Paper1->id,
                'bom_short_paper_item_id' => $a4Paper1->id,
            ],
        );

        // Tenant 1 demo orders & workflow lifecycle
        $this->seedTenant1Orders(
            $shop1,
            $owner1,
            $staff1,
            $staff2,
            $customer1,
            $printerXerox,
            $printerEpson,
            $a4Paper1,
            $tonerInk1,
            $legalPaperLow,
        );

        // =========================================================================
        // TENANT 2: USM Gate Copy & Bind Center (Busy workload: 3 active >= 2, < 6)
        // =========================================================================
        $owner2 = $this->upsertUser(
            email: 'owner2@pressqueue.test',
            name: 'Clarissa Mendoza',
            role: User::ROLE_BUSINESS_OWNER,
            passwordHash: $passwordHash,
        );
        $shop2 = PrintShop::updateOrCreate(
            ['user_id' => $owner2->id],
            [
                'name' => 'USM Gate Copy & Bind Center',
                'is_setup_completed' => true,
                'approval_status' => PrintShop::STATUS_APPROVED,
                'address' => 'USM Main Gate Commercial Arcade, Kabacan, Cotabato',
                'contact' => '0918-440-2210',
                'description' => 'Fast turnaround campus print counter supporting mixed course packs and softbound manuscripts.',
                'file_retention_days' => 14,
                'busy_threshold' => 2,
                'at_capacity_threshold' => 6,
                'operating_hours' => 'Mon-Sat 7:00 AM - 8:00 PM',
            ],
        );
        $this->upsertUser('staff3@pressqueue.test', 'Ramon Dizon', User::ROLE_PRODUCTION_STAFF, $passwordHash, $shop2->id);
        $this->ensureShopServices($shop2, [
            'thesis_binding' => ['is_active' => true, 'capacity' => 18, 'pause_reason' => null, 'paused_until' => null],
            'document_printing' => ['is_active' => true, 'capacity' => 35, 'pause_reason' => null, 'paused_until' => null],
        ]);
        Printer::updateOrCreate(['print_shop_id' => $shop2->id, 'name' => 'Ricoh MP C3504'], ['supported_sizes' => ['A4', 'Letter (Short)', 'Legal (Long)'], 'status' => Printer::STATUS_ONLINE, 'is_wide_format' => false]);
        Printer::updateOrCreate(['print_shop_id' => $shop2->id, 'name' => 'Brother HL-L6400DW'], ['supported_sizes' => ['A4'], 'status' => Printer::STATUS_ONLINE, 'is_wide_format' => false]);
        $this->seedShopConfigsWithoutBom($shop2);
        $this->seedQueueWorkloadOrders($shop2, $owner2, $customer2, 'PQ-DEMO-200', 3);

        // =========================================================================
        // TENANT 3: Cotabato Campus Print Hub (At capacity: 3 active >= 3 + Paused service)
        // =========================================================================
        $owner3 = $this->upsertUser(
            email: 'owner3@pressqueue.test',
            name: 'Dante Ramos',
            role: User::ROLE_BUSINESS_OWNER,
            passwordHash: $passwordHash,
        );
        $shop3 = PrintShop::updateOrCreate(
            ['user_id' => $owner3->id],
            [
                'name' => 'Cotabato Campus Print Hub',
                'is_setup_completed' => true,
                'approval_status' => PrintShop::STATUS_APPROVED,
                'address' => 'Quezon Boulevard, Kidapawan-Kabacan Corridor',
                'contact' => '0920-771-8890',
                'description' => 'Bulk monochrome module printing and rush academic compilation desk.',
                'file_retention_days' => 14,
                'busy_threshold' => 2,
                'at_capacity_threshold' => 3,
                'operating_hours' => 'Mon-Fri 8:00 AM - 6:00 PM',
            ],
        );
        $this->ensureShopServices($shop3, [
            'document_printing' => ['is_active' => true, 'capacity' => 15, 'pause_reason' => null, 'paused_until' => null],
            'thesis_binding' => [
                'is_active' => true,
                'capacity' => 10,
                'pause_reason' => 'Foil Stamping Press Calibration & Maintenance',
                'paused_until' => now()->addDays(2),
            ],
        ]);
        Printer::updateOrCreate(['print_shop_id' => $shop3->id, 'name' => 'Konica Minolta bizhub C368'], ['supported_sizes' => ['A4', 'Legal (Long)'], 'status' => Printer::STATUS_ONLINE, 'is_wide_format' => false]);
        $this->seedShopConfigsWithoutBom($shop3);
        $this->seedQueueWorkloadOrders($shop3, $owner3, $customer2, 'PQ-DEMO-300', 3);

        // =========================================================================
        // TENANT 4: Mindanao Thesis Press (Pending Super Admin Approval)
        // =========================================================================
        $owner4 = $this->upsertUser(
            email: 'owner4@pressqueue.test',
            name: 'Liza Soriano',
            role: User::ROLE_BUSINESS_OWNER,
            passwordHash: $passwordHash,
        );
        $shop4 = PrintShop::updateOrCreate(
            ['user_id' => $owner4->id],
            [
                'name' => 'Mindanao Thesis Press',
                'is_setup_completed' => false,
                'approval_status' => PrintShop::STATUS_PENDING,
                'address' => 'Osmena Drive, Kabacan, Cotabato',
                'contact' => '0999-310-4455',
                'description' => 'Newly registered thesis bookbinding and color plotting shop awaiting Super Admin governance review.',
                'busy_threshold' => 5,
                'at_capacity_threshold' => 10,
                'operating_hours' => 'Mon-Sat 8:00 AM - 5:00 PM',
            ],
        );
        $this->ensureShopServices($shop4, [
            'thesis_binding' => ['is_active' => true, 'capacity' => 12, 'pause_reason' => null, 'paused_until' => null],
            'document_printing' => ['is_active' => true, 'capacity' => 25, 'pause_reason' => null, 'paused_until' => null],
        ]);

        // Process queued notifications and run aging check in non-testing live environments
        if (! app()->runningUnitTests() && config('queue.default') !== 'sync') {
            Artisan::call('queue:work', ['--stop-when-empty' => true]);
        }
        Artisan::call('pressqueue:check-aging-alerts');
        if (! app()->runningUnitTests() && config('queue.default') !== 'sync') {
            Artisan::call('queue:work', ['--stop-when-empty' => true]);
        }
    }

    private function seedTenant1Orders(
        PrintShop $shop1,
        User $owner1,
        User $staff1,
        User $staff2,
        User $customer1,
        Printer $printerXerox,
        Printer $printerEpson,
        InventoryItem $a4Paper1,
        InventoryItem $tonerInk1,
        InventoryItem $legalPaperLow,
    ): void {
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $queueService = app(ProductionQueueService::class);
        $revisionService = app(SpecRevisionService::class);
        $deductionService = app(InventoryDeductionService::class);

        $deductionService->notifyIfLowStock($legalPaperLow);

        // 1. PQ-DEMO-1001: Mixed Multi-Item Order -> Partially Ready (Item 1 ready aging at A-04, Item 2 printing on Xerox)
        if (! Order::where('order_number', 'PQ-DEMO-1001')->exists()) {
            $order1 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [
                    [
                        'service_key' => 'document_printing',
                        'total_pages' => 18,
                        'color_mode' => 'bw',
                        'paper_size' => 'A4',
                        'paper_stock' => '80gsm',
                        'print_sides' => 'duplex',
                        'finishing_type' => 'ring_bind',
                        'copies_count' => 2,
                        'existing_file_path' => 'orders/documents/demo-handout-pack.pdf',
                    ],
                    [
                        'service_key' => 'thesis_binding',
                        'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                        'binding_type' => 'hardbound',
                        'bw_pages' => 64,
                        'color_pages' => 8,
                        'paper_size' => 'A4',
                        'print_sides' => 'simplex',
                        'cover_color' => 'Maroon',
                        'foil_color' => 'Gold',
                        'copies_count' => 1,
                        'custom_fields_data' => ['Thesis Title' => 'Design of a Multi-Tenant Print Queue Platform', 'Author' => 'Alyssa Marie Cruz'],
                        'existing_file_path' => 'orders/manuscripts/demo-thesis-manuscript.pdf',
                    ],
                ],
                false,
                Order::PAYMENT_OPTION_DOWNPAYMENT_50,
            );
            $order1->forceFill(['order_number' => 'PQ-DEMO-1001', 'tracking_code' => 'TRK-DEMO1001'])->save();
            $dpHalf = round((float) $order1->total_amount * 0.5, 2);
            $proof1 = $paymentService->submitProof($order1, $customer1, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $dpHalf, 'GCASH-DEMO-1001');
            $paymentService->verifyPayment($proof1, $owner1);

            $items1 = $order1->items()->orderBy('id')->get();
            $docItem = $items1->get(0);
            $thesisItem = $items1->get(1);

            if ($docItem instanceof OrderItem) {
                $docItem->forceFill(['document_original_name' => 'demo-handout-pack.pdf'])->save();
                $queueService->assignItem($docItem, $staff1, $printerEpson->id, $staff2->id);
                $queueService->approvePrepress($docItem->fresh(), $staff1);
                $queueService->passQualityCheck($docItem->fresh(), $staff1, 'A-04');
                $docItem->fresh()->forceFill(['ready_at' => now()->subDays(4)])->save();
            }

            if ($thesisItem instanceof OrderItem) {
                $thesisItem->forceFill(['document_original_name' => 'demo-thesis-manuscript.pdf'])->save();
                $queueService->assignItem($thesisItem, $staff1, $printerXerox->id, $staff1->id);
                $queueService->approvePrepress($thesisItem->fresh(), $staff1);
                $queueService->startPrintingItem($thesisItem->fresh(), $staff1);
            }
        }

        // 2. PQ-DEMO-1002: 100% Prepaid + Downgrade Spec Revision -> Manual Refund Pending
        if (! Order::where('order_number', 'PQ-DEMO-1002')->exists()) {
            $order2 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [[
                    'service_key' => 'thesis_binding',
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'binding_type' => 'hardbound',
                    'bw_pages' => 90,
                    'color_pages' => 10,
                    'paper_size' => 'A4',
                    'print_sides' => 'simplex',
                    'cover_color' => 'Navy Blue',
                    'foil_color' => 'Gold',
                    'copies_count' => 1,
                    'custom_fields_data' => ['Thesis Title' => 'Campus Bindery Telemetry Study', 'Author' => 'Alyssa Marie Cruz'],
                    'existing_file_path' => 'orders/manuscripts/demo-thesis-manuscript.pdf',
                ]],
                false,
                Order::PAYMENT_OPTION_FULL_100,
            );
            $order2->forceFill(['order_number' => 'PQ-DEMO-1002', 'tracking_code' => 'TRK-DEMO1002'])->save();
            $proof2 = $paymentService->submitProof($order2, $customer1, Order::PAYMENT_OPTION_FULL_100, 'gcash', (float) $order2->total_amount, 'GCASH-DEMO-1002');
            $paymentService->verifyPayment($proof2, $owner1);

            $item2 = $order2->items()->firstOrFail();
            $item2->forceFill(['document_original_name' => 'capstone-chapter1-5.pdf'])->save();
            $revision = $revisionService->requestRevision($item2->fresh(), $customer1, [
                'bw_pages' => 60,
                'color_pages' => 4,
            ], 'Removed oversized raw appendix tables before printing.');
            $revisionService->approveAndApplyRevision($revision, $owner1);
        }

        // 3. PQ-DEMO-1003: Pending Payment with Aging (> 2 hours) Unverified Proof Flag
        if (! Order::where('order_number', 'PQ-DEMO-1003')->exists()) {
            $order3 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [[
                    'service_key' => 'thesis_binding',
                    'fulfillment_type' => OrderItem::FULFILLMENT_COVER_ONLY,
                    'binding_type' => 'hardbound',
                    'preprinted_total_pages' => 54,
                    'paper_size' => 'A4',
                    'print_sides' => 'simplex',
                    'cover_color' => 'Maroon',
                    'foil_color' => 'Silver',
                    'copies_count' => 1,
                    'custom_fields_data' => ['Thesis Title' => 'Cover-Only Preprinted Edition', 'Author' => 'Alyssa Marie Cruz'],
                ]],
                false,
                Order::PAYMENT_OPTION_DOWNPAYMENT_50,
            );
            $order3->forceFill(['order_number' => 'PQ-DEMO-1003', 'tracking_code' => 'TRK-DEMO1003'])->save();
            $half3 = round((float) $order3->total_amount * 0.5, 2);
            $proof3 = $paymentService->submitProof($order3, $customer1, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'maya', $half3, 'MAYA-DEMO-1003');
            $proof3->forceFill(['submitted_at' => now()->subHours(4)])->save();
        }

        // 4. PQ-DEMO-1004: QA Failure Dual Rework Spoilage (Paper + Ink BOM) currently in Quality Check
        if (! Order::where('order_number', 'PQ-DEMO-1004')->exists()) {
            $order4 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [[
                    'service_key' => 'document_printing',
                    'total_pages' => 42,
                    'color_mode' => 'full_color',
                    'paper_size' => 'A4',
                    'paper_stock' => '80gsm',
                    'print_sides' => 'duplex',
                    'finishing_type' => 'booklet',
                    'copies_count' => 2,
                    'existing_file_path' => 'orders/documents/demo-handout-pack.pdf',
                ]],
                false,
                Order::PAYMENT_OPTION_FULL_100,
            );
            $order4->forceFill(['order_number' => 'PQ-DEMO-1004', 'tracking_code' => 'TRK-DEMO1004'])->save();
            $paymentService->recordOfflineCounterPayment($order4, $owner1, (float) $order4->total_amount, 'cash_counter', 'OR-DEMO-1004');
            $item4 = $order4->items()->firstOrFail();
            $item4->forceFill(['document_original_name' => 'color-lab-manual.pdf'])->save();
            $queueService->assignItem($item4->fresh(), $staff1, $printerEpson->id, $staff2->id);
            $queueService->approvePrepress($item4->fresh(), $staff1);
            $queueService->startPrintingItem($item4->fresh(), $staff2);
            $queueService->failQualityCheckWithDualSpoilage(
                $item4->fresh(),
                $staff2,
                'Duplex feeder skewed border margins during color pass',
                10.0,
                $a4Paper1->id,
                $tonerInk1->id,
                0.40,
            );
            $item4->fresh()->forceFill(['production_stage' => OrderItem::STAGE_QUALITY_CHECK])->save();
            $order4->fresh()->syncParentStateFromItems();
        }

        // 5. PQ-DEMO-1005: Completed Order with Counter Balance Settlement (Official Receipt)
        if (! Order::where('order_number', 'PQ-DEMO-1005')->exists()) {
            $order5 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [[
                    'service_key' => 'thesis_binding',
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'binding_type' => 'softbound',
                    'bw_pages' => 44,
                    'color_pages' => 4,
                    'paper_size' => 'A4',
                    'print_sides' => 'simplex',
                    'cover_color' => 'Clear PVC / Standard Cardstock',
                    'copies_count' => 1,
                    'existing_file_path' => 'orders/manuscripts/demo-thesis-manuscript.pdf',
                ]],
                false,
                Order::PAYMENT_OPTION_DOWNPAYMENT_50,
            );
            $order5->forceFill(['order_number' => 'PQ-DEMO-1005', 'tracking_code' => 'TRK-DEMO1005'])->save();
            $half5 = round((float) $order5->total_amount * 0.5, 2);
            $proof5 = $paymentService->submitProof($order5, $customer1, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $half5, 'GCASH-DEMO-1005');
            $paymentService->verifyPayment($proof5, $owner1);
            $item5 = $order5->items()->firstOrFail();
            $queueService->approvePrepress($item5->fresh(), $staff1);
            $queueService->passQualityCheck($item5->fresh(), $staff1, 'B-02');
            $remaining5 = round((float) $order5->fresh()->balance_due, 2);
            $paymentService->recordOfflineCounterPayment($order5->fresh(), $owner1, $remaining5, 'cash_counter', 'COUNTER-OR-1005');
            $queueService->claimItem($item5->fresh(), $staff1);
        }

        // 6. PQ-DEMO-1006: Completed Order with Settled Manual Refund (Sales report reconciliation)
        if (! Order::where('order_number', 'PQ-DEMO-1006')->exists()) {
            $order6 = $checkout->placeOrder(
                $shop1,
                $customer1,
                [[
                    'service_key' => 'thesis_binding',
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'binding_type' => 'hardbound',
                    'bw_pages' => 80,
                    'color_pages' => 0,
                    'paper_size' => 'A4',
                    'print_sides' => 'simplex',
                    'cover_color' => 'Black',
                    'foil_color' => 'Gold',
                    'copies_count' => 1,
                    'existing_file_path' => 'orders/manuscripts/demo-thesis-manuscript.pdf',
                ]],
                false,
                Order::PAYMENT_OPTION_FULL_100,
            );
            $order6->forceFill(['order_number' => 'PQ-DEMO-1006', 'tracking_code' => 'TRK-DEMO1006'])->save();
            $proof6 = $paymentService->submitProof($order6, $customer1, Order::PAYMENT_OPTION_FULL_100, 'gcash', (float) $order6->total_amount, 'GCASH-DEMO-1006');
            $paymentService->verifyPayment($proof6, $owner1);
            $item6 = $order6->items()->firstOrFail();
            $rev6 = $revisionService->requestRevision($item6->fresh(), $customer1, ['bw_pages' => 50], 'Reduced printed pages after adviser review.');
            $revisionService->approveAndApplyRevision($rev6, $owner1);
            $revisionService->settleManualRefund($order6->fresh(), $owner1, 'REFUND-1006');
            $queueService->approvePrepress($item6->fresh(), $staff1);
            $queueService->passQualityCheck($item6->fresh(), $staff1, 'B-05');
            $queueService->claimItem($item6->fresh(), $staff1);
        }
    }

    private function seedQueueWorkloadOrders(
        PrintShop $shop,
        User $owner,
        User $customer,
        string $orderPrefix,
        int $count,
    ): void {
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        for ($i = 1; $i <= $count; $i++) {
            $orderNumber = $orderPrefix.$i;
            if (Order::where('order_number', $orderNumber)->exists()) {
                continue;
            }

            $order = $checkout->placeOrder(
                $shop,
                $customer,
                [[
                    'service_key' => 'document_printing',
                    'total_pages' => 16,
                    'color_mode' => 'bw',
                    'paper_size' => 'A4',
                    'paper_stock' => '70gsm',
                    'print_sides' => 'duplex',
                    'finishing_type' => 'staple',
                    'copies_count' => 1,
                ]],
                false,
                Order::PAYMENT_OPTION_FULL_100,
            );
            $order->forceFill([
                'order_number' => $orderNumber,
                'tracking_code' => 'TRK-'.$orderPrefix.$i,
            ])->save();
            $paymentService->recordOfflineCounterPayment($order, $owner, (float) $order->total_amount, 'cash_counter', 'OR-'.$orderNumber);
        }
    }

    /**
     * @param  array<string, array{is_active: bool, capacity: int, pause_reason: ?string, paused_until: mixed}>  $services
     */
    private function ensureShopServices(PrintShop $shop, array $services): void
    {
        $order = 1;
        foreach ($services as $key => $cfg) {
            $shop->services()->updateOrCreate(
                ['service_key' => $key],
                [
                    'is_active' => $cfg['is_active'],
                    'capacity' => $cfg['capacity'],
                    'pause_reason' => $cfg['pause_reason'],
                    'paused_until' => $cfg['paused_until'],
                    'display_order' => $order++,
                ],
            );
        }
    }

    private function seedShopConfigsWithoutBom(PrintShop $shop): void
    {
        DocumentPrintingConfig::updateOrCreate(
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
                'auto_deduct_inventory' => false,
            ],
        );

        ThesisBindingConfig::updateOrCreate(
            ['print_shop_id' => $shop->id],
            [
                'is_active' => true,
                'hardbound_base_price' => 360.00,
                'softbound_base_price' => 150.00,
                'allow_customer_supplied_paper' => true,
                'hardbound_cover_only_price' => 300.00,
                'page_price_bw' => 2.00,
                'page_price_color' => 5.00,
                'cover_colors' => ['Maroon', 'Black'],
                'foil_colors' => ['Gold', 'Silver'],
                'paper_sizes' => ['A4'],
                'auto_deduct_inventory' => false,
                'max_binding_sheets' => 250,
                'require_pdf_upload' => false,
            ],
        );
    }

    private function upsertUser(
        string $email,
        string $name,
        string $role,
        string $passwordHash,
        ?int $staffShopId = null,
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'password' => $passwordHash,
            ],
        );

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'staff_shop_id' => $staffShopId,
        ])->save();

        return $user;
    }

    private function ensureSamplePdfsExist(): void
    {
        $pdfContent = $this->minimalPdfBytes('PressQueue Sample Print Job');
        Storage::disk('local')->put('orders/manuscripts/demo-thesis-manuscript.pdf', $pdfContent);
        Storage::disk('local')->put('orders/documents/demo-handout-pack.pdf', $pdfContent);
    }

    private function minimalPdfBytes(string $title): string
    {
        $safeTitle = str_replace(['(', ')', '\\'], '', $title);
        $stream = "BT /F1 18 Tf 72 720 Td ({$safeTitle}) Tj ET\n";
        $stream .= 'BT /F1 12 Tf 72 690 Td (PressQueue Multi-Tenant Academic Printing & Bindery System) Tj ET';
        $len = strlen($stream);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length {$len} >>\nstream\n{$stream}\nendstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
