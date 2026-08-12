<?php

namespace Tests\Feature;

use App\Models\PrintShop;
use App\Models\User;
use App\Services\PrintServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebBuilderAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_builder_is_preinstalled_by_default(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Design Print Studio',
            'is_setup_completed' => true,
        ]);

        $this->assertTrue(PrintServiceCatalog::isPreinstalled('web_builder'));
        $this->assertTrue($shop->hasService('web_builder'));
    }

    public function test_admin_cannot_remove_web_builder_app(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Pro Binding Hub',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡owner-dashboard')
            ->call('toggleServiceStatus', 'web_builder')
            ->assertDispatched('toast', message: 'Pre-installed system apps cannot be removed.');

        $this->assertTrue($shop->hasService('web_builder'));
    }

    public function test_web_builder_app_icon_is_rendered_in_dashboard(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Metro Digital Printing',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡owner-dashboard')
            ->assertSee('Web Builder')
            ->assertSee('CORE');
    }

    public function test_business_owner_can_open_web_builder_page(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('owner.web-builder'));
        $response->assertOk();
        $response->assertSee('Web Builder');
        $response->assertSee('Customer Storefront Visual Editor');
    }

    public function test_web_builder_livewire_component_interactivity(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Creative Copy Shop',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡web-builder')
            ->assertSet('storefront_title', 'Creative Copy Shop')
            ->set('storefront_title', 'Updated Print Shop Name')
            ->call('saveChanges')
            ->assertDispatched('toast');
    }
}
