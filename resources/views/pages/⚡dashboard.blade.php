<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

<div class="min-h-screen w-full bg-stone-950 font-sans antialiased text-stone-100 selection:bg-amber-500 selection:text-white">
    @php
        $user = auth()->user();
    @endphp

    @if ($user?->isOwner())
        @if ($user->printShop && $user->printShop->is_setup_completed)
            @livewire('pages::owner.⚡owner-dashboard')
        @else
            @livewire('pages::owner.⚡owner-wizard')
        @endif
    @elseif ($user?->isStaff())
        @livewire('pages::staff.⚡staff-dashboard')
    @else
        @livewire('pages::customer.⚡customer-dashboard')
    @endif

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</div>
