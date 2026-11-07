<?php

use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.auth', ['title' => 'Register your print shop']);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="space-y-3">
        <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"><flux:icon name="building-storefront" class="size-4" /> For shop owners</span>
        <h1 class="text-2xl font-extrabold tracking-tight">Register your print shop</h1>
        <p class="text-sm leading-relaxed text-stone-500 dark:text-stone-400">Create your owner account, configure your shop's services, then complete your profile for approval.</p>
    </div>
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm leading-relaxed text-amber-800 dark:text-amber-300">Your shop will appear in the directory after setup and administrator approval. Production staff join through invitations.</div>
    <x-platform-feedback />
    <form method="POST" action="{{ route('owner.register.store') }}" class="space-y-4">
        @csrf
        <flux:input name="name" label="Full name" value="{{ old('name') }}" autocomplete="name" required autofocus />
        <flux:input name="email" type="email" label="Email" value="{{ old('email') }}" autocomplete="email" required />
        <flux:input name="password" type="password" label="Password" autocomplete="new-password" required viewable />
        <flux:input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required viewable />
        <flux:button type="submit" variant="primary" class="w-full !bg-amber-500 !text-stone-950">Create owner account <flux:icon name="arrow-right" class="ms-2 size-4" /></flux:button>
    </form>
    <p class="text-center text-sm text-stone-500 dark:text-stone-400">Already registered? <flux:link :href="route('login')" wire:navigate>Sign in</flux:link></p>
    <div class="border-t border-stone-200 pt-4 text-center text-sm text-stone-500 dark:border-stone-800 dark:text-stone-400">Here to place an order? <flux:link :href="route('register')" wire:navigate>Create a customer account</flux:link></div>
</div>
