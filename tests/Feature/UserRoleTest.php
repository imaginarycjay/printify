<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_can_be_assigned_and_checked(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isStaff());
        $this->assertFalse($owner->isCustomer());

        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isOwner());

        $this->assertTrue($customer->isCustomer());
        $this->assertTrue($customer->hasRole(User::ROLE_CUSTOMER));
    }

    public function test_registration_assigns_default_customer_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'newcustomer@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }
}
