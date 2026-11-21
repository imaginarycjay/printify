<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-4">
        <x-auth-header
            :title="__('Forgot password')"
            :description="__('Enter your email address and we’ll send you a link to reset your password.')"
        />

        <x-auth-feedback />

        <form
            method="POST"
            action="{{ route('password.email') }}"
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
                <flux:error name="email" />
            </flux:field>

            <flux:button
                variant="primary"
                type="submit"
                data-theme-keep
                class="mt-1 w-full !bg-amber-500 !text-stone-950 font-semibold hover:!bg-amber-400"
                data-test="email-password-reset-link-button"
                x-bind:disabled="submitting"
            >
                {{ __('Email password reset link') }}
            </flux:button>
        </form>
    </div>

    <x-slot:footer>
        <div class="text-center text-sm">
            <flux:link
                data-theme-keep
                class="font-semibold !text-amber-700 hover:!text-amber-800 dark:!text-amber-400 dark:hover:!text-amber-300"
                :href="route('login')"
            >
                {{ __('Back to sign in') }}
            </flux:link>
        </div>
    </x-slot:footer>
</x-layouts::auth>
