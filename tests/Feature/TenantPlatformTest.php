<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantPlatformTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $name = 'Print Shop'): PrintShop
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);

        return PrintShop::create(['user_id' => $owner->id, 'name' => $name, 'is_setup_completed' => true, 'approval_status' => 'approved']);
    }

    private function order(PrintShop $shop, string $status = 'in_queue'): Order
    {
        return Order::create(['order_number' => fake()->uuid(), 'print_shop_id' => $shop->id, 'customer_id' => User::factory()->create()->id, 'service_key' => 'thesis_binding', 'order_status' => $status]);
    }

    public function test_registration_cannot_choose_privileged_roles(): void
    {
        $user = app(CreateNewUser::class)->create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => User::ROLE_ADMIN]);
        $this->assertTrue($user->isCustomer());
        $this->post(route('owner.register.store'), ['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect(route('owner.wizard'));
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'role' => User::ROLE_BUSINESS_OWNER]);
    }

    public function test_directory_hides_unapproved_shops_and_profiles_render(): void
    {
        $shop = $this->shop('Visible Prints');
        $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $hidden = $this->shop('Hidden Prints');
        $hidden->update(['approval_status' => 'pending']);
        $this->get(route('shops.index'))->assertOk()->assertSee('Visible Prints')->assertDontSee('Hidden Prints');
        $this->get(route('shops.show', $shop))->assertOk()->assertSee('Normal');
        $this->get(route('shops.show', $hidden))->assertNotFound();
    }

    public function test_workload_is_per_service_and_pause_expires(): void
    {
        $shop = $this->shop();
        $service = $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true, 'capacity' => 2]);
        $document = $shop->services()->create(['service_key' => 'document_printing', 'is_active' => true]);
        $this->assertSame('Normal', $service->workload());
        $order = $this->order($shop);
        $this->assertSame('Busy', $service->workload());
        $this->order($shop);
        $this->order($shop, 'ready_for_pickup');
        $this->order($this->shop());
        $this->assertSame('At capacity', $service->workload());
        $this->assertSame('Normal', $document->workload());
        $this->assertTrue($service->acceptsOrders());
        $service->update(['pause_reason' => 'Maintenance', 'paused_until' => now()->addHour()]);
        $this->assertFalse($service->acceptsOrders());
        $this->travel(2)->hours();
        $this->assertTrue($service->acceptsOrders());
        $order->update(['payment_status' => 'rejected']);
        $this->assertSame('Busy', $service->workload());
    }

    public function test_owner_cannot_change_another_shops_availability(): void
    {
        $a = $this->shop();
        $b = $this->shop();
        $service = $b->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
        $this->actingAs($a->user)->post(route('owner.availability', $service), ['capacity' => 1])->assertNotFound();
        $this->assertSame(10, $service->fresh()->getAttribute('capacity'));
        $this->actingAs($b->user)->post(route('owner.availability', $service), ['capacity' => 3, 'pause_reason' => 'Maintenance', 'paused_until' => now()->addDay()->toDateTimeString()])->assertRedirect();
        $this->assertTrue($service->fresh()->isPaused());
        $this->post(route('owner.availability', $service), ['capacity' => 3])->assertRedirect();
        $this->assertFalse($service->fresh()->isPaused());
    }

    public function test_staff_actions_and_files_are_isolated(): void
    {
        Storage::fake('local');
        $a = $this->shop();
        $b = $this->shop();
        $order = $this->order($b);
        $order->update(['payment_proof_path' => 'proof.png']);
        Storage::disk('local')->put('proof.png', 'private');
        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $staff->forceFill(['staff_shop_id' => $a->id])->save();
        Livewire::actingAs($staff)->test('pages::staff.⚡staff-dashboard')->call('verifyPayment', $order->id);
        $this->assertNotSame(Order::PAYMENT_VERIFIED_PAID, $order->fresh()->payment_status);
        $this->get(route('orders.file', ['order' => $order, 'kind' => 'payment']))->assertForbidden();
        $this->actingAs($order->customer)->get(route('orders.file', ['order' => $order, 'kind' => 'payment']))->assertOk();
        $this->actingAs($b->user)->get(route('orders.file', ['order' => $order, 'kind' => 'payment']))->assertOk();
        $staff->forceFill(['staff_shop_id' => null])->save();
        $this->actingAs($staff)->get(route('staff.dashboard'))->assertForbidden();
    }

    public function test_approval_is_admin_only_and_rejection_can_be_resubmitted(): void
    {
        $shop = $this->shop();
        $shop->update(['approval_status' => 'pending']);
        $this->actingAs($shop->user)->post(route('admin.shops.review', $shop), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('pending', $shop->fresh()->getAttribute('approval_status'));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.shops'))->assertOk();
        $this->post(route('admin.shops.review', $shop), ['decision' => 'rejected', 'reason' => 'Contact required'])->assertRedirect();
        $this->actingAs($shop->user)->get(route('owner.platform'))->assertOk();
        $this->post(route('owner.platform.save'), ['address' => 'Main Street', 'contact' => 'shop@example.com'])->assertRedirect();
        $this->assertSame('pending', $shop->fresh()->getAttribute('approval_status'));
        $this->actingAs($admin)->post(route('admin.shops.review', $shop), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $shop->fresh()->getAttribute('approval_status'));
    }

    public function test_invitation_is_single_use_and_access_can_be_revoked(): void
    {
        Mail::fake();
        $shop = $this->shop();
        $token = str_repeat('a', 64);
        DB::table('staff_invitations')->insert(['print_shop_id' => $shop->id, 'email' => 'staff@example.com', 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        $this->get(route('staff.invitation', $token))->assertOk();
        $this->post(route('staff.invitation.accept', $token), ['name' => 'Staff', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect(route('staff.dashboard'));
        $staff = User::where('email', 'staff@example.com')->firstOrFail();
        $this->assertSame($shop->id, $staff->getAttribute('staff_shop_id'));
        $this->post(route('staff.invitation.accept', $token))->assertGone();
        $this->actingAs($shop->user)->delete(route('owner.staff.revoke', $staff))->assertRedirect();
        $this->assertNull($staff->fresh()->getAttribute('staff_shop_id'));
        $this->post(route('owner.staff.invite'), ['email' => 'newstaff@example.com'])->assertRedirect();
        $this->assertDatabaseHas('staff_invitations', ['email' => 'newstaff@example.com']);
    }

    public function test_legacy_orders_redirect_to_directory_and_pauses_block_checkout(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $shop = $this->shop();
        $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true, 'paused_until' => now()->addDay(), 'pause_reason' => 'Maintenance']);
        $this->actingAs($customer)->get(route('customer.order-thesis'))->assertRedirect(route('shops.index', ['service' => 'thesis_binding']));
        $this->get(route('customer.shop.order-thesis', $shop))->assertStatus(422);
    }

    public function test_customer_orders_from_selected_shop_and_pause_is_rechecked(): void
    {
        $first = $this->shop('Shop A');
        $second = $this->shop('Shop B');
        $third = $this->shop('Shop C');
        foreach ([$first, $second, $third] as $shop) {
            $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);
            $shop->thesisBindingConfig()->create(['is_active' => true, 'hardbound_base_price' => $shop->id === $second->id ? 600 : 300, 'require_pdf_upload' => false]);
        }
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $component = Livewire::actingAs($customer)->test('pages::customer.⚡thesis-order-wizard', ['shop' => $second->id])
            ->assertSet('hardbound_base_price', 600);
        $second->services()->firstOrFail()->update(['paused_until' => now()->addDay(), 'pause_reason' => 'Maintenance']);
        $component->call('selectedShop')->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_expired_invites_and_incompatible_accounts_are_rejected(): void
    {
        $shop = $this->shop();
        $token = str_repeat('b', 64);
        DB::table('staff_invitations')->insert(['print_shop_id' => $shop->id, 'email' => $shop->user->email, 'token' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        $this->actingAs($shop->user)->post(route('staff.invitation.accept', $token))->assertForbidden();
        DB::table('staff_invitations')->update(['expires_at' => now()->subMinute()]);
        $this->post(route('staff.invitation.accept', $token))->assertGone();
    }

    public function test_private_file_migration_preserves_contents(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $order = $this->order($this->shop());
        $order->update(['payment_proof_path' => 'orders/payments/old.png']);
        Storage::disk('public')->put('orders/payments/old.png', 'original contents');
        $this->artisan('platform:privatize-files')->assertSuccessful();
        $this->assertSame('original contents', Storage::disk('local')->get('orders/payments/old.png'));
        Storage::disk('public')->assertMissing('orders/payments/old.png');
    }

    public function test_admin_can_suspend_and_reactivate_shop_with_audit_logs_and_enforcement(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $shop = $this->shop('Moderated Studio');
        $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);

        $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
        $staff->forceFill(['staff_shop_id' => $shop->id])->save();

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = $this->order($shop);

        // Suspension requires a reason.
        $this->actingAs($admin)->post(route('admin.shops.review', $shop), [
            'decision' => 'suspended',
        ])->assertSessionHasErrors('reason');
        $this->assertSame(PrintShop::STATUS_APPROVED, $shop->fresh()->getAttribute('approval_status'));

        // Suspend shop with reason and verify audit log + marketplace isolation.
        $this->actingAs($admin)->post(route('admin.shops.review', $shop), [
            'decision' => 'suspended',
            'reason' => 'Repeated unfulfilled turnaround',
        ])->assertRedirect();

        $shop->refresh();
        $this->assertSame(PrintShop::STATUS_SUSPENDED, $shop->approval_status);
        $this->assertSame('Repeated unfulfilled turnaround', $shop->rejection_reason);
        $this->assertDatabaseHas('audit_logs', [
            'print_shop_id' => $shop->id,
            'actor_id' => $admin->id,
            'action' => 'shop.suspended',
        ]);

        // Suspended filter tab shows the suspended shop.
        $this->actingAs($admin)->get(route('admin.shops', ['status' => 'suspended']))
            ->assertOk()
            ->assertSee('Moderated Studio')
            ->assertSee('Repeated unfulfilled turnaround');

        // Staff invitation acceptance for suspended shop is blocked.
        $token = str_repeat('c', 64);
        DB::table('staff_invitations')->insert([
            'print_shop_id' => $shop->id,
            'email' => 'invited-suspended@example.com',
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);
        $this->post(route('logout'));
        $this->post(route('staff.invitation.accept', $token), [
            'name' => 'Invited Staff',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        // Owner and staff login are blocked when their shop is suspended.
        $this->post(route('login.store'), [
            'email' => $shop->user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Pre-authenticated owner and staff sessions are terminated upon navigation.
        $this->actingAs($shop->user)->get(route('owner.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($staff)->get(route('staff.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // OrderPolicy::update blocks suspended shop owner and staff.
        $this->assertFalse($shop->user->can('update', $order));
        $this->assertFalse($staff->can('update', $order));

        // Admin reactivates shop, clearing reason and restoring access.
        $this->actingAs($admin)->post(route('admin.shops.review', $shop), [
            'decision' => 'reactivated',
        ])->assertRedirect();

        $shop->refresh();
        $this->assertSame(PrintShop::STATUS_APPROVED, $shop->approval_status);
        $this->assertNull($shop->rejection_reason);
        $this->assertDatabaseHas('audit_logs', [
            'print_shop_id' => $shop->id,
            'actor_id' => $admin->id,
            'action' => 'shop.reactivated',
        ]);

        $this->post(route('logout'));

        // Policy, directory listing, and logins work again.
        $this->actingAs($customer)->get(route('shops.index'))->assertOk()->assertSee('Moderated Studio');
        $this->post(route('logout'));
        $this->assertTrue($shop->user->can('update', $order));

        $this->post(route('login.store'), [
            'email' => $shop->user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($shop->user);
        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_suspended_shop_is_hidden_from_marketplace_and_blocked_from_customer_checkout(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $shop = $this->shop('Suspended Storefront');
        $shop->services()->create(['service_key' => 'thesis_binding', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.shops.review', $shop), [
            'decision' => 'suspended',
            'reason' => 'Temporarily offline for compliance check',
        ])->assertRedirect();

        $this->actingAs($customer)->get(route('shops.index'))->assertOk()->assertDontSee('Suspended Storefront');
        $this->get(route('shops.show', $shop))->assertNotFound();
        $this->get(route('customer.shop.order-thesis', $shop))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.shops.review', $shop), [
            'decision' => 'reactivated',
        ])->assertRedirect();

        $this->actingAs($customer)->get(route('shops.index'))->assertOk()->assertSee('Suspended Storefront');
        $this->get(route('shops.show', $shop))->assertOk()->assertSee('Suspended Storefront');
    }

    public function test_admin_can_update_platform_retention_settings_and_view_isolated_storage_health(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $shop = $this->shop('Ledger Isolated Studio');
        $order = Order::create([
            'order_number' => 'ORD-2026-9999',
            'print_shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_QUEUE,
            'subtotal_amount' => 98765.43,
            'total_amount' => 98765.43,
            'payment_proof_path' => 'orders/payments/proof-9999.png',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'document_file_path' => 'orders/documents/doc-9999.pdf',
        ]);
        Storage::disk('local')->put('orders/documents/doc-9999.pdf', str_repeat('a', 2048));
        Storage::disk('local')->put('orders/payments/proof-9999.png', str_repeat('b', 1024));

        // Non-admins cannot update platform retention settings.
        $this->actingAs($customer)->post(route('admin.settings.update'), [
            'min_retention_days' => 5,
            'default_retention_days' => 21,
            'max_retention_days' => 60,
            'max_upload_mb' => 75,
        ])->assertRedirect(route('customer.dashboard'));

        $this->actingAs($shop->user)->post(route('admin.settings.update'), [
            'min_retention_days' => 5,
            'default_retention_days' => 21,
            'max_retention_days' => 60,
            'max_upload_mb' => 75,
        ])->assertRedirect(route('owner.dashboard'));

        // Admin views storage health & policy without seeing private shop financial figures.
        $this->actingAs($admin)->get(route('admin.shops'))
            ->assertOk()
            ->assertSee('Tenant Governance & Storage Health')
            ->assertSee('Global File Retention & Upload Policy')
            ->assertSee('1 artwork files')
            ->assertSee('1 payment proofs')
            ->assertDontSee('98,765.43')
            ->assertDontSee('98765.43');

        // Invalid retention bounds fail validation in platformSettings bag.
        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'min_retention_days' => 10,
            'default_retention_days' => 120,
            'max_retention_days' => 60,
            'max_upload_mb' => 75,
        ])->assertSessionHasErrors(['default_retention_days'], null, 'platformSettings');

        // Valid update persists and writes audit log.
        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'min_retention_days' => 5,
            'default_retention_days' => 21,
            'max_retention_days' => 60,
            'max_upload_mb' => 75,
        ])->assertRedirect();

        $settings = PlatformSetting::current();
        $this->assertSame(5, $settings->min_retention_days);
        $this->assertSame(21, $settings->default_retention_days);
        $this->assertSame(60, $settings->max_retention_days);
        $this->assertSame(75, $settings->max_upload_mb);
        $this->assertSame($admin->id, $settings->updated_by);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'platform_settings.updated',
        ]);

        // Per-shop retention clamping respects global bounds.
        $this->assertSame(21, $shop->effectiveFileRetentionDays($settings));
        $shop->update(['file_retention_days' => 2]);
        $this->assertSame(5, $shop->effectiveFileRetentionDays($settings));
        $shop->update(['file_retention_days' => 120]);
        $this->assertSame(60, $shop->effectiveFileRetentionDays($settings));
    }

    public function test_pressqueue_rebrand_light_mode_auth_contrast_and_retired_web_builder(): void
    {
        $shop = $this->shop('Contrast Check Studio');

        $this->assertFalse(Route::has('owner.web-builder'));
        $this->actingAs($shop->user)->get('/owner/web-builder')->assertNotFound();
        $this->post(route('logout'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('PressQueue')
            ->assertDontSee('Printify');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('PressQueue')
            ->assertSee('text-stone-900 dark:text-white', false)
            ->assertDontSee('from-white via-stone-100 to-stone-300 bg-clip-text text-transparent', false)
            ->assertDontSee('Printify');

        $this->get(route('shops.index'))
            ->assertOk()
            ->assertSee('PressQueue')
            ->assertDontSee('Printify');
    }
}
