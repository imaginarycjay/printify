<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-4">
        <x-auth-header
            :title="__('Verify your email')"
            :description="__('Click the verification link we emailed to you to finish setting up your account.')"
        />

        <x-auth-feedback />

        <form
            method="POST"
            action="{{ route('verification.send') }}"
            class="w-full"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            @pageshow.window="submitting = false"
        >
            @csrf
            <flux:button
                type="submit"
                variant="primary"
                data-theme-keep
                class="w-full !bg-blue-600 !text-white font-semibold hover:!bg-blue-700 dark:hover:!bg-blue-500"
                x-bind:disabled="submitting"
            >
                {{ __('Resend verification email') }}
            </flux:button>
        </form>
    </div>

    <x-slot:footer>
        <form method="POST" action="{{ route('logout') }}" class="flex justify-center">
            @csrf
            <flux:button variant="ghost" type="submit" class="cursor-pointer text-sm" data-test="logout-button">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </x-slot:footer>
</x-layouts::auth>
