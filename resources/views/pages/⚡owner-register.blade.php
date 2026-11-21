<?php

use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.auth', ['title' => 'Register your print shop']);
    }
}; ?>

<div class="flex flex-col gap-4">
    <nav aria-label="{{ __('Account type') }}" data-theme-keep class="grid grid-cols-2 gap-1 rounded-xl border border-[var(--auth-border)] bg-[var(--auth-input-bg)] p-1">
        <a
            href="{{ route('register') }}"
            data-theme-keep
            class="flex min-h-9 items-center justify-center rounded-lg px-3 text-xs font-medium text-stone-600 transition-colors hover:text-stone-900 dark:text-stone-300 dark:hover:text-white"
        >
            {{ __('Customer') }}
        </a>
        <a
            href="{{ route('owner.register') }}"
            aria-current="page"
            data-theme-keep
            class="flex min-h-9 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-stone-900 shadow-2xs dark:bg-stone-700/90 dark:text-white"
        >
            {{ __('Print shop owner') }}
        </a>
    </nav>

    <x-auth-header
        :title="__('Register your print shop')"
        :description="__('Create your owner account first, then configure your shop profile and services for approval.')"
    />

    <p data-theme-keep class="rounded-xl border border-amber-500/25 bg-amber-500/10 px-3.5 py-2.5 text-xs leading-relaxed text-amber-900 dark:text-amber-200">
        {{ __('Your shop appears in the public directory after you complete setup and receive administrator approval.') }}
    </p>

    <x-auth-feedback />

    <form
        method="POST"
        action="{{ route('owner.register.store') }}"
        class="flex flex-col gap-3.5"
        x-data="{ submitting: false }"
        @submit="submitting = true"
        @pageshow.window="submitting = false"
    >
        @csrf

        <flux:field>
            <flux:label>{{ __('Full name') }}</flux:label>
            <flux:input
                name="name"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Juan Dela Cruz"
            />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Email address') }}</flux:label>
            <flux:input
                name="email"
                :value="old('email')"
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
                :placeholder="__('Create a password')"
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
            icon-trailing="arrow-right"
            data-theme-keep
            class="mt-1 w-full !bg-amber-500 !text-stone-950 font-semibold hover:!bg-amber-400"
            x-bind:disabled="submitting"
        >
            {{ __('Create owner account') }}
        </flux:button>
    </form>

    <div data-theme-keep class="-mx-6 -mb-6 mt-2 border-t border-[var(--auth-border-subtle)] bg-[var(--auth-card-footer)] px-6 py-4 text-center text-sm text-stone-600 dark:text-stone-300 sm:-mx-7 sm:-mb-7 sm:px-7">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link
            data-theme-keep
            class="ms-1 font-semibold !text-amber-700 hover:!text-amber-800 dark:!text-amber-400 dark:hover:!text-amber-300"
            :href="route('login')"
        >
            {{ __('Sign in') }}
        </flux:link>
    </div>
</div>
