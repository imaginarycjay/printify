<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\ServiceBom;
use App\Models\ShopService;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedServiceBomTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected User $customer;

    protected PrintShop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);
    }

    public function test_shop_service_stores_dynamic_json_settings(): void
    {
        $service = ShopService::create([
            'print_shop_id' => $this->shop->id,
            'service_key' => 'tarpaulin_printing',
            'name' => 'Tarpaulin Printing',
            'is_active' => true,
            'display_order' => 3,
            'settings' => [
                'base_rate_sqft' => 15.00,
                'eyelet_price' => 2.00,
                'turnaround_hours' => 24,
            ],
        ]);

        $this->assertEquals(15.00, $service->getSetting('base_rate_sqft'));
        $this->assertEquals(2.00, $service->getSetting('eyelet_price'));

        $service->updateSetting('eyelet_price', 3.50);
        $this->assertEquals(3.50, $service->fresh()->getSetting('eyelet_price'));
    }

    public function test_unified_service_bom_deducts_inventory_dynamically(): void
    {
        // 1. Create inventory items
        $vinylRoll = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => '13oz Heavy Duty Vinyl Roll',
            'category' => 'Media',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 100.0,
            'unit' => 'rolls',
            'reorder_level' => 10.0,
        ]);

        $eyeletPack = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Brass Eyelets Grommets',
            'category' => 'Hardware',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 500.0,
            'unit' => 'pcs',
            'reorder_level' => 50.0,
        ]);

        // 2. Configure dynamic BOM recipes without altering database tables
        ServiceBom::create([
            'print_shop_id' => $this->shop->id,
            'service_key' => 'tarpaulin_printing',
            'inventory_item_id' => $vinylRoll->id,
            'component_name' => 'Vinyl Substrate',
            'usage_type' => ServiceBom::USAGE_PER_COPY,
            'usage_qty' => 1.0,
            'unit' => 'rolls',
        ]);

        ServiceBom::create([
            'print_shop_id' => $this->shop->id,
            'service_key' => 'tarpaulin_printing',
            'inventory_item_id' => $eyeletPack->id,
            'component_name' => 'Perimeter Grommets',
            'usage_type' => ServiceBom::USAGE_PER_COPY,
            'usage_qty' => 4.0,
            'unit' => 'pcs',
        ]);

        // 3. Place order
        $order = Order::create([
            'order_number' => 'ORD-20260909-TARP',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'tarpaulin_printing',
            'order_status' => Order::STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'production_stage' => Order::STAGE_PRINTING,
            'total_amount' => 450.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'paper_size' => '3x4 ft',
            'copies_count' => 2,
            'unit_price' => 225.00,
            'total_price' => 450.00,
            'specifications' => [
                'dimensions' => '3x4',
                'grommets' => true,
            ],
        ]);

        // 4. Trigger deduction
        $deductionService = app(InventoryDeductionService::class);
        $deductions = $deductionService->deductForOrder($order, $this->staff);

        $this->assertCount(2, $deductions);

        // 100 - (1 roll * 2 copies) = 98
        $this->assertEquals(98.0, (float) $vinylRoll->fresh()->stock_qty);

        // 500 - (4 eyelets * 2 copies) = 492
        $this->assertEquals(492.0, (float) $eyeletPack->fresh()->stock_qty);

        // Verify StockMovement audit trail
        $this->assertEquals(2, StockMovement::where('movement_type', StockMovement::TYPE_PRODUCTION_DEDUCTION)->count());
    }
}
