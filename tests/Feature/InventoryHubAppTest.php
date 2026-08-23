<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use App\Services\PrintServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryHubAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_hub_is_registered_as_preinstalled_core_service(): void
    {
        $this->assertTrue(PrintServiceCatalog::isPreinstalled('inventory_hub'));
        $this->assertContains('inventory_hub', PrintServiceCatalog::preinstalledKeys());

        $item = PrintServiceCatalog::find('inventory_hub');
        $this->assertNotNull($item);
        $this->assertEquals('Inventory Hub', $item['name']);
        $this->assertTrue($item['is_preinstalled']);
    }

    public function test_print_shop_has_service_returns_true_for_inventory_hub(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        $this->assertTrue($shop->hasService('inventory_hub'));
    }

    public function test_business_owner_can_access_inventory_hub_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        $response = $this->actingAs($user)->get(route('owner.inventory-hub'));
        $response->assertOk();
    }

    public function test_inventory_hub_seeds_default_materials_if_empty(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::owner.⚡inventory-hub')
            ->assertOk();

        $this->assertGreaterThan(0, $shop->inventoryItems()->count());
        $this->assertDatabaseHas('inventory_items', [
            'print_shop_id' => $shop->id,
            'name' => 'Chipboard Heavy Duty (2mm)',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => StockMovement::TYPE_MANUAL_STOCK_IN,
        ]);
    }

    public function test_business_owner_can_add_and_update_inventory_item(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::owner.⚡inventory-hub')
            ->set('item_name', 'Matte PVC Sheet (A4)')
            ->set('item_sku', 'MAT-PVC-01')
            ->set('item_category', 'Cover')
            ->set('item_type', InventoryItem::TYPE_RAW_MATERIAL)
            ->set('item_service_tag', 'thesis_binding')
            ->set('item_stock_qty', 75.0)
            ->set('item_unit', 'sheets')
            ->set('item_reorder_level', 15.0)
            ->set('item_unit_cost', 30.00)
            ->set('item_selling_price', null)
            ->set('item_supplier_name', 'PVC Supplies Manila')
            ->call('saveItem')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('inventory_items', [
            'print_shop_id' => $shop->id,
            'name' => 'Matte PVC Sheet (A4)',
            'stock_qty' => 75.0,
            'item_type' => 'raw_material',
            'unit_cost' => 30.00,
        ]);
    }

    public function test_stock_adjustment_creates_stock_movement_record(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        $item = $shop->inventoryItems()->create([
            'name' => 'Test Paper Stock',
            'sku' => 'TST-PAP',
            'category' => 'Paper',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 100.0,
            'unit' => 'sheets',
            'reorder_level' => 20.0,
            'unit_cost' => 0.50,
        ]);

        Livewire::actingAs($user)
            ->test('pages::owner.⚡inventory-hub')
            ->call('openAdjustModal', $item->id)
            ->set('adjust_type', StockMovement::TYPE_MANUAL_STOCK_IN)
            ->set('adjust_qty', 50.0)
            ->set('adjust_note', 'Delivery Invoice #9988')
            ->call('saveAdjustment')
            ->assertDispatched('toast');

        $this->assertEquals(150.0, $item->fresh()->stock_qty);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_MANUAL_STOCK_IN,
            'quantity' => 50.0,
            'previous_stock' => 100.0,
            'resulting_stock' => 150.0,
            'reference_note' => 'Delivery Invoice #9988',
        ]);
    }

    public function test_thesis_binding_workspace_loads_with_central_inventory_integration(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $user->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        ThesisBindingConfig::create([
            'print_shop_id' => $shop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'page_price_bw' => 1.50,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Dark Blue'],
            'foil_colors' => ['Gold', 'Silver'],
            'paper_sizes' => ['A4', 'Letter (Short)'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'require_pdf_upload' => true,
            'custom_cover_fields' => ['Thesis Title'],
        ]);

        Livewire::actingAs($user)
            ->test('pages::owner.⚡thesis-binding')
            ->assertOk()
            ->assertSee('Central Inventory Hub')
            ->call('setTab', 'bom')
            ->assertSee('Bill of Materials (BOM)')
            ->assertSee('Recipe Formulation');
    }
}
