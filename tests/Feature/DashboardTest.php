<?php

namespace Tests\Feature;

use App\Models\PrintShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_business_owner_without_shop_is_redirected_to_wizard(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->actingAs($owner);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('owner.wizard'));
    }

    public function test_business_owner_with_completed_shop_is_redirected_to_owner_dashboard(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Prints',
            'slug' => 'apex-prints',
            'is_setup_completed' => true,
        ]);
        $this->actingAs($owner);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_production_staff_is_redirected_to_staff_dashboard(): void
    {
        /** @var User $staff */
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $this->actingAs($staff);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_is_redirected_to_customer_dashboard(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->actingAs($customer);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('customer.dashboard'));
    }

    public function test_all_role_dashboards_render_successfully(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Prints',
            'slug' => 'apex-prints',
            'is_setup_completed' => true,
        ]);

        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();

        /** @var User $staff */
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();

        /** @var User $customer */
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();
    }
}
