<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Printer;
use App\Models\PrintShop;
use App\Models\SpecRevision;
use App\Models\StockMovement;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use App\Services\OrderCheckoutService;
use App\Services\PaymentService;
use App\Services\ProductionQueueService;
use App\Services\SpecRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProductionWorkflowAndRevisionsTest extends TestCase
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
            'name' => 'Other Copy Shop',
            'approval_status' => PrintShop::STATUS_APPROVED,
            'is_setup_completed' => true,
        ]);
        $this->otherShop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);

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

    public function test_owner_manages_printers_workload_thresholds_and_maintenance_failover(): void
    {
        // Configure thresholds & operating hours via owner.platform.save
        $this->actingAs($this->owner)
            ->post(route('owner.platform.save'), [
                'address' => '101 Print Blvd',
                'contact' => '09170001122',
                'operating_hours' => 'Mon-Fri 7:00 AM - 7:00 PM',
                'busy_threshold' => 1,
                'at_capacity_threshold' => 2,
            ])
            ->assertRedirect();

        // Register printers
        $this->post(route('owner.printers.store'), [
            'name' => 'Canon Laser Alpha',
            'supported_sizes_input' => 'A4, Letter',
            'is_wide_format' => '0',
        ])->assertRedirect();

        $this->post(route('owner.printers.store'), [
            'name' => 'HP Backup Beta',
            'supported_sizes_input' => 'A4, Letter, Legal',
            'is_wide_format' => '0',
        ])->assertRedirect();

        $this->post(route('owner.printers.store'), [
            'name' => 'Epson Wide Plotter',
            'supported_sizes_input' => 'A4, Legal',
            'is_wide_format' => '1',
        ])->assertRedirect();

        $printerAlpha = Printer::where('print_shop_id', $this->shop->id)->where('name', 'Canon Laser Alpha')->firstOrFail();
        $printerBeta = Printer::where('print_shop_id', $this->shop->id)->where('name', 'HP Backup Beta')->firstOrFail();
        $printerWide = Printer::where('print_shop_id', $this->shop->id)->where('name', 'Epson Wide Plotter')->firstOrFail();

        $this->assertTrue($printerAlpha->supportsSize('Letter (Short)'));
        $this->assertTrue($printerWide->is_wide_format);

        // Cross-tenant isolation check
        $this->actingAs($this->otherOwner)
            ->patch(route('owner.printers.update', $printerAlpha), ['status' => Printer::STATUS_MAINTENANCE])
            ->assertForbidden();

        // Marketplace displays Normal initially
        $this->actingAs($this->customer)
            ->get(route('shops.index'))
            ->assertOk()
            ->assertSee('Mon-Fri 7:00 AM - 7:00 PM')
            ->assertSee('Normal');

        // Place & admit order #1 -> 1 active item => Busy threshold reached
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        $order1 = $checkout->placeOrder($this->shop, $this->customer, [[
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
            'custom_fields_data' => ['Thesis Title' => 'Load 1', 'Author' => 'User 1'],
        ]]);
        $paymentService->recordOfflineCounterPayment($order1, $this->owner, (float) $order1->total_amount);

        $this->assertSame('Busy', $this->shop->fresh()->workloadStatus());
        $this->get(route('shops.show', $this->shop))->assertOk()->assertSee('Busy');

        // Assign order #1 item to Canon Laser Alpha
        $item1 = $order1->items()->firstOrFail();
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('assignItemPrinter', $item1->id, $printerAlpha->id);

        $this->assertSame($printerAlpha->id, $item1->fresh()->printer_id);

        // Place & admit order #2 -> 2 active items => At capacity threshold reached
        $order2 = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'document_printing',
            'total_pages' => 20,
            'color_mode' => 'bw',
            'paper_size' => 'A4',
            'paper_stock' => '70gsm',
            'print_sides' => 'simplex',
            'finishing_type' => 'loose',
            'copies_count' => 1,
        ]]);
        $paymentService->recordOfflineCounterPayment($order2, $this->owner, (float) $order2->total_amount);

        $this->assertSame('At capacity', $this->shop->fresh()->workloadStatus());

        // Maintenance failover moves queued item from Alpha -> Beta cleanly
        $this->actingAs($this->owner)
            ->patch(route('owner.printers.update', $printerAlpha), [
                'status' => Printer::STATUS_MAINTENANCE,
                'target_printer_id' => $printerBeta->id,
            ])
            ->assertRedirect();

        $this->assertSame(Printer::STATUS_MAINTENANCE, $printerAlpha->fresh()->status);
        $this->assertSame($printerBeta->id, $item1->fresh()->printer_id);
        $this->assertTrue(AuditLog::where('action', 'printer.failover_reassigned')->where('print_shop_id', $this->shop->id)->exists());

        // Delete unused wide plotter
        $this->delete(route('owner.printers.destroy', $printerWide))->assertRedirect();
        $this->assertDatabaseMissing('printers', ['id' => $printerWide->id]);
    }

    public function test_prepress_approval_lock_rejection_reupload_and_cover_only_shift_to_shop_paper(): void
    {
        Storage::fake('local');
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $queueService = app(ProductionQueueService::class);

        $badPdf = UploadedFile::fake()->create('bad-artwork.pdf', 128, 'application/pdf');
        $fixedPdf = UploadedFile::fake()->create('fixed-artwork.pdf', 128, 'application/pdf');

        // Full-package thesis with uploaded file
        $order = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'binding_type' => 'hardbound',
            'bw_pages' => 50,
            'color_pages' => 10,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'document_file' => $badPdf,
            'custom_fields_data' => ['Thesis Title' => 'Prepress Flow', 'Author' => 'Student A'],
        ]]);
        $paymentService->recordOfflineCounterPayment($order, $this->owner, (float) $order->total_amount);

        $item = $order->items()->firstOrFail();
        $oldPath = (string) $item->document_file_path;

        // Prepress rejection puts item on_hold and blocks startPrintingItem
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('openItemPrepressRejectModal', $item->id)
            ->set('prepressRejectReason', 'Margins cut off on page 12')
            ->call('confirmPrepressRejection');

        $item->refresh();
        $this->assertSame(OrderItem::PREPRESS_REJECTED, $item->prepress_status);
        $this->assertSame(OrderItem::STAGE_ON_HOLD, $item->production_stage);
        $this->assertFalse($item->is_spec_locked);
        $this->assertSame(Order::STATUS_ON_HOLD, $order->fresh()->order_status);

        try {
            $queueService->startPrintingItem($item, $this->staff);
            $this->fail('Expected printing start block on prepress-rejected item.');
        } catch (HttpException) {
            // Expected 422 abort
        }

        // Customer re-uploads corrected PDF on customer dashboard
        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡customer-dashboard')
            ->call('openReuploadModal', $item->id)
            ->set('reupload_file', $fixedPdf)
            ->call('submitArtworkReupload')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame(OrderItem::PREPRESS_PENDING, $item->prepress_status);
        $this->assertSame(OrderItem::STAGE_QUEUE, $item->production_stage);
        $this->assertNotSame($oldPath, $item->document_file_path);
        $this->assertFalse(Storage::disk('local')->exists($oldPath));
        $this->assertTrue(Storage::disk('local')->exists((string) $item->document_file_path));

        // Staff approves prepress -> specs locked
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('approveItemPrepress', $item->id);

        $item->refresh();
        $this->assertSame(OrderItem::PREPRESS_APPROVED, $item->prepress_status);
        $this->assertTrue($item->is_spec_locked);

        // Bidirectional Cover-Only rejection -> Shift to Shop Paper
        $coverOrder = $checkout->placeOrder($this->shop, $this->customer, [[
            'service_key' => 'thesis_binding',
            'fulfillment_type' => OrderItem::FULFILLMENT_COVER_ONLY,
            'binding_type' => 'hardbound',
            'preprinted_total_pages' => 100,
            'paper_size' => 'A4',
            'print_sides' => 'simplex',
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'copies_count' => 1,
            'custom_fields_data' => ['Thesis Title' => 'Cover Only Shift', 'Author' => 'Student B'],
        ]]);
        $paymentService->recordOfflineCounterPayment($coverOrder, $this->owner, (float) $coverOrder->total_amount);

        $coverItem = $coverOrder->items()->firstOrFail();
        $reservedPaperBefore = (float) $this->paperItem->fresh()->reserved_qty;

        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('openShiftToShopPaperModal', $coverItem->id)
            ->set('shiftReason', 'Supplied pages damp and mismatched GSM')
            ->set('shiftBwPages', 100)
            ->set('shiftColorPages', 0)
            ->call('confirmShiftToShopPaper');

        $coverItem->refresh();
        $coverOrder->refresh();

        $this->assertSame(OrderItem::FULFILLMENT_FULL_PACKAGE, $coverItem->fulfillment_type);
        $this->assertTrue($coverItem->is_paper_received);
        // Hardbound base (350) + 100 B&W @ 2.00 (200) = 550.00 total vs old 300.00 cover-only
        $this->assertEqualsWithDelta(550.00, (float) $coverItem->total_price, 0.01);
        $this->assertEqualsWithDelta(550.00, (float) $coverOrder->total_amount, 0.01);
        $this->assertEqualsWithDelta(250.00, (float) $coverOrder->balance_due, 0.01);
        $this->assertSame(Order::PAYMENT_PARTIALLY_PAID, $coverOrder->payment_status);
        $this->assertEqualsWithDelta($reservedPaperBefore + 100.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);
        $this->assertDatabaseHas('spec_revisions', [
            'order_item_id' => $coverItem->id,
            'revision_type' => SpecRevision::TYPE_COVER_ONLY_TO_FULL,
            'status' => SpecRevision::STATUS_APPLIED,
        ]);
    }

    public function test_spec_revision_upgrade_overpaid_downgrade_manual_refund_and_post_print_freeze(): void
    {
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $revisionService = app(SpecRevisionService::class);
        $queueService = app(ProductionQueueService::class);

        $order = $checkout->placeOrder($this->shop, $this->customer, [[
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
            'custom_fields_data' => ['Thesis Title' => 'Rev Lifecycle', 'Author' => 'Reviser'],
        ]]);
        // Initial price: 350 + (50 * 2.00) = 450.00
        $paymentService->recordOfflineCounterPayment($order, $this->owner, 450.00);
        $item = $order->items()->firstOrFail();

        // 1. Pre-print UPGRADE via customer dashboard (copies 1 -> 2 => 900.00)
        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡customer-dashboard')
            ->call('openSpecRevisionModal', $item->id)
            ->set('rev_copies_count', 2)
            ->set('rev_notes', 'Need second hardbound copy for adviser')
            ->call('submitSpecRevision')
            ->assertHasNoErrors();

        $this->assertSame(OrderItem::STAGE_ON_HOLD, $item->fresh()->production_stage);
        $this->assertSame(Order::STATUS_ON_HOLD, $order->fresh()->order_status);
        $upgradeRev = SpecRevision::where('order_item_id', $item->id)->where('status', SpecRevision::STATUS_PENDING)->firstOrFail();
        $this->assertSame(SpecRevision::TYPE_UPGRADE, $upgradeRev->revision_type);

        // Staff approves upgrade -> BOM reservation increases from 50 -> 100 sheets & balance becomes 450.00
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('approveItemRevision', $upgradeRev->id);

        $order->refresh();
        $this->assertEqualsWithDelta(900.00, (float) $order->total_amount, 0.01);
        $this->assertEqualsWithDelta(450.00, (float) $order->balance_due, 0.01);
        $this->assertSame(Order::PAYMENT_PARTIALLY_PAID, $order->payment_status);
        $this->assertEqualsWithDelta(100.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);

        // Collect upgrade top-up (now paid = 900.00)
        $paymentService->recordOfflineCounterPayment($order, $this->owner, 450.00, 'cash_counter', 'TOPUP-100');

        // 2. Pre-print DOWNGRADE when already paid (copies 2 -> 1 => 450.00) => triggers Manual Refund Pending 450.00
        $downgradeRev = $revisionService->requestRevision($item->fresh(), $this->customer, ['copies_count' => 1], 'Switch back to 1 copy');
        $this->assertSame(SpecRevision::TYPE_DOWNGRADE, $downgradeRev->revision_type);
        $revisionService->approveAndApplyRevision($downgradeRev, $this->staff);

        $order->refresh();
        $this->assertEqualsWithDelta(450.00, (float) $order->total_amount, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $order->balance_due, 0.01);
        $this->assertEqualsWithDelta(450.00, (float) $order->refund_due, 0.01);
        $this->assertSame(Order::REFUND_MANUAL_PENDING, $order->refund_status);
        $this->assertEqualsWithDelta(50.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);

        // Owner settles manual refund from Analytics Hub
        Livewire::actingAs($this->owner)
            ->test('pages::owner.⚡analytics-hub')
            ->set('active_tab', 'payments')
            ->assertSee('Pending Manual Refunds')
            ->call('settleManualRefund', $order->id);

        $order->refresh();
        $this->assertSame(Order::REFUND_SETTLED, $order->refund_status);
        $this->assertEqualsWithDelta(0.0, (float) $order->refund_due, 0.01);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_MANUAL_REFUND,
            'amount' => -450.00,
        ]);
        $this->assertNotNull($downgradeRev->fresh()->settled_at);

        // 3. Post-printing freeze (R5): hard-block revision once printing starts
        $queueService->startPrintingItem($item->fresh(), $this->staff);

        try {
            $revisionService->requestRevision($item->fresh(), $this->customer, ['copies_count' => 3]);
            $this->fail('Expected 422 freeze once item started printing.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_multi_item_split_pickup_partially_ready_and_zero_balance_completion_gate(): void
    {
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $queueService = app(ProductionQueueService::class);

        $order = $checkout->placeOrder($this->shop, $this->customer, [
            [
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
                'custom_fields_data' => ['Thesis Title' => 'Split Order', 'Author' => 'Split User'],
            ],
            [
                'service_key' => 'document_printing',
                'total_pages' => 20,
                'color_mode' => 'bw',
                'paper_size' => 'A4',
                'paper_stock' => '70gsm',
                'print_sides' => 'simplex',
                'finishing_type' => 'loose',
                'copies_count' => 1,
            ],
        ]);

        // Pay 50% DP and verify
        $dpAmount = $order->minimumUpfrontAmount();
        $dpPayment = $paymentService->submitProof($order, $this->customer, Order::PAYMENT_OPTION_DOWNPAYMENT_50, 'gcash', $dpAmount, 'GCASH-SPLIT-50');
        $paymentService->verifyPayment($dpPayment, $this->owner);

        $order->refresh();
        $thesisItem = $order->items->firstWhere('service_key', 'thesis_binding');
        $docItem = $order->items->firstWhere('service_key', 'document_printing');
        $this->assertInstanceOf(OrderItem::class, $thesisItem);
        $this->assertInstanceOf(OrderItem::class, $docItem);

        // Move Thesis item -> Printing -> Binding -> QC -> Ready (Shelf BIN-A3) while Doc item stays in queue
        $queueService->startPrintingItem($thesisItem, $this->staff);
        $queueService->advanceItemStage($thesisItem->fresh(), $this->staff);
        $queueService->advanceItemStage($thesisItem->fresh(), $this->staff);

        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('openItemQaPassModal', $thesisItem->id)
            ->set('qaPassShelfBin', 'BIN-A3')
            ->call('confirmItemQaPass');

        $order->refresh();
        $thesisItem->refresh();
        $this->assertSame(Order::STATUS_PARTIALLY_READY, $order->order_status);
        $this->assertSame('BIN-A3', $thesisItem->shelf_bin_code);
        $this->assertNotNull($thesisItem->ready_at);

        // Customer portal shows Partially Ready and Shelf/Bin tag
        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡customer-dashboard')
            ->set('active_tab', 'orders')
            ->assertSee('Partially ready')
            ->assertSee('BIN-A3');

        // Early split pickup of Item 1 allowed while Item 2 is still unfinished
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('claimOrderItem', $thesisItem->id);

        $this->assertTrue($thesisItem->fresh()->isClaimed());
        $this->assertSame(Order::STATUS_PARTIALLY_READY, $order->fresh()->order_status);

        // Finish Item 2 -> Printing -> QC -> Ready (BIN-B1)
        $queueService->startPrintingItem($docItem, $this->staff);
        $queueService->advanceItemStage($docItem->fresh(), $this->staff);
        $queueService->passQualityCheck($docItem->fresh(), $this->staff, 'BIN-B1');

        // Claiming FINAL item blocked while balance_due > 0
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('claimOrderItem', $docItem->id);

        $docItem->refresh();
        $order->refresh();
        $this->assertFalse($docItem->isClaimed());
        $this->assertNotSame(Order::STATUS_COMPLETED, $order->order_status);

        // Settle remaining 50% balance, then claim Item 2 -> completes parent order with completed_at
        $paymentService->recordOfflineCounterPayment($order, $this->owner, (float) $order->balance_due);
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('claimOrderItem', $docItem->id);

        $docItem->refresh();
        $order->refresh();
        $this->assertTrue($docItem->isClaimed());
        $this->assertSame(Order::STATUS_COMPLETED, $order->order_status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_direct_browser_print_inline_preview_rip_hide_and_dual_qa_rework_spoilage(): void
    {
        Storage::fake('local');
        $checkout = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);
        $queueService = app(ProductionQueueService::class);

        $standardPrinter = Printer::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Office Laser Jet',
            'supported_sizes' => ['A4', 'Letter'],
            'status' => Printer::STATUS_ONLINE,
            'is_wide_format' => false,
        ]);

        $widePrinter = Printer::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Wide Format Plotter',
            'supported_sizes' => ['A4', 'Legal'],
            'status' => Printer::STATUS_ONLINE,
            'is_wide_format' => true,
        ]);

        $pdf = UploadedFile::fake()->create('thesis-print.pdf', 120, 'application/pdf');

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
            'document_file' => $pdf,
            'custom_fields_data' => ['Thesis Title' => 'Direct Print', 'Author' => 'Print User'],
        ]]);
        $paymentService->recordOfflineCounterPayment($order, $this->owner, (float) $order->total_amount);

        $item = $order->items()->firstOrFail();
        $initialTotal = (float) $order->total_amount;
        $initialItemTotal = (float) $item->total_price;

        // Assign standard printer: job ticket hides RIP Export and serves Direct Print Preview
        $queueService->assignItem($item, $this->staff, $standardPrinter->id, $this->staff->id);

        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('viewJobTicket', $order->id)
            ->assertSee('Direct Print Preview')
            ->assertDontSee('RIP Export');

        // Inline preview route authorization & headers
        $this->actingAs($this->customer)
            ->get(route('orders.file.preview', ['order' => $order->id, 'item' => $item->id]))
            ->assertForbidden();

        $this->actingAs($this->otherOwner)
            ->get(route('orders.file.preview', ['order' => $order->id, 'item' => $item->id]))
            ->assertForbidden();

        $previewResponse = $this->actingAs($this->staff)
            ->get(route('orders.file.preview', ['order' => $order->id, 'item' => $item->id]))
            ->assertOk();

        $this->assertStringStartsWith('inline', (string) $previewResponse->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $previewResponse->headers->get('X-Content-Type-Options'));

        // Assign wide-format printer: RIP Export becomes available
        $queueService->assignItem($item->fresh(), $this->staff, $widePrinter->id, $this->staff->id);

        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('viewJobTicket', $order->id)
            ->assertSee('RIP Export');

        // Start printing -> Binding -> QC, then FAIL QC with dual Paper + Ink rework spoilage (Edge Case R2)
        $queueService->startPrintingItem($item->fresh(), $this->staff);
        $queueService->advanceItemStage($item->fresh(), $this->staff);
        $queueService->advanceItemStage($item->fresh(), $this->staff);

        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->call('openItemQaFailModal', $item->id)
            ->set('qaFailReason', 'Fuser smudge across chapter 2')
            ->set('qaFailSpoiledSheets', 12.0)
            ->set('qaFailPaperItemId', $this->paperItem->id)
            ->set('qaFailInkItemId', $this->inkItem->id)
            ->set('qaFailInkQty', 2.5)
            ->call('confirmItemQaFail');

        $item->refresh();
        $order->refresh();

        $this->assertSame(OrderItem::STAGE_PRINTING, $item->production_stage);
        $this->assertEqualsWithDelta($initialItemTotal, (float) $item->total_price, 0.01);
        $this->assertEqualsWithDelta($initialTotal, (float) $order->total_amount, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $order->balance_due, 0.01);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->paperItem->id,
            'order_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_REWORK_SPOILAGE,
            'quantity' => -12.0,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->inkItem->id,
            'order_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_REWORK_SPOILAGE,
            'quantity' => -2.5,
        ]);
    }
}
