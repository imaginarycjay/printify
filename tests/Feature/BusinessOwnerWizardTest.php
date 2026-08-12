<?php

namespace Tests\Feature;

use App\Models\PrintShop;
use App\Models\ShopService;
use App\Models\User;
use App\Services\PrintServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOwnerWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_service_catalog_contains_the_8_mvp_services(): void
    {
        $services = PrintServiceCatalog::all();

        $this->assertCount(8, $services);
        $this->assertArrayHasKey('thesis_binding', $services);
        $this->assertArrayHasKey('document_printing', $services);
        $this->assertArrayHasKey('tarpaulin', $services);
        $this->assertArrayHasKey('tshirt', $services);
        $this->assertArrayHasKey('pvc_id', $services);
        $this->assertArrayHasKey('trophy', $services);
        $this->assertArrayHasKey('mug', $services);
        $this->assertArrayHasKey('sticker', $services);
    }

    public function test_business_owner_can_complete_setup_wizard(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡owner-wizard')
            ->set('shop_name', 'Pixel Print Studio')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('selected_services', ['thesis_binding', 'document_printing', 'sticker'])
            ->call('completeSetup')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('print_shops', [
            'user_id' => $owner->id,
            'name' => 'Pixel Print Studio',
            'is_setup_completed' => true,
        ]);

        $shop = PrintShop::where('user_id', $owner->id)->first();
        $this->assertNotNull($shop);

        $this->assertTrue($shop->hasService('thesis_binding'));
        $this->assertTrue($shop->hasService('document_printing'));
        $this->assertTrue($shop->hasService('sticker'));
        $this->assertFalse($shop->hasService('tarpaulin'));
    }

    public function test_business_owner_can_toggle_services_in_dashboard(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        /** @var PrintShop $shop */
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Alpha Prints',
            'is_setup_completed' => true,
        ]);

        ShopService::create([
            'print_shop_id' => $shop->id,
            'service_key' => 'document_printing',
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.⚡owner-dashboard')
            ->call('toggleServiceStatus', 'tarpaulin');

        $this->assertTrue($shop->fresh()->hasService('tarpaulin'));
    }
}
