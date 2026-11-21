@php
    $user = auth()->user();
    $shopName = $user?->printShop?->name;
    $description = $shopName
        ? __('Confirm your password to continue managing :shop.', ['shop' => $shopName])
        : __('This is a secure area of PressQueue. Please confirm your password before continuing.');
@endphp

<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-4">
        <x-auth-header
            :title="__('Confirm your password')"
            :description="$description"
        />

        <x-auth-feedback />

        <form
            method="POST"
            action="{{ route('password.confirm.store') }}"
            class="flex flex-col gap-3.5"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            @pageshow.window="submitting = false"
        >
            @csrf

            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    :placeholder="__('Enter your account password')"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:button
                variant="primary"
                type="submit"
                data-theme-keep
                class="mt-1 w-full !bg-amber-500 !text-stone-950 font-semibold hover:!bg-amber-400"
                data-test="confirm-password-button"
                x-bind:disabled="submitting"
            >
                {{ __('Confirm password') }}
            </flux:button>
        </form>

        <x-passkey-verify
            position="below"
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirm with passkey')"
            :loading-label="__('Confirming...')"
            :separator="__('Or')"
        />
    </div>

    <x-slot:footer>
        <div class="text-center text-sm">
            <a
                href="{{ route('dashboard') }}"
                data-theme-keep
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-600 transition-colors hover:text-amber-700 dark:text-stone-300 dark:hover:text-amber-400"
            >
                <flux:icon name="arrow-left" class="size-3.5" />
                <span>{{ __('Cancel and return to dashboard') }}</span>
            </a>
        </div>
    </x-slot:footer>
</x-layouts::auth>
