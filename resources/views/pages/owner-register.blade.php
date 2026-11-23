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
            class="flex min-h-9 items-center justify-center rounded-lg px-3 text-xs font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
        >
            {{ __('Customer') }}
        </a>
        <a
            href="{{ route('owner.register') }}"
            aria-current="page"
            data-theme-keep
            class="flex min-h-9 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-900 shadow-2xs dark:bg-slate-700/90 dark:text-white"
        >
            {{ __('Print shop owner') }}
        </a>
    </nav>

    <x-auth-header
        :title="__('Register your print shop')"
        :description="__('Create your owner account first, then configure your shop profile and services for approval.')"
    />

    <p data-theme-keep class="rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-xs leading-relaxed text-blue-900 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-200">
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
            class="mt-1 w-full !bg-blue-600 !text-white font-semibold hover:!bg-blue-700 dark:hover:!bg-blue-500"
            x-bind:disabled="submitting"
        >
            {{ __('Create owner account') }}
        </flux:button>
    </form>

    <div data-theme-keep class="-mx-6 -mb-6 mt-2 border-t border-[var(--auth-border-subtle)] bg-[var(--auth-card-footer)] px-6 py-4 text-center text-sm text-slate-600 dark:text-slate-300 sm:-mx-7 sm:-mb-7 sm:px-7">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link
            data-theme-keep
            class="ms-1 font-semibold !text-blue-700 hover:!text-blue-800 dark:!text-blue-400 dark:hover:!text-blue-300"
            :href="route('login')"
        >
            {{ __('Sign in') }}
        </flux:link>
    </div>
</div>
