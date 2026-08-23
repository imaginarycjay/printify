<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAnalyticsHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected PrintShop $shop;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'Apex Print Hub',
            'slug' => 'apex-print-hub',
            'is_setup_completed' => true,
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Mary Rose Enoc',
            'email' => 'mary@example.test',
            'role' => User::ROLE_CUSTOMER,
        ]);

        // Seed basic inventory items for BOM calculations
        InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Short Bond Paper 80gsm',
            'category' => 'Paper Stock',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 500,
            'unit' => 'sheets',
            'unit_cost' => 0.50,
            'reorder_level' => 100,
        ]);

        InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Standard Chipboard #20',
            'category' => 'Binding Board',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 50,
            'unit' => 'pcs',
            'unit_cost' => 25.00,
            'reorder_level' => 10,
        ]);

        InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Maroon Leatherette Paper',
            'category' => 'Cover Material',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 50,
            'unit' => 'meters',
            'unit_cost' => 35.00,
            'reorder_level' => 10,
        ]);

        InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Gold Foil Stamping Roll',
            'category' => 'Foil Stock',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 20,
            'unit' => 'rolls',
            'unit_cost' => 5.00,
            'reorder_level' => 5,
        ]);
    }

    public function test_owner_can_access_analytics_hub_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.analytics-hub'));

        $response->assertOk();
        $response->assertSee('Sales & Financial Analytics Hub');
        $response->assertSee('Live Cashflow');
    }

    public function test_sales_analytics_service_calculates_gross_revenue_and_margins(): void
    {
        // 1. Create a paid Full Package Hardbound Order (100 pages, ₱500)
        $order1 = Order::create([
            'order_number' => 'ORD-2026-0001',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_READY_FOR_PICKUP,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 500.00,
            'rush_fee_amount' => 0.00,
            'total_amount' => 500.00,
            'is_rush' => false,
            'production_stage' => Order::STAGE_READY_FOR_PICKUP,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'is_paper_received' => true,
            'estimated_spine_thickness_mm' => 10.0,
            'bw_pages_count' => 80,
            'color_pages_count' => 20,
            'total_pages_count' => 100,
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'paper_size' => 'Letter',
            'copies_count' => 1,
            'unit_price' => 500.00,
            'total_price' => 500.00,
        ]);

        // 2. Create a paid Cover-Only Hardbound Order (0 pages printed by shop, ₱350 + ₱100 rush)
        $order2 = Order::create([
            'order_number' => 'ORD-2026-0002',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 350.00,
            'rush_fee_amount' => 100.00,
            'total_amount' => 450.00,
            'is_rush' => true,
            'production_stage' => Order::STAGE_COMPLETED,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => OrderItem::FULFILLMENT_COVER_ONLY,
            'is_paper_received' => true,
            'estimated_spine_thickness_mm' => 12.0,
            'bw_pages_count' => 0,
            'color_pages_count' => 0,
            'total_pages_count' => 0,
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'paper_size' => 'Letter',
            'copies_count' => 1,
            'unit_price' => 450.00,
            'total_price' => 450.00,
        ]);

        $service = app(SalesAnalyticsService::class);
        $metrics = $service->getMetrics($this->shop, 'all_time');

        // Total Gross Revenue: 500 + 450 = 950
        $this->assertEquals(950.00, $metrics['gross_revenue']);
        $this->assertEquals(2, $metrics['paid_orders_count']);
        $this->assertEquals(475.00, $metrics['average_order_value']);
        $this->assertEquals(100.00, $metrics['rush_revenue']);

        // Material Cost Calculation:
        // Order 1 (Full package): 100 pages * 0.50 (50) + 25 board + 35 leather + 5 foil = 115.00
        // Order 2 (Cover only): 0 pages * 0.50 (0) + 25 board + 35 leather + 5 foil = 65.00
        // Total Material Cost = 115 + 65 = 180.00
        $this->assertEquals(180.00, $metrics['raw_material_cost']);

        // Net Profit = 950 - 180 = 770.00
        $this->assertEquals(770.00, $metrics['net_profit']);
        $this->assertGreaterThan(75.0, $metrics['profit_margin_pct']);
    }

    public function test_spoilage_costs_reduce_net_profit(): void
    {
        $leatherItem = InventoryItem::where('print_shop_id', $this->shop->id)
            ->where('category', 'Cover Material')
            ->firstOrFail();

        // Log 2 meters of ruined leatherette (2 * 35.00 = 70.00 loss)
        StockMovement::create([
            'inventory_item_id' => $leatherItem->id,
            'movement_type' => StockMovement::TYPE_SPOILAGE_WASTE,
            'quantity' => -2.0,
            'previous_stock' => 50.0,
            'resulting_stock' => 48.0,
            'reference_note' => 'Ruined in hot foil heat press misalignment',
            'logged_by' => $this->owner->id,
        ]);

        $service = app(SalesAnalyticsService::class);
        $metrics = $service->getMetrics($this->shop, 'all_time');

        $this->assertEquals(70.00, $metrics['spoilage_loss']);
    }

    public function test_product_mix_calculates_fulfillment_ratios_and_popular_colors(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-MIX-1',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 350.00,
            'rush_fee_amount' => 0.00,
            'total_amount' => 350.00,
            'is_rush' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => OrderItem::FULFILLMENT_COVER_ONLY,
            'is_paper_received' => true,
            'estimated_spine_thickness_mm' => 10.0,
            'bw_pages_count' => 0,
            'color_pages_count' => 0,
            'total_pages_count' => 0,
            'cover_color' => 'Navy Blue',
            'foil_color' => 'Silver',
            'paper_size' => 'Letter',
            'copies_count' => 1,
            'unit_price' => 350.00,
            'total_price' => 350.00,
        ]);

        $service = app(SalesAnalyticsService::class);
        $mix = $service->getProductMix($this->shop, 'all_time');

        $this->assertEquals(1, $mix['cover_only_count']);
        $this->assertEquals(1, $mix['hardbound_count']);
        $this->assertNotEmpty($mix['top_colors']);
        $this->assertEquals('Navy Blue', $mix['top_colors'][0]['color']);
    }

    public function test_owner_can_export_csv_sales_ledger(): void
    {
        Order::create([
            'order_number' => 'ORD-2026-EXPORT',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 400.00,
            'rush_fee_amount' => 0.00,
            'total_amount' => 400.00,
            'is_rush' => false,
        ]);

        $service = app(SalesAnalyticsService::class);
        $csv = $service->generateCsvExport($this->shop, 'all_time', 'all');

        $this->assertStringContainsString('ORD-2026-EXPORT', $csv);
        $this->assertStringContainsString('Mary Rose Enoc', $csv);
        $this->assertStringContainsString('400.00', $csv);
    }
}
