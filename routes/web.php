<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
        Route::livewire('order/thesis-binding', 'pages::customer.⚡thesis-order-wizard')->name('order-thesis');
        Route::livewire('order/document-printing', 'pages::customer.⚡document-order-wizard')->name('order-document');
    });

    // Backward-compatible direct order aliases (Strictly Customer)
    Route::middleware('role:customer')->group(function () {
        Route::livewire('order/thesis-binding', 'pages::customer.⚡thesis-order-wizard')->name('order.thesis-binding');
        Route::livewire('order/document-printing', 'pages::customer.⚡document-order-wizard')->name('order.document-printing');
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
