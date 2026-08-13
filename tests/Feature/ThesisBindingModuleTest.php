<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\PrintShop;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThesisBindingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_access_thesis_binding_page(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Campus Print Experts',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('owner.thesis-binding'));
        $response->assertOk();
        $response->assertSee('Hardbound');
        $response->assertSee('Thesis Binding');
    }

    public function test_thesis_binding_auto_initializes_defaults_and_inventory_materials(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'University Press',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡thesis-binding')
            ->assertSet('hardbound_base_price', 350.0)
            ->assertSet('softbound_base_price', 150.0)
            ->assertSet('page_price_bw', 1.5)
            ->assertSet('page_price_color', 5.0)
            ->assertSet('rush_fee', 150.0);

        $this->assertDatabaseHas('thesis_binding_configs', [
            'print_shop_id' => $shop->id,
            'hardbound_base_price' => 350.00,
        ]);

        $this->assertGreaterThan(0, InventoryItem::where('print_shop_id', $shop->id)->count());
    }

    public function test_business_owner_can_save_pricing_configuration(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Quick Copy & Binding',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡thesis-binding')
            ->set('hardbound_base_price', 400.0)
            ->set('softbound_base_price', 180.0)
            ->set('page_price_bw', 2.0)
            ->set('page_price_color', 6.0)
            ->set('rush_fee', 200.0)
            ->call('savePricing')
            ->assertSee('Pricing configuration saved successfully!');

        $this->assertDatabaseHas('thesis_binding_configs', [
            'print_shop_id' => $shop->id,
            'hardbound_base_price' => 400.00,
            'softbound_base_price' => 180.00,
            'page_price_bw' => 2.00,
            'page_price_color' => 6.00,
            'rush_fee' => 200.00,
        ]);
    }

    public function test_business_owner_can_manage_variants_and_customer_form(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Academic Publishing',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡thesis-binding')
            ->set('new_color_input', 'Burgundy')
            ->call('addCoverColor')
            ->set('new_field_input', 'Advisor Name')
            ->call('addCustomField');

        /** @var ThesisBindingConfig $config */
        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        $this->assertContains('Burgundy', $config->cover_colors ?? []);
        $this->assertContains('Advisor Name', $config->custom_cover_fields ?? []);
    }

    public function test_business_owner_can_add_new_inventory_material_and_bom_rule(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Pro Bookbinders',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        $component = Livewire::test('pages::owner.⚡thesis-binding')
            ->set('new_inv_name', 'Special Gold Foil Sheet')
            ->set('new_inv_sku', 'FOL-GLD-99')
            ->set('new_inv_category', 'Foil')
            ->set('new_inv_stock_qty', 100)
            ->set('new_inv_unit', 'sheets')
            ->call('saveQuickInventoryItem');

        $invItem = InventoryItem::where('print_shop_id', $shop->id)->where('name', 'Special Gold Foil Sheet')->first();
        $this->assertNotNull($invItem);

        $component->set('selected_inventory_item_id', $invItem->id)
            ->set('bom_binding_type', 'hardbound')
            ->set('bom_usage_qty', 2.5)
            ->call('addBomItem')
            ->assertSee('Added Special Gold Foil Sheet to Bill of Materials (BOM)!');

        $this->assertDatabaseHas('thesis_binding_bom_items', [
            'inventory_item_id' => $invItem->id,
            'binding_type' => 'hardbound',
            'usage_qty' => 2.5,
        ]);
    }
}
