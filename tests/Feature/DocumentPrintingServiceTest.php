<?php

namespace Tests\Feature;

use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryDeductionService;
use App\Services\SalesAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentPrintingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected User $customer;

    protected PrintShop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => User::ROLE_BUSINESS_OWNER,
            'email_verified_at' => now(),
        ]);

        $this->staff = User::factory()->create([
            'role' => User::ROLE_PRODUCTION_STAFF,
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'email_verified_at' => now(),
        ]);

        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'Speedy Prints Inc',
            'slug' => 'speedy-prints',
            'address' => '456 University Ave, Sampaloc, Manila',
            'phone' => '+63 917 123 4567',
            'is_onboarding_completed' => true,
        ]);
    }

    public function test_owner_can_access_document_printing_config_and_save_rates(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.document-printing'));
        $response->assertOk();
        $response->assertSee('Document Printing');

        Livewire::actingAs($this->owner)
            ->test('pages::owner.⚡document-printing')
            ->set('page_price_bw_a4', 2.00)
            ->set('page_price_color_a4', 6.00)
            ->set('duplex_discount_percent', 15)
            ->set('ring_bind_base_price', 50.00)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $config = DocumentPrintingConfig::where('print_shop_id', $this->shop->id)->first();
        $this->assertNotNull($config);
        $this->assertEquals(2.00, $config->page_price_bw_a4);
        $this->assertEquals(6.00, $config->page_price_color_a4);
        $this->assertEquals(15, $config->duplex_discount_percent);
        $this->assertEquals(50.00, $config->ring_bind_base_price);
    }

    public function test_customer_can_place_document_printing_order_with_duplex_and_ring_binding(): void
    {
        DocumentPrintingConfig::create([
            'print_shop_id' => $this->shop->id,
            'page_price_bw_a4' => 1.50,
            'page_price_color_a4' => 5.00,
            'duplex_discount_percent' => 10,
            'paper_stock_80gsm_price' => 0.50,
            'ring_bind_base_price' => 45.00,
            'rush_fee_amount' => 50.00,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.order-document'));
        $response->assertOk();
        $response->assertSee('Order Document Printing');

        // Total 20 pages: 15 B&W, 5 Color, A4 80gsm, Duplex (10 physical sheets), Ring Binding (Blue)
        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡document-order-wizard')
            ->set('total_pages', 20)
            ->set('color_mode', 'mixed')
            ->set('custom_color_pages', '1, 5, 10, 15, 20')
            ->set('paper_size', 'A4')
            ->set('paper_stock', '80gsm')
            ->set('print_sides', 'duplex')
            ->set('finishing_type', 'ring_bind')
            ->set('ring_back_cover_color', 'Blue')
            ->set('copies_count', 2)
            ->set('is_rush', true)
            ->call('proceedToCheckout')
            ->assertSet('step', 2)
            ->set('customer_name', 'Maria Santos')
            ->set('customer_email', 'maria@student.ph')
            ->set('payment_method', 'gcash')
            ->set('payment_reference_no', 'GCASH-987654321')
            ->call('submitOrder')
            ->assertSet('step', 3)
            ->assertHasNoErrors();

        $order = Order::where('service_key', 'document_printing')->first();
        $this->assertNotNull($order);
        $this->assertEquals(Order::STATUS_IN_QUEUE, $order->order_status);
        $this->assertTrue($order->is_rush);

        $item = $order->items->first();
        $this->assertNotNull($item);
        $this->assertEquals('A4', $item->paper_size);
        $this->assertEquals(2, $item->copies_count);
        $this->assertEquals(20, $item->total_pages_count);
        $this->assertEquals(15, $item->bw_pages_count);
        $this->assertEquals(5, $item->color_pages_count);
        $this->assertTrue($item->isDuplex());
        $this->assertEquals('ring_bind', $item->getFinishingType());
        // 20 pages Duplex = 10 sheets * 2 copies = 20 total sheets
        $this->assertEquals(20, $item->getPhysicalSheetsCount());
    }

    public function test_staff_sees_document_printing_orders_and_category_filter(): void
    {
        $order = Order::create([
            'order_number' => 'DOC-2026-TEST01',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'document_printing',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 150.00,
            'rush_fee_amount' => 0.00,
            'total_amount' => 150.00,
            'is_rush' => false,
            'production_stage' => Order::STAGE_QUEUE,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'ring_bind',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'paper_size' => 'A4',
            'bw_pages_count' => 18,
            'color_pages_count' => 2,
            'total_pages_count' => 20,
            'copies_count' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00,
            'custom_fields_data' => [
                'service_key' => 'document_printing',
                'print_sides' => 'duplex',
                'finishing_type' => 'ring_bind',
                'paper_stock' => '80gsm',
            ],
        ]);

        // Staff visits dashboard
        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));
        $response->assertOk();
        $response->assertSee('DOC-2026-TEST01');
        $response->assertSee('Doc Print');
        $response->assertSee('Plastic Ring Binding');

        // Test filtering by document_printing category
        Livewire::actingAs($this->staff)
            ->test('pages::staff.⚡staff-dashboard')
            ->set('selectedService', 'document_printing')
            ->assertSee('DOC-2026-TEST01')
            ->set('selectedService', 'thesis_binding')
            ->assertDontSee('DOC-2026-TEST01');
    }

    public function test_advancing_document_order_triggers_exact_duplex_paper_and_ring_bom_deductions(): void
    {
        // 1. Create shop inventory items
        $a4Paper = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'A4 Bond Paper 80gsm',
            'sku' => 'PAP-A4-80',
            'category' => 'Paper Stock',
            'unit' => 'sheets',
            'stock_qty' => 500.0,
            'reorder_level' => 100.0,
            'unit_cost' => 0.60,
        ]);

        $ringComb = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Plastic Ring Comb 12mm',
            'sku' => 'RNG-12MM',
            'category' => 'Binding Materials',
            'unit' => 'pcs',
            'stock_qty' => 50.0,
            'reorder_level' => 10.0,
            'unit_cost' => 10.00,
        ]);

        $pvcAcetate = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Clear PVC Acetate Sheets',
            'sku' => 'PVC-ACT',
            'category' => 'Cover Stock',
            'unit' => 'sheets',
            'stock_qty' => 100.0,
            'reorder_level' => 20.0,
            'unit_cost' => 3.00,
        ]);

        $backBoard = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Morocco Back Board (Blue)',
            'sku' => 'BRD-MOR-BLU',
            'category' => 'Cover Stock',
            'unit' => 'sheets',
            'stock_qty' => 100.0,
            'reorder_level' => 20.0,
            'unit_cost' => 5.00,
        ]);

        // 2. Link config BOM
        DocumentPrintingConfig::create([
            'print_shop_id' => $this->shop->id,
            'auto_deduct_inventory' => true,
            'bom_a4_paper_item_id' => $a4Paper->id,
            'bom_ring_spine_item_id' => $ringComb->id,
            'bom_pvc_acetate_item_id' => $pvcAcetate->id,
            'bom_back_cover_item_id' => $backBoard->id,
        ]);

        // 3. Create a Document Order: 30 pages Duplex (= 15 sheets) * 2 copies = 30 sheets, 2 rings, 4 acetate, 2 boards
        $order = Order::create([
            'order_number' => 'DOC-2026-BOM01',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'document_printing',
            'order_status' => Order::STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 300.00,
            'total_amount' => 300.00,
            'production_stage' => Order::STAGE_QUALITY_CHECK,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'ring_bind',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'paper_size' => 'A4',
            'bw_pages_count' => 30,
            'color_pages_count' => 0,
            'total_pages_count' => 30,
            'copies_count' => 2,
            'unit_price' => 150.00,
            'total_price' => 300.00,
            'custom_fields_data' => [
                'service_key' => 'document_printing',
                'print_sides' => 'duplex',
                'finishing_type' => 'ring_bind',
            ],
        ]);

        // Execute BOM deduction
        $deductionService = app(InventoryDeductionService::class);
        $deductions = $deductionService->deductForOrder($order, $this->staff);

        $this->assertNotEmpty($deductions);

        // Verify A4 Paper: 500 - 30 = 470
        $this->assertEquals(470.0, (float) $a4Paper->fresh()->stock_qty);

        // Verify Ring Comb: 50 - 2 = 48
        $this->assertEquals(48.0, (float) $ringComb->fresh()->stock_qty);

        // Verify PVC Acetate: 100 - 4 = 96
        $this->assertEquals(96.0, (float) $pvcAcetate->fresh()->stock_qty);

        // Verify Back Board: 100 - 2 = 98
        $this->assertEquals(98.0, (float) $backBoard->fresh()->stock_qty);

        // Verify StockMovements logged
        $this->assertEquals(4, StockMovement::where('movement_type', StockMovement::TYPE_PRODUCTION_DEDUCTION)->count());
    }

    public function test_sales_analytics_tracks_document_printing_revenue_and_margins(): void
    {
        $order = Order::create([
            'order_number' => 'DOC-2026-SALES01',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'document_printing',
            'order_status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 200.00,
            'rush_fee_amount' => 50.00,
            'total_amount' => 250.00,
            'is_rush' => true,
            'production_stage' => Order::STAGE_COMPLETED,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'ring_bind',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'paper_size' => 'A4',
            'bw_pages_count' => 20,
            'color_pages_count' => 0,
            'total_pages_count' => 20,
            'copies_count' => 1,
            'unit_price' => 200.00,
            'total_price' => 250.00,
            'custom_fields_data' => [
                'service_key' => 'document_printing',
                'print_sides' => 'duplex',
                'finishing_type' => 'ring_bind',
            ],
        ]);

        $analytics = app(SalesAnalyticsService::class);
        $metrics = $analytics->getMetrics($this->shop, 'all_time', 'document_printing');

        $this->assertEquals(250.00, $metrics['gross_revenue']);
        $this->assertEquals(1, $metrics['paid_orders_count']);
        $this->assertEquals(50.00, $metrics['rush_revenue']);
        $this->assertGreaterThan(0.0, $metrics['net_profit']);
        $this->assertGreaterThan(0.0, $metrics['profit_margin_pct']);
    }
}
