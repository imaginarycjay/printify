<?php

use App\Http\Controllers\PlatformController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('shops', 'pages::shops')->name('shops.index');
Route::livewire('shops/{shop}', 'pages::shop')->whereNumber('shop')->name('shops.show');
Route::livewire('register/shop', 'pages::owner-register')->middleware('guest')->name('owner.register');
Route::post('register/shop', [PlatformController::class, 'registerOwner'])->middleware(['guest', 'throttle:6,1'])->name('owner.register.store');
Route::livewire('staff-invitations/{token}', 'pages::staff-invitation')->name('staff.invitation');
Route::post('staff-invitations/{token}', [PlatformController::class, 'accept'])->middleware('throttle:6,1')->name('staff.invitation.accept');
Route::middleware(['auth', 'role:platform_admin'])->group(function (): void {
    Route::livewire('admin/shops', 'pages::admin-shops')->name('admin.shops');
    Route::post('admin/shops/{shop}', [PlatformController::class, 'review'])->name('admin.shops.review');
});
Route::middleware(['auth', 'role:business_owner'])->group(function (): void {
    Route::livewire('owner/platform', 'pages::owner-platform')->name('owner.platform');
    Route::post('owner/platform', [PlatformController::class, 'saveShop'])->name('owner.platform.save');
    Route::post('owner/services/{service}/availability', [PlatformController::class, 'availability'])->name('owner.availability');
    Route::post('owner/staff/invite', [PlatformController::class, 'invite'])->name('owner.staff.invite');
    Route::delete('owner/staff/{staff}', [PlatformController::class, 'revoke'])->name('owner.staff.revoke');
});
Route::get('orders/{order}/files/{kind}/{item?}', [PlatformController::class, 'file'])->middleware('auth')->name('orders.file');

Route::middleware(['auth', 'verified'])->group(function () {
    // Smart Single Sign-On (SSO) Role Redirector
    Route::livewire('dashboard', 'pages::⚡dashboard')->name('dashboard');

    // Business Owner Routes (Strictly Restricted to Business Owner)
    Route::prefix('owner')->name('owner.')->middleware('role:business_owner')->group(function () {
        Route::livewire('dashboard', 'pages::owner.⚡owner-dashboard')->name('dashboard');
        Route::livewire('setup', 'pages::owner.⚡owner-wizard')->name('wizard');
        Route::livewire('analytics', 'pages::owner.⚡analytics-hub')->name('analytics-hub');
        Route::livewire('web-builder', 'pages::owner.⚡web-builder')->name('web-builder');
        Route::livewire('inventory', 'pages::owner.⚡inventory-hub')->name('inventory-hub');
        Route::livewire('thesis-binding', 'pages::owner.⚡thesis-binding')->name('thesis-binding');
        Route::livewire('document-printing', 'pages::owner.⚡document-printing')->name('document-printing');
    });

    // Production Staff Routes (Strictly Restricted to Production Staff)
    Route::prefix('staff')->name('staff.')->middleware('role:production_staff')->group(function () {
        Route::livewire('dashboard', 'pages::staff.⚡staff-dashboard')->name('dashboard');
        Route::livewire('production', 'pages::staff.⚡staff-dashboard')->name('production-hub');
    });

    // Customer Routes (Strictly Restricted to Customer)
    Route::prefix('customer')->name('customer.')->middleware('role:customer')->group(function () {
        Route::livewire('dashboard', 'pages::customer.⚡customer-dashboard')->name('dashboard');
        Route::livewire('shops/{shop}/order/thesis', 'pages::customer.⚡thesis-order-wizard')->whereNumber('shop')->name('shop.order-thesis');
        Route::get('order/thesis-binding', fn () => redirect()->route('shops.index', ['service' => 'thesis_binding']))->name('order-thesis');
        Route::livewire('shops/{shop}/order/document', 'pages::customer.⚡document-order-wizard')->whereNumber('shop')->name('shop.order-document');
        Route::get('order/document-printing', fn () => redirect()->route('shops.index', ['service' => 'document_printing']))->name('order-document');
    });

    // Backward-compatible direct order aliases (Strictly Customer)
    Route::middleware('role:customer')->group(function () {
        Route::get('order/thesis-binding', fn () => redirect()->route('shops.index', ['service' => 'thesis_binding']))->name('order.thesis-binding');
        Route::get('order/document-printing', fn () => redirect()->route('shops.index', ['service' => 'document_printing']))->name('order.document-printing');
    });
});

if (app()->isLocal()) {
    Route::post('/dev-login/{role}', function (string $role) {
        $validRoles = [User::ROLE_BUSINESS_OWNER, User::ROLE_PRODUCTION_STAFF, User::ROLE_CUSTOMER];
        if (! in_array($role, $validRoles, true)) {
            return back();
        }

        $user = User::where('role', $role)->first();
        if ($user) {
            auth()->login($user);

            return match ($role) {
                User::ROLE_BUSINESS_OWNER => redirect()->route('owner.dashboard'),
                User::ROLE_PRODUCTION_STAFF => redirect()->route('staff.dashboard'),
                default => redirect()->route('customer.dashboard'),
            };
        }

        return back();
    })->name('dev.login');
}

require __DIR__.'/settings.php';
