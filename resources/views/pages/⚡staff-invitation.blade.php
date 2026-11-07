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

<div class="flex flex-col gap-6">
    <div class="space-y-3">
        <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"><flux:icon name="users" class="size-4" /> Production team invitation</span>
        <h1 class="text-2xl font-extrabold tracking-tight">Join your print shop team</h1>
        <p class="text-sm leading-relaxed text-stone-500 dark:text-stone-400">Accept your invitation to access the shop's production queue and update printing progress.</p>
    </div>
    <x-platform-feedback />
    @guest
        <flux:callout icon="information-circle">New staff can create an account below. If you already have a staff account, sign in with the invited email, then reopen this invitation.</flux:callout>
    @else
        <p class="rounded-xl border border-stone-200 p-4 text-sm dark:border-stone-800">Signed in as <strong>{{ auth()->user()->email }}</strong>. This must match the email receiving the invitation.</p>
    @endguest
    <form method="POST" action="{{ route('staff.invitation.accept', $this->token) }}" class="space-y-4">
        @csrf
        @guest
            <flux:input name="name" label="Full name" value="{{ old('name') }}" autocomplete="name" required />
            <flux:input type="password" name="password" label="Password" autocomplete="new-password" required viewable />
            <flux:input type="password" name="password_confirmation" label="Confirm password" autocomplete="new-password" required viewable />
        @endguest
        <flux:button type="submit" variant="primary" class="w-full !bg-amber-500 !text-stone-950">Accept invitation <flux:icon name="arrow-right" class="ms-2 size-4" /></flux:button>
    </form>
    @guest
        <p class="text-center text-sm text-stone-500 dark:text-stone-400">Already have a staff account? <flux:link :href="route('login')" wire:navigate>Sign in</flux:link></p>
    @endguest
    <p class="text-center text-xs text-stone-500 dark:text-stone-400">Invitations expire after 48 hours and can only be accepted once.</p>
</div>
