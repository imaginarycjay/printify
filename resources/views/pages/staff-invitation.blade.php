<?php

use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.auth', ['title' => 'Join your print shop team']);
    }
}; ?>

<div class="flex flex-col gap-4">
    <x-auth-header
        :title="__('Join your print shop team')"
        :description="__('Accept your invitation to access the shop’s production queue and update printing progress.')"
    />

    <x-auth-feedback />

    @guest
        <flux:callout icon="information-circle">
            {{ __('New staff can create an account below. If you already have a staff account, sign in with the invited email, then reopen this invitation.') }}
        </flux:callout>
    @else
        <p data-theme-keep class="rounded-xl border border-[var(--auth-border)] bg-[var(--auth-input-bg)] p-3.5 text-sm text-slate-700 dark:text-slate-300">
            {{ __('Signed in as') }} <strong>{{ auth()->user()->email }}</strong>. {{ __('This must match the email receiving the invitation.') }}
        </p>
    @endguest

    <form
        method="POST"
        action="{{ route('staff.invitation.accept', $this->token) }}"
        class="flex flex-col gap-3.5"
        x-data="{ submitting: false }"
        @submit="submitting = true"
        @pageshow.window="submitting = false"
    >
        @csrf

        @guest
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
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    type="password"
                    name="password"
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
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Re-enter your password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>
        @endguest

        <flux:button
            type="submit"
            variant="primary"
            icon-trailing="arrow-right"
            data-theme-keep
            class="mt-1 w-full !bg-blue-600 !text-white font-semibold hover:!bg-blue-700 dark:hover:!bg-blue-500"
            x-bind:disabled="submitting"
        >
            {{ __('Accept invitation') }}
        </flux:button>
    </form>

    <div data-theme-keep class="-mx-6 -mb-6 mt-2 space-y-1.5 border-t border-[var(--auth-border-subtle)] bg-[var(--auth-card-footer)] px-6 py-4 text-center text-sm text-slate-600 dark:text-slate-300 sm:-mx-7 sm:-mb-7 sm:px-7">
        @guest
            <p>
                <span>{{ __('Already have a staff account?') }}</span>
                <flux:link
                    data-theme-keep
                    class="ms-1 font-semibold !text-blue-700 hover:!text-blue-800 dark:!text-blue-400 dark:hover:!text-blue-300"
                    :href="route('login')"
                >
                    {{ __('Sign in') }}
                </flux:link>
            </p>
        @endguest
        <p data-theme-keep class="text-xs text-slate-500 dark:text-slate-400">
            {{ __('Invitations expire after 48 hours and can only be accepted once.') }}
        </p>
    </div>
</div>
