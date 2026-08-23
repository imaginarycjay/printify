<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionStaffQueueTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected User $owner;

    protected User $customer;

    protected PrintShop $shop;

    protected ThesisBindingConfig $config;

    protected InventoryItem $paperItem;

    protected InventoryItem $leatheretteItem;

    protected InventoryItem $chipboardItem;

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

        $this->config = ThesisBindingConfig::create([
            'print_shop_id' => $this->shop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'allow_customer_supplied_paper' => true,
            'hardbound_cover_only_price' => 300.00,
            'page_price_bw' => 1.50,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Dark Blue', 'Black'],
            'foil_colors' => ['Gold', 'Silver'],
            'paper_sizes' => ['A4', 'Letter (Short)'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'require_pdf_upload' => true,
            'custom_cover_fields' => ['Thesis Title', 'Name of Researchers', 'Degree / Course'],
        ]);

        $this->paperItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'A4 80gsm Copier Paper',
            'sku' => 'PAP-A4-80G',
            'category' => 'Paper',
            'stock_qty' => 500,
            'unit' => 'sheets',
            'reorder_level' => 100,
        ]);

        $this->leatheretteItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => 'Maroon Leatherette Sheet',
            'sku' => 'COV-LTH-MRN',
            'category' => 'Raw Material',
            'stock_qty' => 50,
            'unit' => 'sheets',
            'reorder_level' => 10,
        ]);

        $this->chipboardItem = InventoryItem::create([
            'print_shop_id' => $this->shop->id,
            'name' => '2mm Chipboard',
            'sku' => 'BRD-CHP-2MM',
            'category' => 'Raw Material',
            'stock_qty' => 40,
            'unit' => 'pcs',
            'reorder_level' => 10,
        ]);

        // Link explicit BOM items
        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $this->config->id,
            'inventory_item_id' => $this->paperItem->id,
            'binding_type' => 'hardbound',
            'usage_qty' => 1.0,
            'unit' => 'sheets',
        ]);

        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $this->config->id,
            'inventory_item_id' => $this->leatheretteItem->id,
            'binding_type' => 'hardbound',
            'usage_qty' => 1.0,
            'unit' => 'sheets',
        ]);

        ThesisBindingBomItem::create([
            'thesis_binding_config_id' => $this->config->id,
            'inventory_item_id' => $this->chipboardItem->id,
            'binding_type' => 'hardbound',
            'usage_qty' => 1.0,
            'unit' => 'pcs',
        ]);
    }

    public function test_staff_can_view_production_queue_and_kpis(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0001',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'is_rush' => true,
            'production_stage' => Order::STAGE_QUEUE,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'full_package',
            'bw_pages_count' => 100,
            'color_pages_count' => 10,
            'total_pages_count' => 110,
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'paper_size' => 'A4',
            'copies_count' => 1,
            'unit_price' => 500.00,
            'total_price' => 500.00,
        ]);

        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->assertSee('Production Operations Hub')
            ->assertSee('ORD-2026-0001')
            ->assertSee('RUSH')
            ->assertSee('Maroon');
    }

    public function test_staff_can_advance_order_stages_sequentially(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0002',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 400.00,
            'production_stage' => Order::STAGE_QUEUE,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'full_package',
            'total_pages_count' => 50,
            'copies_count' => 1,
            'unit_price' => 400.00,
            'total_price' => 400.00,
        ]);

        $this->actingAs($this->staff);

        // Advance 1: Queue -> Printing
        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('advanceOrder', $order->id);

        $order->refresh();
        $this->assertEquals(Order::STAGE_PRINTING, $order->production_stage);
        $this->assertEquals(Order::STATUS_IN_PRODUCTION, $order->order_status);

        // Advance 2: Printing -> Binding
        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('advanceOrder', $order->id);

        $order->refresh();
        $this->assertEquals(Order::STAGE_BINDING, $order->production_stage);

        // Advance 3: Binding -> Quality Check
        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('advanceOrder', $order->id);

        $order->refresh();
        $this->assertEquals(Order::STAGE_QUALITY_CHECK, $order->production_stage);
        $this->assertEquals(Order::STATUS_QUALITY_CHECK, $order->order_status);
    }

    public function test_staff_can_mark_physical_paper_received_for_cover_only_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0003',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 300.00,
            'production_stage' => Order::STAGE_QUEUE,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'cover_only',
            'is_paper_received' => false,
            'total_pages_count' => 150,
            'estimated_spine_thickness_mm' => 15.0,
            'copies_count' => 1,
            'unit_price' => 300.00,
            'total_price' => 300.00,
        ]);

        $this->assertTrue($order->isPaperIntakePending());

        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('markPaperReceived', $order->id);

        $item->refresh();
        $order->refresh();

        $this->assertTrue($item->is_paper_received);
        $this->assertFalse($order->isPaperIntakePending());
    }

    public function test_advancing_order_to_ready_triggers_automated_bom_inventory_deduction(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0004',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_QUALITY_CHECK,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 500.00,
            'production_stage' => Order::STAGE_QUALITY_CHECK,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'full_package',
            'total_pages_count' => 80,
            'copies_count' => 2, // 2 copies = 160 pages, 2 leatherettes, 2 chipboards
            'unit_price' => 250.00,
            'total_price' => 500.00,
        ]);

        $this->actingAs($this->staff);

        // Advance from QC -> Ready for Pickup (Triggers BOM deduction)
        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('advanceOrder', $order->id);

        $this->paperItem->refresh();
        $this->leatheretteItem->refresh();
        $this->chipboardItem->refresh();

        // 500 - (80 * 2) = 340 sheets
        $this->assertEquals(340.0, $this->paperItem->stock_qty);
        // 50 - 2 = 48 sheets
        $this->assertEquals(48.0, $this->leatheretteItem->stock_qty);
        // 40 - 2 = 38 pcs
        $this->assertEquals(38.0, $this->chipboardItem->stock_qty);

        // Stock movements logged
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->paperItem->id,
            'movement_type' => StockMovement::TYPE_PRODUCTION_DEDUCTION,
            'quantity' => -160.0,
        ]);
    }

    public function test_cover_only_order_deducts_cover_materials_but_zero_paper(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0005',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_QUALITY_CHECK,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 300.00,
            'production_stage' => Order::STAGE_QUALITY_CHECK,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'cover_only',
            'is_paper_received' => true,
            'total_pages_count' => 200,
            'copies_count' => 1,
            'unit_price' => 300.00,
            'total_price' => 300.00,
        ]);

        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('advanceOrder', $order->id);

        $this->paperItem->refresh();
        $this->leatheretteItem->refresh();
        $this->chipboardItem->refresh();

        // Paper stock is UNTOUCHED (500 sheets remaining)
        $this->assertEquals(500.0, $this->paperItem->stock_qty);
        // Leatherette deducted 1
        $this->assertEquals(49.0, $this->leatheretteItem->stock_qty);
        // Chipboard deducted 1
        $this->assertEquals(39.0, $this->chipboardItem->stock_qty);
    }

    public function test_staff_can_report_material_spoilage(): void
    {
        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->set('spoilageItemId', $this->paperItem->id)
            ->set('spoilageQty', 15)
            ->set('spoilageReason', 'Fuser unit paper jam on page 40')
            ->call('submitSpoilage');

        $this->paperItem->refresh();
        $this->assertEquals(485.0, $this->paperItem->stock_qty);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $this->paperItem->id,
            'movement_type' => StockMovement::TYPE_SPOILAGE_WASTE,
            'quantity' => -15.0,
        ]);
    }

    public function test_staff_can_save_machine_assignment_and_notes_on_job_ticket(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0006',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_QUEUE,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 400.00,
            'production_stage' => Order::STAGE_QUEUE,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'full_package',
            'total_pages_count' => 100,
            'copies_count' => 1,
            'unit_price' => 400.00,
            'total_price' => 400.00,
        ]);

        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('viewJobTicket', $order->id)
            ->set('assignedMachine', 'Digital Hot Foil Press Alpha')
            ->set('staffNotes', 'Extra 1mm margin added on spine')
            ->call('saveJobDetails');

        $order->refresh();
        $this->assertEquals('Digital Hot Foil Press Alpha', $order->assigned_machine);
        $this->assertEquals('Extra 1mm margin added on spine', $order->staff_notes);
        $this->assertEquals($this->staff->id, $order->assigned_staff_id);
    }

    public function test_qc_rejection_steps_back_order_stage(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0007',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_QUALITY_CHECK,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'total_amount' => 400.00,
            'production_stage' => Order::STAGE_QUALITY_CHECK,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'hardbound',
            'fulfillment_type' => 'full_package',
            'total_pages_count' => 100,
            'copies_count' => 1,
            'unit_price' => 400.00,
            'total_price' => 400.00,
        ]);

        $this->actingAs($this->staff);

        Livewire::test('pages::staff.⚡staff-dashboard')
            ->call('openRejectModal', $order->id)
            ->set('rejectReason', 'Crooked foil stamp on author line')
            ->call('submitQcRejection');

        $order->refresh();
        $this->assertEquals(Order::STAGE_BINDING, $order->production_stage);
        $this->assertEquals(Order::STATUS_IN_PRODUCTION, $order->order_status);
        $this->assertStringContainsString('Crooked foil stamp on author line', $order->staff_notes ?? '');
    }
}
