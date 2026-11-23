<x-layouts::auth :title="__('Two-factor authentication')">
    <div
        class="flex flex-col gap-4"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            submitting: false,
            focusOtp() {
                this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
            },
            init() {
                if (! this.showRecoveryInput) {
                    this.focusOtp();
                }
            },
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;

                this.code = '';
                this.recovery_code = '';

                $nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : this.focusOtp();
                });
            },
        }"
        @pageshow.window="submitting = false"
    >
        <div x-show="!showRecoveryInput">
            <x-auth-header
                :title="__('Authentication code')"
                :description="__('Enter the 6-digit code from your authenticator app to finish signing in.')"
            />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth-header
                :title="__('Recovery code')"
                :description="__('Enter one of your emergency recovery codes to verify access to your account.')"
            />
        </div>

        <x-auth-feedback />

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-4" @submit="submitting = true">
            @csrf

            <div x-show="!showRecoveryInput" class="flex flex-col items-center gap-2">
                <div class="flex items-center justify-center py-1" x-ref="otp">
                    <flux:otp
                        x-model="code"
                        length="6"
                        name="code"
                        label="OTP Code"
                        label:sr-only
                        class="mx-auto"
                    />
                </div>
                @error('code')
                    <p class="text-center text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="showRecoveryInput" class="flex flex-col gap-2">
                <flux:field>
                    <flux:label>{{ __('Recovery code') }}</flux:label>
                    <flux:input
                        type="text"
                        name="recovery_code"
                        x-ref="recovery_code"
                        x-bind:required="showRecoveryInput"
                        autocomplete="one-time-code"
                        x-model="recovery_code"
                        placeholder="xxxxx-xxxxx"
                    />
                    <flux:error name="recovery_code" />
                </flux:field>
            </div>

            <flux:button
                variant="primary"
                type="submit"
                data-theme-keep
                class="w-full !bg-blue-600 !text-white font-semibold hover:!bg-blue-700 dark:hover:!bg-blue-500"
                x-bind:disabled="submitting"
            >
                {{ __('Continue') }}
            </flux:button>
        </form>

        <div data-theme-keep class="-mx-6 -mb-6 mt-2 border-t border-[var(--auth-border-subtle)] bg-[var(--auth-card-footer)] px-6 py-4 text-center text-sm sm:-mx-7 sm:-mb-7 sm:px-7">
            <button
                type="button"
                @click="toggleInput()"
                data-theme-keep
                class="cursor-pointer font-semibold text-blue-700 underline underline-offset-4 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                <span x-show="!showRecoveryInput">{{ __('Use a recovery code') }}</span>
                <span x-show="showRecoveryInput">{{ __('Use an authentication code') }}</span>
            </button>
        </div>
    </div>
</x-layouts::auth>
