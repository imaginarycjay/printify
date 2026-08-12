<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('owner/setup', 'pages::owner.⚡owner-wizard')->name('owner.wizard');
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

            return redirect()->route('dashboard');
        }

        return back();
    })->name('dev.login');
}

require __DIR__.'/settings.php';
