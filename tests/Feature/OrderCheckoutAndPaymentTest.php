<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use App\Services\InventoryDeductionService;
use App\Services\OrderCheckoutService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCheckoutAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $otherOwner;

    protected User $customer;

    protected User $otherCustomer;

    protected PrintShop $shop;

    protected PrintShop $otherShop;

    protected ThesisBindingConfig $thesisConfig;

    protected DocumentPrintingConfig $docConfig;

    protected InventoryItem $paperItem;

    protected InventoryItem $chipboardItem;

    protected InventoryItem $maroonCoverItem;

    protected InventoryItem $blueCoverItem;

    protected InventoryItem $glueItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->otherOwner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->otherCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'PressQueue Central Bindery',
            'approval_status' => PrintShop::STATUS_APPROVED,
            'is_setup_completed' => true,
            'address' => '123 University Ave',
            'contact' => '09171234567',
        ]);
        $this->shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $this->shop->services()->create(['service_key' => 'document_printing', 'is_active' => true]);

        $this->otherShop = PrintShop::create([
            'user_id' => $this->otherOwner->id,
            'name' => 'Rival Prints Co.',
            'approval_status' => PrintShop::STATUS_APPROVED,
            'is_setup_completed' => true,
        ]);
        $this->otherShop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $this->otherShop->services()->create(['service_key' => 'document_printing', 'is_active' => true]);

        $this->thesisConfig = ThesisBindingConfig::create([
            'print_shop_id' => $this->shop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'allow_customer_supplied_paper' => true,
            'hardbound_cover_only_price' => 300.00,
            'page_price_bw' => 1.50,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Blue', 'Black'],
            'foil_colors' => ['Gold', 'Silver'],
            'paper_sizes' => ['A4', 'Letter (Short)'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'max_binding_sheets' => 200,
            'require_pdf_upload' => false,
            'custom_cover_fields' => ['Thesis Title', 'Author'],
        ]);

        ThesisBindingConfig::create([
            'print_shop_id' => $this->otherShop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'page_price_bw' => 1.50,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Black'],
            'foil_colors' => ['Gold'],
            'paper_sizes' => ['A4'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'max_binding_sheets' => 250,
            'require_pdf_upload' => false,
            'custom_cover_fields' => ['Thesis Title'],
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
            'name' => 'A4 Bond Paper 70gsm',
            'sku' => 'PAP-A4-70',
            'category' => 'Paper',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'all',
            'stock_qty' => 1000.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 100.0,
            'unit_cost' => 0.50,
        ]);

        $this->chipboardItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Chipboard Heavy Duty 2mm',
            'sku' => 'COV-CHP-2MM',
            'category' => 'Cover',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'thesis_binding',
            'stock_qty' => 20.0,
            'reserved_qty' => 0.0,
            'unit' => 'pcs',
            'reorder_level' => 5.0,
            'unit_cost' => 15.00,
        ]);

        $this->maroonCoverItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Leatherette Cover Maroon',
            'sku' => 'COV-LTH-MAR',
            'category' => 'Cover',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'thesis_binding',
            'stock_qty' => 20.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 5.0,
            'unit_cost' => 20.00,
        ]);

        $this->blueCoverItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Leatherette Cover Blue',
            'sku' => 'COV-LTH-BLU',
            'category' => 'Cover',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'thesis_binding',
            'stock_qty' => 50.0,
            'reserved_qty' => 0.0,
            'unit' => 'sheets',
            'reorder_level' => 5.0,
            'unit_cost' => 20.00,
        ]);

        $this->glueItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Hot Melt Binding Glue',
            'sku' => 'ADH-GLU-01',
            'category' => 'Adhesive',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'service_tag' => 'thesis_binding',
            'stock_qty' => 20.0,
            'reserved_qty' => 0.0,
            'unit' => 'kg',
            'reorder_level' => 2.0,
            'unit_cost' => 100.00,
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

    public function test_mixed_multi_service_cart_places_single_order_with_tracking_code_and_reserves_stock(): void
    {
        $component = Livewire::actingAs($this->customer)
            ->test('pages::customer.shop-cart', ['shop' => $this->shop->id])
            ->set('thesis_bw_pages', 50)
            ->set('thesis_color_pages', 10)
            ->set('thesis_print_sides', 'simplex')
            ->set('thesis_copies_count', 1)
            ->set('thesis_custom_fields_data', [
                'Thesis Title' => 'Automated Campus Queue',
                'Author' => 'Juan Dela Cruz',
            ])
            ->call('addItemToCart')
            ->assertHasNoErrors()
            ->call('selectDraftService', 'document_printing')
            ->set('doc_total_pages', 20)
            ->set('doc_print_sides', 'duplex')
            ->set('doc_copies_count', 2)
            ->call('addItemToCart')
            ->assertHasNoErrors()
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        $orderId = $component->get('created_order_id');
        $order = Order::with('items')->findOrFail($orderId);

        $this->assertNull($order->service_key);
        $this->assertTrue($order->isMultiService());
        $this->assertStringStartsWith('TRK-', (string) $order->tracking_code);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->order_status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertEqualsWithDelta((float) $order->total_amount, (float) $order->balance_due, 0.01);
        $this->assertCount(2, $order->items);
        $this->assertContains('thesis_binding', $order->items->pluck('service_key')->all());
        $this->assertContains('document_printing', $order->items->pluck('service_key')->all());

        // Thesis (60 sheets) + Doc (20 duplex pages = 10 sheets/copy * 2 copies = 20 sheets) = 80 reserved sheets
        $this->paperItem->refresh();
        $this->maroonCoverItem->refresh();
        $this->assertEqualsWithDelta(80.0, (float) $this->paperItem->reserved_qty, 0.01);
        $this->assertEqualsWithDelta(1.0, (float) $this->maroonCoverItem->reserved_qty, 0.01);
        $this->assertSame(
            2,
            StockMovement::where('movement_type', StockMovement::TYPE_RESERVATION)
                ->where('inventory_item_id', $this->paperItem->id)
                ->count()
        );
    }

    public function test_duplex_physical_sheets_and_max_binding_sheets_validation(): void
    {
        $this->thesisConfig->update(['max_binding_sheets' => 150]);

        Livewire::actingAs($this->customer)
            ->test('pages::customer.shop-cart', ['shop' => $this->shop->id])
            ->set('thesis_bw_pages', 200)
            ->set('thesis_color_pages', 0)
            ->set('thesis_print_sides', 'simplex')
            ->set('thesis_custom_fields_data', [
                'Thesis Title' => 'Thick Manuscript',
                'Author' => 'Maria Clara',
            ])
            ->call('addItemToCart')
            ->assertHasErrors(['binding_limit'])
            ->set('thesis_print_sides', 'duplex')
            ->call('addItemToCart')
            ->assertHasNoErrors()
            ->set('thesis_bw_pages', 302)
            ->set('thesis_print_sides', 'duplex')
            ->call('addItemToCart')
            ->assertHasErrors(['binding_limit']);
    }

    public function test_platform_max_upload_mb_enforced_on_checkout_uploads(): void
    {
        Storage::fake('local');
        PlatformSetting::current()->update(['max_upload_mb' => 1]);

        $oversized = UploadedFile::fake()->create('manuscript.pdf', 2048, 'application/pdf');
        $valid = UploadedFile::fake()->create('manuscript.pdf', 512, 'application/pdf');

        Livewire::actingAs($this->customer)
            ->test('pages::customer.shop-cart', ['shop' => $this->shop->id])
            ->set('thesis_custom_fields_data', [
                'Thesis Title' => 'File Size Check',
                'Author' => 'Ana Santos',
            ])
            ->set('draft_document_file', $oversized)
            ->call('addItemToCart')
            ->assertHasErrors(['draft_document_file'])
            ->set('draft_document_file', $valid)
            ->call('addItemToCart')
            ->assertHasNoErrors();
    }

    public function test_insufficient_stock_rolls_back_order_cleans_upload_and_displays_fallback_suggestions(): void
    {
        Storage::fake('local');
        $this->maroonCoverItem->update(['stock_qty' => 1.0, 'reserved_qty' => 0.0]);
        $checkoutService = app(OrderCheckoutService::class);

        $file = UploadedFile::fake()->create('thesis-rollback.pdf', 256, 'application/pdf');

        try {
            $checkoutService->placeOrder(
                shop: $this->shop,
                customer: $this->customer,
                cartItems: [[
                    'service_key' => 'thesis_binding',
                    'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                    'binding_type' => 'hardbound',
                    'bw_pages' => 40,
                    'color_pages' => 10,
                    'paper_size' => 'A4',
                    'print_sides' => 'simplex',
                    'cover_color' => 'Maroon',
                    'foil_color' => 'Gold',
                    'copies_count' => 2,
                    'document_file' => $file,
                    'custom_fields_data' => ['Thesis Title' => 'Test', 'Author' => 'Tester'],
                ]],
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            $this->assertSame($this->maroonCoverItem->id, $e->inventoryItemId);
            $this->assertNotEmpty($e->fallbackSuggestions);
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0.0, (float) $this->paperItem->fresh()->reserved_qty);
        $this->assertSame([], Storage::disk('local')->allFiles('orders/manuscripts'));

        Livewire::actingAs($this->customer)
            ->test('pages::customer.shop-cart', ['shop' => $this->shop->id])
            ->set('thesis_bw_pages', 40)
            ->set('thesis_color_pages', 10)
            ->set('thesis_copies_count', 2)
            ->set('thesis_custom_fields_data', [
                'Thesis Title' => 'Shortage Test',
                'Author' => 'Tester',
            ])
            ->call('addItemToCart')
            ->assertHasNoErrors()
            ->call('placeOrder')
            ->assertSet('step', 1)
            ->assertSee('Insufficient Material Stock')
            ->assertSee('Leatherette Cover Blue');
    }

    public function test_inventory_commit_on_print_and_idempotent_cancellation_release(): void
    {
        $checkoutService = app(OrderCheckoutService::class);
        $deductionService = app(InventoryDeductionService::class);
        $paymentService = app(PaymentService::class);

        $order = $checkoutService->placeOrder(
            shop: $this->shop,
            customer: $this->customer,
            cartItems: [[
                'service_key' => 'thesis_binding',
                'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                'binding_type' => 'hardbound',
                'bw_pages' => 40,
                'color_pages' => 10,
                'paper_size' => 'A4',
                'print_sides' => 'simplex',
                'cover_color' => 'Maroon',
                'foil_color' => 'Gold',
                'copies_count' => 1,
                'custom_fields_data' => ['Thesis Title' => 'Commit Test', 'Author' => 'Author 1'],
            ]],
        );

        $this->assertEqualsWithDelta(50.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);

        $deductions = $deductionService->commitReservationOnPrint($order, $this->owner);
        $this->assertNotEmpty($deductions);

        $this->paperItem->refresh();
        $this->maroonCoverItem->refresh();
        $this->assertEqualsWithDelta(950.0, (float) $this->paperItem->stock_qty, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $this->paperItem->reserved_qty, 0.01);
        $this->assertEqualsWithDelta(19.0, (float) $this->maroonCoverItem->stock_qty, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $this->maroonCoverItem->reserved_qty, 0.01);
        $this->assertSame([], $deductionService->releaseReservation($order, $this->owner));

        $order2 = $checkoutService->placeOrder(
            shop: $this->shop,
            customer: $this->customer,
            cartItems: [[
                'service_key' => 'thesis_binding',
                'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                'binding_type' => 'hardbound',
                'bw_pages' => 30,
                'color_pages' => 0,
                'paper_size' => 'A4',
                'print_sides' => 'simplex',
                'cover_color' => 'Maroon',
                'foil_color' => 'Gold',
                'copies_count' => 1,
                'custom_fields_data' => ['Thesis Title' => 'Cancel Test', 'Author' => 'Author 2'],
            ]],
        );

        $this->assertEqualsWithDelta(30.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);

        $cancelled = $paymentService->cancelUnpaidOrder($order2, $this->customer, 'Changed mind');
        $this->assertSame(Order::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertEqualsWithDelta(0.0, (float) $this->paperItem->fresh()->reserved_qty, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $this->maroonCoverItem->fresh()->reserved_qty, 0.01);
        $this->assertSame([], $deductionService->releaseReservation($cancelled, $this->customer));
    }

    public function test_two_step_downpayment_validation_duplicate_reference_guard_and_rejection_retry(): void
    {
        $checkoutService = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        $cart = [[
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
            'custom_fields_data' => ['Thesis Title' => 'Payment Test', 'Author' => 'Pay Tester'],
        ]];

        $order = $checkoutService->placeOrder($this->shop, $this->customer, $cart);
        $expectedMin = $order->minimumUpfrontAmount();

        // Reject under-50% DP via Livewire dashboard modal
        Livewire::actingAs($this->customer)
            ->test('pages::customer.customer-dashboard')
            ->call('openPaymentModal', $order->id)
            ->call('selectPaymentOption', Order::PAYMENT_OPTION_DOWNPAYMENT_50)
            ->set('payment_amount', max(1.0, $expectedMin - 50.0))
            ->set('payment_reference_no', 'GCASH-UNDER-01')
            ->call('submitPaymentProof')
            ->assertHasErrors(['amount'])
            ->set('payment_amount', $expectedMin)
            ->set('payment_reference_no', 'gcash-unique-01')
            ->call('submitPaymentProof')
            ->assertHasNoErrors();

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('GCASH-UNIQUE-01', $payment->reference_no);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        // Same reference rejected for another order in the SAME shop
        $secondOrder = $checkoutService->placeOrder($this->shop, $this->customer, $cart);
        Livewire::actingAs($this->customer)
            ->test('pages::customer.customer-dashboard')
            ->call('openPaymentModal', $secondOrder->id)
            ->set('payment_reference_no', 'GCASH-UNIQUE-01')
            ->call('submitPaymentProof')
            ->assertHasErrors(['reference_no']);

        // Allowed in ANOTHER print shop
        $otherShopOrder = $checkoutService->placeOrder($this->otherShop, $this->customer, $cart);
        $otherShopPayment = $paymentService->submitProof(
            order: $otherShopOrder,
            customer: $this->customer,
            paymentOption: Order::PAYMENT_OPTION_FULL_100,
            paymentMethod: 'gcash',
            amount: (float) $otherShopOrder->total_amount,
            referenceNo: 'GCASH-UNIQUE-01',
        );
        $this->assertSame($this->otherShop->id, $otherShopPayment->print_shop_id);

        // Owner rejects first payment; stock reservation remains alive for customer retry
        $paymentService->rejectPayment($payment, $this->owner, 'Blurry reference image');
        $order->refresh();
        $this->assertSame(Order::PAYMENT_REJECTED, $order->payment_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->order_status);
        $this->assertGreaterThan(0.0, (float) $this->paperItem->fresh()->reserved_qty);

        // Customer retries via dashboard
        Livewire::actingAs($this->customer)
            ->test('pages::customer.customer-dashboard')
            ->call('openPaymentModal', $order->id)
            ->call('selectPaymentOption', Order::PAYMENT_OPTION_DOWNPAYMENT_50)
            ->set('payment_amount', $expectedMin)
            ->set('payment_reference_no', 'GCASH-RETRY-02')
            ->call('submitPaymentProof')
            ->assertHasNoErrors();

        $this->assertSame(Order::PAYMENT_PENDING_VERIFICATION, $order->fresh()->payment_status);
    }

    public function test_owner_payment_audit_queue_aging_flag_and_counter_settlement_with_tenant_isolation(): void
    {
        $checkoutService = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        $order = $checkoutService->placeOrder(
            shop: $this->shop,
            customer: $this->customer,
            cartItems: [[
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
                'custom_fields_data' => ['Thesis Title' => 'Audit Queue', 'Author' => 'Owner Audit'],
            ]],
        );

        $halfAmount = $order->minimumUpfrontAmount();
        $remainingAmount = round((float) $order->total_amount - $halfAmount, 2);

        $payment = $paymentService->submitProof(
            order: $order,
            customer: $this->customer,
            paymentOption: Order::PAYMENT_OPTION_DOWNPAYMENT_50,
            paymentMethod: 'gcash',
            amount: $halfAmount,
            referenceNo: 'GCASH-AGING-99',
        );
        $payment->forceFill(['submitted_at' => now()->subHours(3)])->save();

        // Cross-tenant owner cannot approve another shop's payment
        try {
            Livewire::actingAs($this->otherOwner)
                ->test('pages::owner.analytics-hub')
                ->call('approvePayment', $payment->id);
            $this->fail('Expected ModelNotFoundException for cross-tenant payment approval.');
        } catch (ModelNotFoundException) {
            $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        }

        // Owning merchant sees aging badge, verifies 50% DP, then settles remaining balance at counter
        Livewire::actingAs($this->owner)
            ->test('pages::owner.analytics-hub')
            ->set('active_tab', 'payments')
            ->assertSee('Aging (>2h)')
            ->call('approvePayment', $payment->id)
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::PAYMENT_PARTIALLY_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_IN_QUEUE, $order->order_status);
        $this->assertEqualsWithDelta($halfAmount, (float) $order->amount_paid, 0.01);
        $this->assertEqualsWithDelta($remainingAmount, (float) $order->balance_due, 0.01);

        Livewire::actingAs($this->owner)
            ->test('pages::owner.analytics-hub')
            ->set('active_tab', 'payments')
            ->call('openCounterSettlementModal', $order->id)
            ->set('counter_amount', $remainingAmount)
            ->set('counter_method', 'cash_counter')
            ->call('recordCounterSettlement')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::PAYMENT_VERIFIED_PAID, $order->payment_status);
        $this->assertEqualsWithDelta((float) $order->total_amount, (float) $order->amount_paid, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $order->balance_due, 0.01);
    }

    public function test_official_receipt_html_and_pdf_with_authorization(): void
    {
        $checkoutService = app(OrderCheckoutService::class);
        $paymentService = app(PaymentService::class);

        $order = $checkoutService->placeOrder(
            shop: $this->shop,
            customer: $this->customer,
            cartItems: [[
                'service_key' => 'thesis_binding',
                'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
                'binding_type' => 'hardbound',
                'bw_pages' => 80,
                'color_pages' => 10,
                'paper_size' => 'A4',
                'print_sides' => 'simplex',
                'cover_color' => 'Maroon',
                'foil_color' => 'Gold',
                'copies_count' => 1,
                'custom_fields_data' => ['Thesis Title' => 'Receipt Spec', 'Author' => 'Receipt Tester'],
            ]],
        );

        $paymentService->recordOfflineCounterPayment($order, $this->owner, (float) $order->total_amount, 'cash_counter', 'OR-CASH-1001');
        $order->refresh();

        $this->actingAs($this->customer)
            ->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('OR-'.$order->order_number)
            ->assertSee((string) $order->tracking_code)
            ->assertSee('OR-CASH-1001')
            ->assertSee($this->shop->name);

        $this->actingAs($this->owner)
            ->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('OR-'.$order->order_number);

        $pdfResponse = $this->actingAs($this->customer)
            ->get(route('orders.receipt', ['order' => $order, 'format' => 'pdf']))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $pdfResponse->getContent());

        $this->actingAs($this->otherCustomer)
            ->get(route('orders.receipt', $order))
            ->assertForbidden();

        $this->actingAs($this->otherOwner)
            ->get(route('orders.receipt', $order))
            ->assertForbidden();
    }
}
