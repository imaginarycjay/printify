<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-4">
        <x-auth-header
            :title="__('Reset password')"
            :description="__('Choose a new password for your account.')"
        />

        <x-auth-feedback />

        <form
            method="POST"
            action="{{ route('password.update') }}"
            class="flex flex-col gap-3.5"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            @pageshow.window="submitting = false"
        >
            @csrf

            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <flux:field>
                <flux:label>{{ __('Email address') }}</flux:label>
                <flux:input
                    name="email"
                    value="{{ old('email', request('email')) }}"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Create a new password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Confirm password') }}</flux:label>
                <flux:input
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Re-enter your password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>

            <flux:button
                type="submit"
                variant="primary"
                data-theme-keep
                class="mt-1 w-full !bg-amber-500 !text-stone-950 font-semibold hover:!bg-amber-400"
                data-test="reset-password-button"
                x-bind:disabled="submitting"
            >
                {{ __('Reset password') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
