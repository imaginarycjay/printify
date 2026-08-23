<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public function mount(): mixed
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isOwner()) {
            if ($user->printShop && $user->printShop->is_setup_completed) {
                return redirect()->route('owner.dashboard');
            }

            return redirect()->route('owner.wizard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        return redirect()->route('customer.dashboard');
    }
}; ?>

<div class="min-h-screen w-full bg-stone-950 flex items-center justify-center">
    <div class="flex items-center gap-2 text-stone-400 text-xs font-bold">
        <flux:icon name="arrow-path" class="size-4 animate-spin text-amber-500" />
        <span>Redirecting to your workspace...</span>
    </div>
</div>
