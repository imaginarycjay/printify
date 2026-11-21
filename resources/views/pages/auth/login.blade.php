<x-layouts::auth :title="__('Sign in')">
    <div class="flex flex-col gap-4">
        <x-auth-header
            :title="__('Sign in to PressQueue')"
            :description="__('One sign-in for customers, print shop owners, and production staff — we’ll route you to your workspace.')"
        />

        <x-auth-feedback />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="flex flex-col gap-3.5"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            @pageshow.window="submitting = false"
        >
            @csrf

            <flux:field>
                <flux:label>{{ __('Email address') }}</flux:label>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="you@example.com"
                />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between gap-2">
                    <flux:label>{{ __('Password') }}</flux:label>
                    @if (Route::has('password.request'))
                        <flux:link
                            data-theme-keep
                            class="text-xs font-semibold !text-amber-700 hover:!text-amber-800 dark:!text-amber-400 dark:hover:!text-amber-300"
                            :href="route('password.request')"
                        >
                            {{ __('Forgot password?') }}
                        </flux:link>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:checkbox name="remember" :label="__('Keep me signed in')" :checked="old('remember')" />

            <flux:button
                variant="primary"
                type="submit"
                data-theme-keep
                class="mt-1 w-full !bg-amber-500 !text-stone-950 font-semibold hover:!bg-amber-400"
                data-test="login-button"
                x-bind:disabled="submitting"
            >
                {{ __('Sign in') }}
            </flux:button>
        </form>

        <div class="relative my-0.5">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-[var(--auth-border)]"></div>
            </div>
            <div class="relative flex justify-center text-xs font-medium uppercase tracking-wider">
                <span data-theme-keep class="bg-[var(--auth-card)] px-3 text-stone-500 dark:text-stone-400">
                    {{ __('Or') }}
                </span>
            </div>
        </div>

        <div
            x-data="{
                googleNotice: false,
                notifyGoogle() {
                    this.googleNotice = true;
                    this.$dispatch('toast-show', {
                        duration: 4500,
                        slots: {
                            heading: @js(__('Coming soon')),
                            text: @js(__('Google sign-in is not enabled yet. Please sign in with your email and password.')),
                        },
                        dataset: {
                            variant: 'info',
                        },
                    });
                },
            }"
            class="space-y-2"
        >
            <button
                type="button"
                @click="notifyGoogle()"
                data-theme-keep
                data-test="google-login-button"
                class="flex h-10 w-full cursor-pointer items-center justify-center gap-2.5 rounded-lg border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 transition-colors hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700/80"
            >
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                <span>{{ __('Continue with Google') }}</span>
            </button>

            <p x-show="googleNotice" x-cloak role="status" aria-live="polite" data-theme-keep class="text-center text-xs text-stone-600 dark:text-stone-400">
                {{ __('Google sign-in is coming soon. Please sign in with your email and password above for now.') }}
            </p>
        </div>
    </div>

    <x-slot:footer>
        <p data-theme-keep class="mb-2.5 text-xs font-semibold text-stone-700 dark:text-stone-300">
            {{ __('New to PressQueue? Create an account') }}
        </p>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <a
                href="{{ route('register') }}"
                data-theme-keep
                class="group flex items-center justify-between gap-2.5 rounded-xl border border-stone-300/90 bg-white px-3 py-2.5 transition-colors hover:border-stone-400 hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:border-stone-700 dark:bg-[#23201d] dark:hover:border-stone-500 dark:hover:bg-[#2b2723]"
            >
                <span class="flex min-w-0 items-center gap-2.5">
                    <flux:icon name="user" class="size-4 shrink-0 text-stone-600 dark:text-stone-300" />
                    <span class="min-w-0">
                        <span data-theme-keep class="block text-xs font-semibold text-stone-900 dark:text-white">{{ __('Customer account') }}</span>
                        <span data-theme-keep class="block truncate text-[11px] text-stone-500 dark:text-stone-400">{{ __('Order printing') }}</span>
                    </span>
                </span>
                <flux:icon name="arrow-right" class="size-3.5 shrink-0 text-stone-400 transition-transform group-hover:translate-x-0.5 group-hover:text-stone-700 dark:group-hover:text-stone-200" />
            </a>

            <a
                href="{{ route('owner.register') }}"
                data-theme-keep
                class="group flex items-center justify-between gap-2.5 rounded-xl border border-amber-500/40 bg-amber-500/[0.07] px-3 py-2.5 transition-colors hover:border-amber-500/80 hover:bg-amber-500/12 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:border-amber-500/45 dark:bg-amber-500/10 dark:hover:border-amber-400 dark:hover:bg-amber-500/15"
            >
                <span class="flex min-w-0 items-center gap-2.5">
                    <flux:icon name="building-storefront" class="size-4 shrink-0 text-amber-700 dark:text-amber-400" />
                    <span class="min-w-0">
                        <span data-theme-keep class="block text-xs font-semibold text-amber-800 dark:text-amber-300">{{ __('Print shop owner') }}</span>
                        <span data-theme-keep class="block truncate text-[11px] text-stone-500 dark:text-stone-400">{{ __('Register your shop') }}</span>
                    </span>
                </span>
                <flux:icon name="arrow-right" class="size-3.5 shrink-0 text-amber-700 transition-transform group-hover:translate-x-0.5 dark:text-amber-400" />
            </a>
        </div>

        <p data-theme-keep class="mt-2.5 text-xs text-stone-600 dark:text-stone-400">
            {{ __('Production staff join using an invitation link sent by their shop owner.') }}
        </p>
    </x-slot:footer>
</x-layouts::auth>
