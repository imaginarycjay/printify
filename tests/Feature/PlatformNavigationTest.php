<?php

use App\Models\DocumentPrintingConfig;
use App\Models\PrintShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $this->shop = PrintShop::create([
        'user_id' => $this->owner->id,
        'name' => 'Campus Print Studio',
        'is_setup_completed' => true,
        'approval_status' => 'approved',
    ]);
    $this->shop->services()->createMany([
        ['service_key' => 'thesis_binding', 'is_active' => true],
        ['service_key' => 'document_printing', 'is_active' => true],
    ]);
});

test('the storefront shows only the selected shops document price', function () {
    DocumentPrintingConfig::create(['print_shop_id' => $this->shop->id, 'page_price_bw_a4' => 3.75]);
    $otherOwner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
    $other = PrintShop::create(['user_id' => $otherOwner->id, 'name' => 'Another Shop']);
    DocumentPrintingConfig::create(['print_shop_id' => $other->id, 'page_price_bw_a4' => 99.99]);

    $this->get(route('shops.show', $this->shop))
        ->assertOk()
        ->assertSee('₱3.75')
        ->assertDontSee('₱99.99')
        ->assertSee(route('customer.shop.order-document', $this->shop), false);
});

test('a storefront pause survives polling and restores ordering when it expires', function () {
    $service = $this->shop->services()->where('service_key', 'document_printing')->firstOrFail();
    $service->update(['pause_reason' => 'Printer maintenance', 'paused_until' => now()->addHour()]);

    $component = Livewire::test('pages::shop', ['shop' => $this->shop->id])
        ->assertSee('Printer maintenance')
        ->assertDontSeeHtml(route('customer.shop.order-document', $this->shop));

    $this->travel(2)->hours();

    $component->call('$refresh')
        ->assertDontSee('Printer maintenance')
        ->assertSeeHtml(route('customer.shop.order-document', $this->shop));
});

test('directory service filters keep customers with shops offering that service', function () {
    $this->shop->services()->where('service_key', 'document_printing')->update(['is_active' => false]);

    $this->get(route('shops.index', ['service' => 'document_printing']))
        ->assertOk()->assertDontSee('Campus Print Studio');
    $this->get(route('shops.index', ['service' => 'thesis_binding']))
        ->assertOk()->assertSee('Campus Print Studio');
});

test('owner settings offer shop controls without guest registration links', function () {
    $this->actingAs($this->owner)->get(route('owner.platform'))
        ->assertOk()
        ->assertSee(route('owner.platform.save'), false)
        ->assertSee(route('owner.staff.invite'), false)
        ->assertSee(route('shops.show', $this->shop), false)
        ->assertDontSee(route('owner.register'), false);

    $this->shop->update(['approval_status' => 'rejected', 'rejection_reason' => 'Please complete the address.']);
    $this->actingAs($this->owner->fresh());

    $this->get(route('owner.platform'))
        ->assertSee('Please complete the address.')
        ->assertDontSee(route('shops.show', $this->shop), false);
});

test('application review filters by status and excludes actions for incomplete setup', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->shop->update(['approval_status' => 'pending', 'is_setup_completed' => false]);

    $this->actingAs($admin)->get(route('admin.shops'))
        ->assertOk()->assertSee('Campus Print Studio')
        ->assertDontSee(route('admin.shops.review', $this->shop), false);

    $this->get(route('admin.shops', ['status' => 'approved']))
        ->assertOk()->assertDontSee('Campus Print Studio');

    $this->shop->update(['is_setup_completed' => true]);
    $this->get(route('admin.shops'))->assertSee(route('admin.shops.review', $this->shop), false);
});

test('customer service discovery sends customers to shop selection without fixed prices', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_CUSTOMER]))->get(route('customer.dashboard'))
        ->assertOk()
        ->assertSee(route('shops.index'), false)
        ->assertSee(route('customer.order-thesis'), false)
        ->assertSee(route('customer.order-document'), false)
        ->assertDontSee('₱350.00')
        ->assertDontSee('₱1.50');
});

test('owner and invitation forms retain their submission destinations in the auth layout', function () {
    $this->get(route('owner.register'))
        ->assertOk()
        ->assertSee(route('owner.register.store'), false)
        ->assertSee('data-test="theme-toggle"', false);

    $this->get(route('staff.invitation', 'example-token'))
        ->assertOk()
        ->assertSee(route('staff.invitation.accept', 'example-token'), false)
        ->assertSee('data-test="theme-toggle"', false);
});

test('service configuration and the builder lead back to shop management', function () {
    $this->actingAs($this->owner)->get(route('owner.document-printing'))
        ->assertOk()->assertSee(route('owner.platform').'#availability', false);
    $this->get(route('owner.thesis-binding'))
        ->assertOk()->assertSee(route('owner.platform').'#availability', false);
    $this->get(route('owner.web-builder'))
        ->assertOk()->assertSee(route('shops.show', $this->shop), false);
});

test('invalid availability changes preserve input only for the submitted service', function () {
    $service = $this->shop->services()->where('service_key', 'document_printing')->firstOrFail();

    $response = $this->actingAs($this->owner)->from(route('owner.platform'))->followingRedirects()->post(route('owner.availability', $service), [
        'availability_service' => $service->id,
        'capacity' => 37,
        'pause_reason' => 'Replace damaged printer rollers',
        'paused_until' => now()->subHour()->format('Y-m-d\TH:i'),
    ])->assertOk()
        ->assertSee('The paused until field must be a date after now.')
        ->assertSee('value="37"', false)
        ->assertSee('Replace damaged printer rollers');

    expect(substr_count($response->getContent(), 'Replace damaged printer rollers'))->toBe(1);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->evaluate('count(//form[@action="'.route('owner.availability', $service).'"]//details[@open])'))->toBe(1.0);
});
