@php
    $user = auth()->user();
    $shop = $user?->printShop;
    $shopName = $shop?->name ?? 'Print Shop';
@endphp

<x-layouts::blank>
    <div class="h-screen w-screen bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-center items-center p-4 relative selection:bg-amber-500 selection:text-white overflow-hidden">
        <!-- Ambient Glow Background Effects -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>
        <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

        <div class="w-full max-w-md space-y-5 relative z-10 my-auto">
            <!-- Header Section: Printify X Shop Name -->
            <div class="text-center space-y-2">
                <!-- App Logo & Name -->
                <div class="flex items-center justify-center gap-2.5">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-lg shadow-amber-500/20">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="text-2xl font-extrabold tracking-tight text-white">
                        {{ config('app.name', 'Printify') }}
                    </span>
                </div>

                <!-- Bigger Yellow X Separator (No Circle) -->
                <div class="flex items-center justify-center py-0.5">
                    <span class="text-xl sm:text-2xl font-black text-amber-400 tracking-widest select-none">
                        ✕
                    </span>
                </div>

                <!-- Print Shop Name in Yellow -->
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-amber-400 tracking-tight">
                        {{ $shopName }}
                    </h1>
                    <p class="text-xs text-stone-400">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </p>
                </div>
            </div>

            <!-- Card Container -->
            <div class="rounded-3xl border border-stone-800 bg-stone-900/90 p-6 sm:p-7 shadow-2xl backdrop-blur-xl space-y-5">
                <x-auth-session-status class="text-center" :status="session('status')" />

                <x-passkey-verify
                    options-route="passkey.confirm-options"
                    submit-route="passkey.confirm"
                    :label="__('Confirm with passkey')"
                    :loading-label="__('Confirming...')"
                    :separator="__('Or confirm with password')"
                />

                <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Enter your account password')"
                            viewable
                        />
                    </div>

                    <flux:button variant="primary" type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-stone-950 font-extrabold shadow-md shadow-amber-500/20 py-2.5 transition-all" data-test="confirm-password-button">
                        {{ __('Confirm Password') }}
                    </flux:button>
                </form>

                <div class="text-center pt-2 border-t border-stone-800/80">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-stone-400 hover:text-amber-400 transition-colors inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-left" class="size-3.5" />
                        <span>Cancel &amp; Return to Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts::blank>
