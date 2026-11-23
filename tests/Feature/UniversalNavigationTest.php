<?php

use App\Models\Order;
use App\Models\PrintShop;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('navigation helper resolves deterministic workspace homes and role labels for all roles', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    expect(Navigation::workspaceHome(null))->toBe(['url' => route('home'), 'label' => 'Home'])
        ->and(Navigation::workspaceHome($admin))->toBe(['url' => route('admin.shops'), 'label' => 'Platform Admin'])
        ->and(Navigation::workspaceHome($owner))->toBe(['url' => route('owner.dashboard'), 'label' => 'App Launcher'])
        ->and(Navigation::workspaceHome($staff))->toBe(['url' => route('staff.dashboard'), 'label' => 'Production Queue'])
        ->and(Navigation::workspaceHome($customer))->toBe(['url' => route('customer.dashboard'), 'label' => 'My Orders'])
        ->and(Navigation::roleLabel(null))->toBe('Guest')
        ->and(Navigation::roleLabel($admin))->toBe('Super Admin')
        ->and(Navigation::roleLabel($owner))->toBe('Printing Shop Owner')
        ->and(Navigation::roleLabel($staff))->toBe('Production Staff')
        ->and(Navigation::roleLabel($customer))->toBe('Customer');
});

test('navigation helper returns null on root workspaces and resolves deterministic back targets with scoped overrides', function () {
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $shop = PrintShop::create([
        'user_id' => $owner->id,
        'name' => 'Kabacan Academic Press',
        'is_setup_completed' => true,
        'approval_status' => PrintShop::STATUS_APPROVED,
    ]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    foreach (['home', 'owner.dashboard', 'staff.dashboard', 'staff.production-hub', 'customer.dashboard', 'admin.shops'] as $rootRoute) {
        expect(Navigation::backTarget($owner, $rootRoute))->toBeNull();
    }

    expect(Navigation::backTarget($owner, 'owner.platform'))
        ->toMatchArray(['url' => route('owner.dashboard'), 'label' => 'App Launcher'])
        ->and(Navigation::backTarget($owner, 'owner.reports.show'))
        ->toMatchArray(['url' => route('owner.analytics-hub', ['tab' => 'reports']), 'label' => 'Standardized Reports'])
        ->and(Navigation::backTarget($owner, 'owner.inventory-hub', ['from' => 'thesis-binding']))
        ->toMatchArray(['url' => route('owner.thesis-binding'), 'label' => 'Thesis Binding'])
        ->and(Navigation::backTarget($owner, 'owner.inventory-hub', ['from' => 'payments']))
        ->toMatchArray(['url' => route('owner.analytics-hub', ['tab' => 'payments']), 'label' => 'Payments & Audit'])
        ->and(Navigation::backTarget($customer, 'shops.show'))
        ->toMatchArray(['url' => route('shops.index'), 'label' => 'Find Shops'])
        ->and(Navigation::backTarget($customer, 'customer.shop.checkout', ['shop' => $shop]))
        ->toMatchArray(['url' => route('shops.show', $shop->id), 'label' => 'Kabacan Academic Press'])
        ->and(Navigation::backTarget($customer, 'customer.shop.order-thesis', ['shop' => $shop]))
        ->toMatchArray(['url' => route('shops.show', $shop->id), 'label' => 'Kabacan Academic Press'])
        // Cross-role ?from= spoofing must be ignored
        ->and(Navigation::backTarget($customer, 'customer.shop.checkout', ['shop' => $shop, 'from' => 'thesis-binding']))
        ->toMatchArray(['url' => route('shops.show', $shop->id), 'label' => 'Kabacan Academic Press']);
});

test('settings layout displays role-specific back navigation and role badge across all four roles', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $staff = User::factory()->create(['role' => User::ROLE_PRODUCTION_STAFF]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $this->actingAs($admin)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Back to Platform Admin')
        ->assertSee('Super Admin')
        ->assertSee(route('admin.shops'), false);

    $this->actingAs($owner)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Back to App Launcher')
        ->assertSee('Printing Shop Owner')
        ->assertSee(route('owner.dashboard'), false);

    $this->actingAs($staff)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Back to Production Queue')
        ->assertSee('Production Staff')
        ->assertSee(route('staff.dashboard'), false);

    $this->actingAs($customer)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Back to My Orders')
        ->assertSee('Customer')
        ->assertSee(route('customer.dashboard'), false);
});

test('shared headers enforce universal contextual back navigation without root dead-end back links', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $shop = PrintShop::create([
        'user_id' => $owner->id,
        'name' => 'Kabacan Academic Press',
        'is_setup_completed' => true,
        'approval_status' => PrintShop::STATUS_APPROVED,
    ]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $this->get(route('shops.index'))
        ->assertOk()
        ->assertDontSee('data-test="universal-back-link"', false);

    $this->actingAs($admin)->get(route('admin.shops'))
        ->assertOk()
        ->assertDontSee('data-test="universal-back-link"', false);

    $this->actingAs($admin)->get(route('shops.index'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('Platform Admin')
        ->assertSee(route('admin.shops'), false);

    $this->actingAs($customer)->get(route('shops.index'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('My Orders')
        ->assertSee(route('customer.dashboard'), false);

    $this->get(route('shops.show', $shop->id))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('Find Shops')
        ->assertSee(route('shops.index'), false);

    $this->actingAs($owner)->get(route('owner.platform'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('App Launcher')
        ->assertSee(route('owner.dashboard'), false);
});

test('standardized reports and receipts provide deterministic role-aware return URLs and analytics hub accepts tab query', function () {
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $shop = PrintShop::create([
        'user_id' => $owner->id,
        'name' => 'Kabacan Academic Press',
        'is_setup_completed' => true,
        'approval_status' => PrintShop::STATUS_APPROVED,
    ]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $order = Order::create([
        'order_number' => 'PQ-NAV-1001',
        'tracking_code' => 'TRK-NAV1001',
        'print_shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'order_status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_UNPAID,
        'total_amount' => 250.00,
        'amount_paid' => 0.00,
        'balance_due' => 250.00,
    ]);

    $this->actingAs($owner)->get(route('owner.reports.show', ['type' => 'schedule']))
        ->assertOk()
        ->assertSee('Standardized Reports')
        ->assertSee(route('owner.analytics-hub', ['tab' => 'reports']), false);

    Livewire::actingAs($owner)
        ->withQueryParams(['tab' => 'reports'])
        ->test('pages::owner.⚡analytics-hub')
        ->assertSet('active_tab', 'reports');

    Livewire::actingAs($owner)
        ->withQueryParams(['tab' => 'invalid_tab'])
        ->test('pages::owner.⚡analytics-hub')
        ->assertSet('active_tab', 'overview');

    $this->actingAs($customer)->get(route('orders.receipt', $order))
        ->assertOk()
        ->assertSee('My Orders')
        ->assertSee(route('customer.dashboard'), false);

    $this->actingAs($owner)->get(route('orders.receipt', $order))
        ->assertOk()
        ->assertSee('Payments & Audit')
        ->assertSee(route('owner.analytics-hub', ['tab' => 'payments']), false);
});

test('owner, staff, and customer operational pages render universal contextual back links to exact parent routes', function () {
    $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $shop = PrintShop::create([
        'user_id' => $owner->id,
        'name' => 'Kabacan Academic Press',
        'is_setup_completed' => true,
        'approval_status' => PrintShop::STATUS_APPROVED,
    ]);
    $shop->services()->createMany([
        ['service_key' => 'thesis_binding', 'is_active' => true],
        ['service_key' => 'document_printing', 'is_active' => true],
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_PRODUCTION_STAFF,
    ]);
    $staff->forceFill(['staff_shop_id' => $shop->id])->save();
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $this->actingAs($owner)->get(route('owner.analytics-hub'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('App Launcher')
        ->assertSee(route('owner.dashboard'), false);

    $this->actingAs($owner)->get(route('owner.inventory-hub'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('App Launcher')
        ->assertSee(route('owner.dashboard'), false);

    $this->actingAs($owner)->get(route('owner.inventory-hub', ['from' => 'thesis-binding']))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('Thesis Binding')
        ->assertSee(route('owner.thesis-binding'), false);

    $this->actingAs($owner)->get(route('owner.inventory-hub', ['from' => 'document-printing']))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('Document Printing')
        ->assertSee(route('owner.document-printing'), false);

    $this->actingAs($owner)->get(route('owner.document-printing'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('App Launcher')
        ->assertSee(route('owner.dashboard'), false);

    $this->actingAs($owner)->get(route('owner.thesis-binding'))
        ->assertOk()
        ->assertSee('data-test="universal-back-link"', false)
        ->assertSee('App Launcher')
        ->assertSee(route('owner.dashboard'), false);

    $this->actingAs($owner)->get(route('staff.dashboard'))
        ->assertRedirect(route('owner.dashboard'));

    Livewire::actingAs($owner)->test('pages::staff.⚡staff-dashboard')
        ->assertSeeHtml('data-test="universal-back-link"')
        ->assertSeeHtml(route('owner.dashboard'));

    $this->actingAs($staff)->get(route('staff.dashboard'))
        ->assertOk()
        ->assertDontSee('data-test="universal-back-link"', false);

    foreach (['customer.shop.checkout', 'customer.shop.order-document', 'customer.shop.order-thesis'] as $routeName) {
        $this->actingAs($customer)->get(route($routeName, $shop->id))
            ->assertOk()
            ->assertSee('data-test="universal-back-link"', false)
            ->assertSee('Kabacan Academic Press')
            ->assertSee(route('shops.show', $shop->id), false);
    }
});

test('operational blade views contain zero UI emojis and blank layout omits forced dark class', function () {
    $directory = new RecursiveDirectoryIterator(resource_path('views/pages'));
    $iterator = new RecursiveIteratorIterator($directory);
    $violations = [];

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname()) ?: '';
        if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]|✕|✓/u', $contents) === 1) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBe([]);

    $blankLayout = file_get_contents(resource_path('views/layouts/blank.blade.php')) ?: '';
    expect($blankLayout)->not->toContain('<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}" class="dark">');
});
