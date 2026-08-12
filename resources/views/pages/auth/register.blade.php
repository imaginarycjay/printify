<x-layouts::auth :title="__('Create Account')">
    <div class="flex flex-col gap-6">
        <!-- Auth Header & Navigation Pills -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <flux:icon name="user-plus" class="size-4" />
                    </span>
                    <span class="text-sm font-bold text-stone-900 dark:text-stone-100 uppercase tracking-wide">Customer Registration</span>
                </div>
                <div class="flex items-center gap-1 rounded-lg bg-stone-100 p-1 dark:bg-stone-800/60">
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-1 text-xs font-medium text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white" wire:navigate>
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('register') }}" class="rounded-md bg-white px-3 py-1 text-xs font-semibold text-stone-900 shadow-xs dark:bg-stone-950 dark:text-white" wire:navigate>
                        {{ __('Register') }}
                    </a>
                </div>
            </div>

            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    {{ __('Create Customer Account') }}
                </h1>
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    {{ __('Sign up to order custom printing services, track job status, and manage specs') }}
                </p>
            </div>
        </div>

        <!-- Role Info Banner -->
        <div class="flex items-center gap-3 rounded-xl bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300 border border-amber-500/20">
            <flux:icon name="information-circle" class="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
            <span>Public registration creates a <strong>Customer Account</strong>. Business Owner and Production Staff accounts are configured during setup.</span>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Name -->
            <flux:field>
                <flux:label>{{ __('Full Name') }}</flux:label>
                <flux:input
                    name="name"
                    :value="old('name')"
                    type="text"
                    icon="user"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Juan Dela Cruz"
                />
                <flux:error name="name" />
            </flux:field>

            <!-- Email Address -->
            <flux:field>
                <flux:label>{{ __('Email Address') }}</flux:label>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    icon="envelope"
                    required
                    autocomplete="email"
                    placeholder="name@example.com"
                />
                <flux:error name="email" />
            </flux:field>

            <!-- Password -->
            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    name="password"
                    type="password"
                    icon="key"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <!-- Confirm Password -->
            <flux:field>
                <flux:label>{{ __('Confirm Password') }}</flux:label>
                <flux:input
                    name="password_confirmation"
                    type="password"
                    icon="key"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>

            <!-- Submit Button -->
            <flux:button variant="primary" type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-stone-950 font-semibold shadow-md shadow-amber-500/20 mt-2" data-test="register-user-button">
                {{ __('Create Customer Account') }}
            </flux:button>
        </form>

        <!-- Footer Link -->
        <div class="text-xs text-center text-stone-500 dark:text-stone-400 border-t border-stone-100 dark:border-stone-800/60 pt-4">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link class="font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-400 ms-1" :href="route('login')" wire:navigate>
                {{ __('Log in') }}
            </flux:link>
        </div>

        <!-- Dev Role Switcher -->
        <x-dev-role-switcher />
    </div>
</x-layouts::auth>
