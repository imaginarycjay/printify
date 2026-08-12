<x-layouts::auth :title="__('Sign In')">
    <div class="flex flex-col gap-6">
        <!-- Auth Header & Navigation Pills -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <flux:icon name="arrow-right-end-on-rectangle" class="size-4" />
                    </span>
                    <span class="text-sm font-bold text-stone-900 dark:text-stone-100 uppercase tracking-wide">Portal Sign In</span>
                </div>
                <div class="flex items-center gap-1 rounded-lg bg-stone-100 p-1 dark:bg-stone-800/60">
                    <a href="{{ route('login') }}" class="rounded-md bg-white px-3 py-1 text-xs font-semibold text-stone-900 shadow-xs dark:bg-stone-950 dark:text-white" wire:navigate>
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('register') }}" class="rounded-md px-3 py-1 text-xs font-medium text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white" wire:navigate>
                        {{ __('Register') }}
                    </a>
                </div>
            </div>

            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    {{ __('Welcome Back') }}
                </h1>
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    {{ __('Enter your credentials to access your printing account dashboard') }}
                </p>
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:field>
                <flux:label>{{ __('Email Address') }}</flux:label>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    icon="envelope"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="name@company.com"
                />
                <flux:error name="email" />
            </flux:field>

            <!-- Password -->
            <flux:field>
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Password') }}</flux:label>
                    @if (Route::has('password.request'))
                        <flux:link class="text-xs text-amber-600 hover:text-amber-500 dark:text-amber-400" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot password?') }}
                        </flux:link>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    icon="key"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <flux:checkbox name="remember" :label="__('Keep me signed in')" :checked="old('remember')" />
            </div>

            <!-- Submit Button -->
            <flux:button variant="primary" type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-stone-950 font-semibold shadow-md shadow-amber-500/20" data-test="login-button">
                {{ __('Sign In to Dashboard') }}
            </flux:button>
        </form>

        <!-- Footer link -->
        <div class="text-xs text-center text-stone-500 dark:text-stone-400 border-t border-stone-100 dark:border-stone-800/60 pt-4">
            <span>{{ __('Don\'t have a customer account yet?') }}</span>
            <flux:link class="font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-400 ms-1" :href="route('register')" wire:navigate>
                {{ __('Create account') }}
            </flux:link>
        </div>

        <!-- Dev Role Switcher for 1-Click Role Logins -->
        <x-dev-role-switcher />
    </div>
</x-layouts::auth>
