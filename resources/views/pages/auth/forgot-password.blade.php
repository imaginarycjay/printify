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
                class="mt-1 w-full !bg-blue-600 !text-white font-semibold hover:!bg-blue-700 dark:hover:!bg-blue-500"
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
                class="font-semibold !text-blue-700 hover:!text-blue-800 dark:!text-blue-400 dark:hover:!text-blue-300"
                :href="route('login')"
            >
                {{ __('Back to sign in') }}
            </flux:link>
        </div>
    </x-slot:footer>
</x-layouts::auth>
