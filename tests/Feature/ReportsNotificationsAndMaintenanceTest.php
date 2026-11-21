<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
use App\Notifications\AgingOperationalAlertNotification;
use App\Notifications\LowStockReorderNotification;
use App\Notifications\OrderPickupReadyNotification;
use App\Notifications\PaymentStatusNotification;
use App\Notifications\PrepressActionRequiredNotification;
use App\Notifications\SpecRevisionNotification;
use App\Services\InventoryDeductionService;
use App\Services\OrderCheckoutService;
use App\Services\PaymentService;
use App\Services\ProductionQueueService;
use App\Services\ReportService;
use App\Services\SpecRevisionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsNotificationsAndMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected User $otherOwner;

    protected User $customer;

    protected User $otherCustomer;

    protected PrintShop $shop;

    protected PrintShop $otherShop;

    protected ThesisBindingConfig $thesisConfig;

    protected DocumentPrintingConfig $docConfig;

    protected InventoryItem $paperItem;

    protected InventoryItem $inkItem;

    protected InventoryItem $maroonCoverItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $this->otherOwner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->otherCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'PressQueue Central Bindery',
            'approval_status' => PrintShop::STATUS_APPROVED,
            'is_setup_completed' => true,
            'address' => '101 Print Blvd',
            'contact' => '09170001122',
            'busy_threshold' => 2,
            'at_capacity_threshold' => 4,
            'operating_hours' => 'Mon-Sat 8:00 AM - 6:00 PM',
        ]);
        $this->shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $this->shop->services()->create(['service_key' => 'document_printing', 'is_active' => true]);
        $this->staff->forceFill(['staff_shop_id' => $this->shop->id])->save();

        $this->otherShop = PrintShop::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Other Rival Copy Shop',
            'approval_status' => PrintShop::STATUS_APPROVED,
            'is_setup_completed' => true,
        ]);
        $this->otherShop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $this->otherShop->services()->create(['service_key' => 'document_printing', 'is_active' => true]);

        ThesisBindingConfig::create([
            'print_shop_id' => $this->otherShop->id,
            'is_active' => true,
            'hardbound_base_price' => 400.00,
            'softbound_base_price' => 180.00,
            'page_price_bw' => 2.50,
            'page_price_color' => 6.00,
            'cover_colors' => ['Black'],
            'foil_colors' => ['Gold'],
            'paper_sizes' => ['A4'],
            'auto_deduct_inventory' => false,
            'require_pdf_upload' => false,
        ]);

        $this->thesisConfig = ThesisBindingConfig::create([
            'print_shop_id' => $this->shop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'allow_customer_supplied_paper' => true,
            'hardbound_cover_only_price' => 300.00,
            'page_price_bw' => 2.00,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Blue', 'Black'],
            'foil_colors' => ['Gold', 'Silver'],
            'paper_sizes' => ['A4', 'Letter (Short)'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'max_binding_sheets' => 250,
            'require_pdf_upload' => false,
            'custom_cover_fields' => ['Thesis Title', 'Author'],
        ]);

        $this->docConfig = DocumentPrintingConfig::create([
            'print_shop_id' => $this->shop->id,
            'is_active' => true,
            'page_price_bw_short' => 1.50,
            'page_price_bw_a4' => 1.50,
            'page_price_bw_long' => 2.00,
            'page_price_color_short' => 5.00,
            'page_price_color_a4' => 5.00,
            'page_price_color_long' => 6.00,
            'paper_stock_80gsm_price' => 0.50,
            'paper_stock_100gsm_price' => 1.50,
            'duplex_discount_percent' => 10,
            'staple_price' => 2.00,
            'folder_fastener_price' => 15.00,
            'ring_bind_base_price' => 45.00,
            'booklet_staple_price' => 20.00,
            'rush_fee_amount' => 50.00,
        ]);

        $this->paperItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'A4 Bond Paper 80gsm',
            'sku' => 'PAP-A4-80',
            'category' => 'Paper',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'all',
            'stock_qty' => 2000.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 100.0,
            'unit_cost' => 0.50,
        ]);

        $this->inkItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Black & Color Laser Toner',
            'sku' => 'TON-CMYK-01',
            'category' => 'Ink / Toner',
            'item_type' => InventoryItem::TYPE_CONSUMABLE,
            'service_tag' => 'all',
            'stock_qty' => 100.0,
            'reserved_qty' => 0.0,
            'unit' => 'ml',
            'reorder_level' => 10.0,
            'unit_cost' => 15.00,
        ]);

        $this->maroonCoverItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Leatherette Cover Maroon',
            'sku' => 'COV-LTH-MAR',
            'category' => 'Cover',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'thesis_binding',
            'stock_qty' => 50.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 5.0,
            'unit_cost' => 25.00,
        ]);

        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $this->thesisConfig->id,
            'inventory_item_id' => $this->paperItem->id,
            'binding_type' => 'both',
            'usage_qty' => 1.0,
            'unit' => 'sheets',
        ]);

        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $this->thesisConfig->id,
            'inventory_item_id' => $this->maroonCoverItem->id,
            'binding_type' => 'hardbound',
            'usage_qty' => 1.0,
            'unit' => 'sheets',
        ]);
    }

    public function test_standardized_reports_render_reconcile_ledgers_enforce_tenant_isolation_and_export_pdf_and_csv(): void
    {
        Mail::fake();

        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $deductionService = app(InventoryDeductionService::class);
        $revisionService = app(SpecRevisionService::class);
        $reportService = app(ReportService::class);

        $printer = Printer::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Xerox AltaLink C8155',
            'status' => Printer::STATUS_ONLINE,
            'supported_sizes' => ['A4', 'Letter'],
        ]);

        // Order 1: 50% DP verified + remaining settled at counter + item currently printing
        $orderDp = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'hardbound',
            'bw_pages' => 50,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'custom_fields_data' => ['Thesis Title' => 'DP Thesis', 'Author' => 'Alice'],
        ]]);
        $dpHalf = round((float) $orderDp->total_amount * 0.5, 2);
        $dpProof = $paymentService->submitProof($orderDp, $this->customer, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $dpHalf, 'GCASHDP0001');
        $paymentService->verifyPayment($dpProof, $this->owner);
        $remaining = round((float) $orderDp->fresh()->balance_due, 2);
        $paymentService->recordOfflineCounterPayment($orderDp->fresh(), $this->owner, $remaining, 'cash_counter', 'COUNTER-DP-1');

        $dpItem = $orderDp->items()->firstOrFail();
        $dpItem->update([
            'printer_id' => $printer->id,
            'assigned_staff_id' => $this->staff->id,
            'production_stage' => OrderItem::STAGE_PRINTING,
        ]);

        // Order 2: 100% full prepay verified + downgrade revision creating a settled manual refund, and aging ready_for_pickup
        $orderFull = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'hardbound',
            'bw_pages' => 100,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'custom_fields_data' => ['Thesis Title' => 'Full Thesis', 'Author' => 'Bob'],
        ]]);
        $fullProof = $paymentService->submitProof($orderFull, $this->customer, Order::PAYMENT_OPTION_FULL_100, 'gcash', (float) $orderFull->total_amount, 'GCASHFULL01');
        $paymentService->verifyPayment($fullProof, $this->owner);

        $fullItem = $orderFull->items()->firstOrFail();
        $downgrade = $revisionService->requestRevision($fullItem, $this->customer, [
            'bw_pages' => 60,
        ], 'Trimmed appendix chapters');
        $revisionService->approveAndApplyRevision($downgrade, $this->owner);
        $expectedRefund = round((float) $orderFull->fresh()->refund_due, 2);
        $this->assertGreaterThan(0.0, $expectedRefund);
        $revisionService->settleManualRefund($orderFull->fresh(), $this->owner, 'REFUND-001');

        $fullItem->fresh()->update([
            'printer_id' => $printer->id,
            'assigned_staff_id' => $this->staff->id,
            'production_stage' => OrderItem::STAGE_READY_FOR_PICKUP,
            'shelf_bin_code' => 'B-09',
            'ready_at' => now()->subDays(4),
        ]);
        $orderFull->fresh()->syncParentStateFromItems();

        // Order 3: finishing/QC item for schedule bucket verification
        $orderFin = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'document_printing',
            'total_pages' => 25,
            'color_mode' => 'bw',
            'paper_size' => 'A4',
            'paper_stock' => '80gsm',
            'print_sides' => 'simplex',
            'finishing_type' => 'ring_bind',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($orderFin, $this->owner, (float) $orderFin->total_amount);
        $orderFin->items()->firstOrFail()->update([
            'printer_id' => $printer->id,
            'assigned_staff_id' => $this->staff->id,
            'production_stage' => OrderItem::STAGE_BINDING,
        ]);

        // Log spoilage for inventory & sales report reconciliation
        $deductionService->recordSpoilage($this->paperItem, 20.0, 'Paper jam', $this->staff);

        // Tenant isolation: rival shop order + inventory must never leak into owner's reports
        InventoryItem::create([
            'print_shop_id' => $this->otherShop->id,
            'name' => 'Secret Rival Foil',
            'sku' => 'RIV-999',
            'category' => 'Foil',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'all',
            'stock_qty' => 999.0,
            'reserved_qty' => 0.0,
            'unit' => 'rolls',
            'reorder_level' => 5.0,
            'unit_cost' => 100.0,
        ]);
        $otherOrder = $checkout->placeOrder($this->otherShop, $this->otherCustomer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'softbound',
            'bw_pages' => 20,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($otherOrder, $this->otherOwner, (float) $otherOrder->total_amount);

        // Assert ReportService data accuracy & reconciliation
        $salesReport = $reportService->buildSalesReport($this->shop, 'this_month');
        $this->assertEqualsWithDelta($dpHalf, $salesReport['summary']['downpayments_50'], 0.01);
        $this->assertEqualsWithDelta((float) $fullProof->amount, $salesReport['summary']['full_prepayments_100'], 0.01);
        $this->assertGreaterThan(0.0, $salesReport['summary']['counter_balance_settlements']);
        $this->assertEqualsWithDelta($expectedRefund, $salesReport['summary']['manual_refunds_settled'], 0.01);
        $this->assertGreaterThan(0.0, $salesReport['summary']['spoilage_rework_loss']);
        $this->assertNotContains($otherOrder->order_number, array_column($salesReport['orders'], 'order_number'));

        $inventoryReport = $reportService->buildInventoryReport($this->shop, 'this_month');
        $this->assertEqualsWithDelta(10.0, $inventoryReport['summary']['spoilage_loss_cost'], 0.01);
        $this->assertNotContains('Secret Rival Foil', array_column($inventoryReport['items'], 'name'));

        $scheduleReport = $reportService->buildProductionScheduleReport($this->shop);
        $this->assertCount(1, $scheduleReport['currently_printing']);
        $this->assertCount(1, $scheduleReport['in_finishing']);
        $this->assertCount(1, $scheduleReport['ready_for_pickup']);
        $this->assertSame('B-09', $scheduleReport['ready_for_pickup'][0]['shelf_bin_code']);
        $this->assertSame(1, $scheduleReport['summary']['aging_pickup_count']);
        $this->assertNotContains($otherOrder->order_number, array_column($scheduleReport['currently_printing'], 'order_number'));

        // Access control check
        $this->get(route('owner.reports.show', ['type' => 'sales']))->assertRedirect(route('login'));
        $this->actingAs($this->customer)->get(route('owner.reports.show', ['type' => 'sales']))->assertRedirect(route('customer.dashboard'));
        $this->actingAs($this->staff)->get(route('owner.reports.show', ['type' => 'sales']))->assertRedirect(route('staff.dashboard'));

        // Owner HTML, PDF, and CSV endpoints across all 3 types
        foreach (['inventory', 'sales', 'schedule'] as $type) {
            $htmlResp = $this->actingAs($this->owner)
                ->get(route('owner.reports.show', ['type' => $type, 'period' => 'this_month']));
            $htmlResp->assertOk()
                ->assertDontSee($otherOrder->order_number)
                ->assertDontSee('Secret Rival Foil');

            $pdfResp = $this->actingAs($this->owner)
                ->get(route('owner.reports.show', ['type' => $type, 'period' => 'this_month', 'format' => 'pdf']));
            $pdfResp->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $pdfResp->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF', $pdfResp->getContent());

            $csvResp = $this->actingAs($this->owner)
                ->get(route('owner.reports.show', ['type' => $type, 'period' => 'this_month', 'format' => 'csv']));
            $csvResp->assertOk();
            $csvBody = $csvResp->streamedContent();
            $this->assertStringNotContainsString($otherOrder->order_number, $csvBody);
            $this->assertStringNotContainsString('Secret Rival Foil', $csvBody);
        }

        // Analytics Hub 3rd 'reports' tab preserves default 'overview' and switches cleanly
        Livewire::actingAs($this->owner)
            ->test('pages::owner.⚡analytics-hub')
            ->assertSet('active_tab', 'overview')
            ->set('active_tab', 'reports')
            ->assertSee('Standardized Reports')
            ->assertSee($orderDp->order_number)
            ->set('report_type', 'inventory')
            ->assertSee('A4 Bond Paper 80gsm')
            ->set('report_type', 'schedule')
            ->assertSee('B-09')
            ->assertDontSee('Secret Rival Foil');
    }

    public function test_notifications_are_dispatched_deduplicated_and_manageable_via_notification_bell(): void
    {
        Mail::fake();

        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $queueService = app(ProductionQueueService::class);
        $revisionService = app(SpecRevisionService::class);
        $deductionService = app(InventoryDeductionService::class);

        // 1. Low-stock notification + 12h dedup
        $lowPaper = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Legal Cream Bond Paper',
            'sku' => 'PAP-LEG-80',
            'category' => 'Paper',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'all',
            'stock_qty' => 25.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 50.0,
            'unit_cost' => 0.70,
        ]);

        $deductionService->notifyIfLowStock($lowPaper);
        $deductionService->notifyIfLowStock($lowPaper); // deduplicated

        $this->assertSame(
            1,
            $this->owner->notifications()->where('type', LowStockReorderNotification::class)->count()
        );

        // 2. Payment proof submitted -> verified -> counter settled + another rejected
        Storage::fake('local');
        $pdfA = UploadedFile::fake()->create('thesis-a.pdf', 100, 'application/pdf');
        $pdfFixed = UploadedFile::fake()->create('thesis-fixed.pdf', 100, 'application/pdf');

        $order = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'hardbound',
            'bw_pages' => 40,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'uploaded_file' => $pdfA,
        ]]);

        $dpHalf = round((float) $order->total_amount * 0.5, 2);
        $proofBad = $paymentService->submitProof($order, $this->customer, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $dpHalf, 'BADREF001');
        $paymentService->rejectPayment($proofBad, $this->owner, 'Blurred reference');

        $proofGood = $paymentService->submitProof($order->fresh(), $this->customer, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $dpHalf, 'GOODREF01');
        $paymentService->verifyPayment($proofGood, $this->owner);

        $item = $order->items()->firstOrFail();

        // 3. Prepress reject -> reupload -> approve -> QC ready pickup notifications
        $queueService->rejectPrepress($item, $this->staff, 'Missing title page');
        $queueService->reuploadArtwork($item->fresh(), $this->customer, $pdfFixed);
        $queueService->approvePrepress($item->fresh(), $this->staff);

        // 4. Spec revision request -> approval notifications
        $rev = $revisionService->requestRevision($item->fresh(), $this->customer, ['cover_color' => 'Blue'], 'Prefer Navy Blue');
        $revisionService->approveAndApplyRevision($rev, $this->owner);

        // 5. QC ready notification
        $queueService->passQualityCheck($item->fresh(), $this->staff, 'C-04');

        // 6. Cover-only shift to shop paper notification
        $coverOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_COVER_ONLY,
            'binding_type' => 'hardbound',
            'preprinted_total_pages' => 50,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'uploaded_file' => UploadedFile::fake()->create('preprinted.pdf', 80, 'application/pdf'),
        ]]);
        $paymentService->recordOfflineCounterPayment($coverOrder, $this->owner, (float) $coverOrder->total_amount);
        $queueService->rejectCoverOnlyAndShiftToShopPaper(
            $coverOrder->items()->firstOrFail(),
            $this->staff,
            'Damaged paper intake',
            40,
            10,
        );

        // Assert notifications reached expected recipients across all workflow notification classes
        $this->assertGreaterThanOrEqual(2, $this->owner->notifications()->where('type', PaymentStatusNotification::class)->count());
        $this->assertGreaterThanOrEqual(1, $this->owner->notifications()->where('type', PrepressActionRequiredNotification::class)->count());
        $this->assertGreaterThanOrEqual(1, $this->owner->notifications()->where('type', SpecRevisionNotification::class)->count());

        $this->assertGreaterThanOrEqual(2, $this->customer->notifications()->where('type', PaymentStatusNotification::class)->count());
        $this->assertGreaterThanOrEqual(2, $this->customer->notifications()->where('type', PrepressActionRequiredNotification::class)->count());
        $this->assertGreaterThanOrEqual(1, $this->customer->notifications()->where('type', SpecRevisionNotification::class)->count());
        $this->assertSame(1, $this->customer->notifications()->where('type', OrderPickupReadyNotification::class)->count());

        // Test Livewire notification-bell isolation, markAsRead & markAllRead
        $ownerUnreadBefore = $this->owner->unreadNotifications()->count();
        $customerUnreadBefore = $this->customer->unreadNotifications()->count();
        $this->assertGreaterThanOrEqual(3, $ownerUnreadBefore);
        $this->assertGreaterThanOrEqual(5, $customerUnreadBefore);

        /** @var DatabaseNotification $firstOwnerNotif */
        $firstOwnerNotif = $this->owner->unreadNotifications()->firstOrFail();

        // Cross-user isolation: customer cannot mark owner's notification as read
        Livewire::actingAs($this->customer)
            ->test('notification-bell')
            ->call('markAsRead', (string) $firstOwnerNotif->id);

        $this->assertNull($firstOwnerNotif->fresh()?->read_at);

        // Owner marks one read, then marks all read
        Livewire::actingAs($this->owner)
            ->test('notification-bell')
            ->assertSet('open', false)
            ->call('toggle')
            ->assertSet('open', true)
            ->assertSee((string) ($firstOwnerNotif->data['title'] ?? ''))
            ->call('markAsRead', (string) $firstOwnerNotif->id)
            ->call('markAllRead');

        $this->assertSame(0, $this->owner->unreadNotifications()->count());
        $this->assertSame($customerUnreadBefore, $this->customer->unreadNotifications()->count());

        // Verify portal pages render cleanly with embedded notification bell
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk();
        $this->actingAs($this->owner)->get(route('owner.analytics-hub'))->assertOk();
        $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertOk();
        $this->actingAs($this->customer)->get(route('customer.dashboard'))->assertOk();
        $this->actingAs($this->customer)->get(route('shops.index'))->assertOk();
    }

    public function test_scheduled_file_purge_command_respects_clamped_ttl_preserves_receipts_and_skips_active_orders(): void
    {
        Storage::fake('local');

        $settings = PlatformSetting::current();
        $settings->update([
            'default_retention_days' => 30,
            'min_retention_days' => 14,
            'max_retention_days' => 90,
        ]);

        // Shop asks for 7 days -> clamped by platform min_retention_days = 14
        $this->shop->forceFill(['file_retention_days' => 7])->save();

        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        // Helper files
        Storage::disk('local')->put('orders/manuscripts/expired.pdf', 'expired-bytes');
        Storage::disk('local')->put('orders/manuscripts/fresh-completed.pdf', 'fresh-bytes');
        Storage::disk('local')->put('orders/manuscripts/on-hold.pdf', 'hold-bytes');
        Storage::disk('local')->put('orders/manuscripts/partially-ready.pdf', 'partial-bytes');

        // 1. Expired completed order (16 days old > 14d clamped TTL) => PURGED
        $expiredOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'softbound',
            'bw_pages' => 20,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($expiredOrder, $this->owner, (float) $expiredOrder->total_amount);
        $expiredOrder->forceFill([
            'order_status' => Order::STATUS_COMPLETED,
            'completed_at' => now()->subDays(16),
        ])->save();
        $expiredItem = $expiredOrder->items()->firstOrFail();
        $expiredItem->forceFill([
            'production_stage' => OrderItem::STAGE_CLAIMED,
            'document_file_path' => 'orders/manuscripts/expired.pdf',
            'document_original_name' => 'expired.pdf',
            'claimed_at' => now()->subDays(16),
        ])->save();

        // 2. Completed order only 10 days old (< 14d clamped TTL) => KEPT
        $withinTtlOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'softbound',
            'bw_pages' => 20,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($withinTtlOrder, $this->owner, (float) $withinTtlOrder->total_amount);
        $withinTtlOrder->forceFill([
            'order_status' => Order::STATUS_COMPLETED,
            'completed_at' => now()->subDays(10),
        ])->save();
        $withinTtlItem = $withinTtlOrder->items()->firstOrFail();
        $withinTtlItem->forceFill([
            'production_stage' => OrderItem::STAGE_CLAIMED,
            'document_file_path' => 'orders/manuscripts/fresh-completed.pdf',
            'claimed_at' => now()->subDays(10),
        ])->save();

        // 3. On-Hold order => KEPT
        $onHoldOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'softbound',
            'bw_pages' => 20,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'copies_count' => 1,
        ]]);
        $onHoldOrder->forceFill(['order_status' => Order::STATUS_ON_HOLD])->save();
        $onHoldItem = $onHoldOrder->items()->firstOrFail();
        $onHoldItem->forceFill([
            'production_stage' => OrderItem::STAGE_ON_HOLD,
            'document_file_path' => 'orders/manuscripts/on-hold.pdf',
        ])->save();

        // 4. Partially ready order (has an unclaimed ready_for_pickup item) => KEPT
        $partialOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'softbound',
            'bw_pages' => 20,
            'color_pages' => 0,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'copies_count' => 1,
        ]]);
        $partialOrder->forceFill(['order_status' => Order::STATUS_PARTIALLY_READY])->save();
        $partialItem = $partialOrder->items()->firstOrFail();
        $partialItem->forceFill([
            'production_stage' => OrderItem::STAGE_READY_FOR_PICKUP,
            'document_file_path' => 'orders/manuscripts/partially-ready.pdf',
        ])->save();

        $this->artisan('pressqueue:purge-expired-files')
            ->expectsOutputToContain('Purged 1 expired order artwork file(s).')
            ->assertExitCode(0);

        Storage::disk('local')->assertMissing('orders/manuscripts/expired.pdf');
        Storage::disk('local')->assertExists('orders/manuscripts/fresh-completed.pdf');
        Storage::disk('local')->assertExists('orders/manuscripts/on-hold.pdf');
        Storage::disk('local')->assertExists('orders/manuscripts/partially-ready.pdf');

        $this->assertNotNull($expiredItem->fresh()->file_purged_at);
        $this->assertNull($withinTtlItem->fresh()->file_purged_at);
        $this->assertNull($onHoldItem->fresh()->file_purged_at);
        $this->assertNull($partialItem->fresh()->file_purged_at);

        $this->assertTrue(
            AuditLog::where('action', 'files.purged')
                ->where('print_shop_id', $this->shop->id)
                ->exists()
        );

        // Historical receipt remains accessible after source file purge
        $this->actingAs($this->customer)
            ->get(route('orders.receipt', $expiredOrder))
            ->assertOk()
            ->assertSee($expiredOrder->order_number);

        // Idempotent re-run purges 0 additional files
        $this->artisan('pressqueue:purge-expired-files')
            ->expectsOutputToContain('Purged 0 expired order artwork file(s).')
            ->assertExitCode(0);
    }

    public function test_scheduled_aging_alerts_command_notifies_once_and_schedule_registers_wp4_jobs(): void
    {
        Mail::fake();

        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        // 1. Aging unverified payment proof (> 2 hours)
        $agingPayOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'document_printing',
            'total_pages' => 15,
            'color_mode' => 'bw',
            'paper_size' => 'A4',
            'paper_stock' => '80gsm',
            'print_sides' => 'simplex',
            'finishing_type' => 'loose',
            'copies_count' => 1,
        ]]);
        $agingProof = $paymentService->submitProof(
            $agingPayOrder,
            $this->customer,
            Order::PAYMENT_OPTION_FULL_100,
            'gcash',
            (float) $agingPayOrder->total_amount,
            'AGINGPAY001',
        );
        $agingProof->forceFill(['submitted_at' => now()->subHours(3)])->save();

        // 2. Fresh payment proof (< 2 hours) should NOT alert
        $freshPayOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'document_printing',
            'total_pages' => 10,
            'color_mode' => 'bw',
            'paper_size' => 'A4',
            'paper_stock' => '80gsm',
            'print_sides' => 'simplex',
            'finishing_type' => 'loose',
            'copies_count' => 1,
        ]]);
        $paymentService->submitProof(
            $freshPayOrder,
            $this->customer,
            Order::PAYMENT_OPTION_FULL_100,
            'gcash',
            (float) $freshPayOrder->total_amount,
            'FRESHPAY001',
        );

        // 3. Unclaimed ready_for_pickup item (> 3 days)
        $agingPickupOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'document_printing',
            'total_pages' => 20,
            'color_mode' => 'bw',
            'paper_size' => 'A4',
            'paper_stock' => '80gsm',
            'print_sides' => 'simplex',
            'finishing_type' => 'loose',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($agingPickupOrder, $this->owner, (float) $agingPickupOrder->total_amount);
        $agingPickupItem = $agingPickupOrder->items()->firstOrFail();
        $agingPickupItem->forceFill([
            'production_stage' => OrderItem::STAGE_READY_FOR_PICKUP,
            'shelf_bin_code' => 'A-12',
            'ready_at' => now()->subDays(4),
            'claimed_at' => null,
        ])->save();

        $this->artisan('pressqueue:check-aging-alerts')->assertExitCode(0);

        $this->assertSame(
            1,
            $this->owner->notifications()->where('type', AgingOperationalAlertNotification::class)->count()
        );
        $this->assertSame(
            1,
            $this->customer->notifications()->where('type', AgingOperationalAlertNotification::class)->count()
        );
        $this->assertTrue(AuditLog::where('action', 'alerts.aging_payment_proof')->where('print_shop_id', $this->shop->id)->exists());
        $this->assertTrue(AuditLog::where('action', 'alerts.unclaimed_pickup')->where('print_shop_id', $this->shop->id)->exists());

        // Second run deduplicates both alerts cleanly
        $this->artisan('pressqueue:check-aging-alerts')->assertExitCode(0);

        $this->assertSame(
            1,
            $this->owner->notifications()->where('type', AgingOperationalAlertNotification::class)->count()
        );
        $this->assertSame(
            1,
            $this->customer->notifications()->where('type', AgingOperationalAlertNotification::class)->count()
        );

        // Verify Schedule registration in routes/console.php
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command);

        $this->assertTrue($commands->contains(fn (string $cmd): bool => str_contains($cmd, 'pressqueue:purge-expired-files')));
        $this->assertTrue($commands->contains(fn (string $cmd): bool => str_contains($cmd, 'pressqueue:check-aging-alerts')));
    }
}
