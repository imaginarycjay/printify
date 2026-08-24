<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\ShopService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_owner_dashboard_and_is_redirected(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($customer)->get(route('owner.dashboard'));

        $response->assertRedirect(route('customer.dashboard'));
        $response->assertSessionHas('warning');
    }

    public function test_customer_cannot_access_staff_dashboard_and_is_redirected(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($customer)->get(route('staff.dashboard'));

        $response->assertRedirect(route('customer.dashboard'));
        $response->assertSessionHas('warning');
    }

    public function test_staff_cannot_access_owner_dashboard_or_analytics_and_is_redirected(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);

        $responseDashboard = $this->actingAs($staff)->get(route('owner.dashboard'));
        $responseDashboard->assertRedirect(route('staff.dashboard'));

        $responseAnalytics = $this->actingAs($staff)->get(route('owner.analytics-hub'));
        $responseAnalytics->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_access_customer_dashboard_and_is_redirected(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);

        $response = $this->actingAs($staff)->get(route('customer.dashboard'));
        $response->assertRedirect(route('staff.dashboard'));
        $response->assertSessionHas('warning');
    }

    public function test_owner_cannot_access_staff_or_customer_dashboard_and_is_redirected(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        $responseStaff = $this->actingAs($owner)->get(route('staff.dashboard'));
        $responseStaff->assertRedirect(route('owner.dashboard'));
        $responseStaff->assertSessionHas('warning');

        $responseCustomer = $this->actingAs($owner)->get(route('customer.dashboard'));
        $responseCustomer->assertRedirect(route('owner.dashboard'));
        $responseCustomer->assertSessionHas('warning');
    }

    public function test_owner_can_access_owner_routes(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Print Hub',
            'slug' => 'apex-print-hub',
            'is_onboarding_completed' => true,
        ]);

        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('owner.analytics-hub'))->assertOk();
    }

    public function test_analytics_hub_renders_dynamic_catalog_services(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Print Hub',
            'slug' => 'apex-print-hub',
            'is_onboarding_completed' => true,
        ]);
        ShopService::create(['print_shop_id' => $shop->id, 'service_key' => 'thesis_binding', 'is_active' => true]);
        ShopService::create(['print_shop_id' => $shop->id, 'service_key' => 'document_printing', 'is_active' => true]);

        $this->actingAs($owner);

        Livewire::test('pages::owner.analytics-hub')
            ->assertSee('All Services')
            ->assertSee('Thesis Binding')
            ->assertSee('Document Printing');
    }

    public function test_customer_dashboard_reflects_order_received_when_staff_completes_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $shop = PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Apex Print Hub',
            'slug' => 'apex-print-hub',
            'is_onboarding_completed' => true,
        ]);
        ShopService::create(['print_shop_id' => $shop->id, 'service_key' => 'document_printing', 'is_active' => true]);

        $order = Order::create([
            'order_number' => 'DOC-2026-TEST01',
            'print_shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'service_key' => 'document_printing',
            'order_status' => Order::STATUS_READY_FOR_PICKUP,
            'production_stage' => Order::STAGE_READY_FOR_PICKUP,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 150.00,
            'total_amount' => 150.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'binding_type' => 'ring_bind',
            'fulfillment_type' => OrderItem::FULFILLMENT_FULL_PACKAGE,
            'total_pages_count' => 20,
            'copies_count' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00,
            'custom_fields_data' => [
                'service_key' => 'document_printing',
                'finishing_type' => 'ring_bind',
            ],
        ]);

        // Customer sees Ready for Pickup initially
        $this->actingAs($customer);
        Livewire::test('pages::customer.customer-dashboard')
            ->set('active_tab', 'orders')
            ->assertSee('Ready for Pickup');

        // Staff marks the order as picked up / completed
        $order->advanceStage(); // advances from STAGE_READY_FOR_PICKUP to STAGE_COMPLETED
        $order->refresh();

        $this->assertEquals(Order::STAGE_COMPLETED, $order->production_stage);
        $this->assertEquals(Order::STATUS_COMPLETED, $order->order_status);

        // Customer dashboard now displays Completed / Received
        Livewire::test('pages::customer.customer-dashboard')
            ->set('active_tab', 'orders')
            ->assertSee('Completed / Received')
            ->assertSee('Completed & Received');
    }
}
