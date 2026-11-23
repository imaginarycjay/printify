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

        $suspendedShop = $user->suspendedShop();
        if ($suspendedShop !== null) {
            $reason = $suspendedShop->rejection_reason;
            auth()->guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            $message = $reason
                ? "Your print shop account is currently suspended: {$reason}"
                : 'Your print shop account is currently suspended. Contact the platform administrator.';

            return redirect()->route('login')->withErrors([
                \Laravel\Fortify\Fortify::username() => $message,
            ]);
        }

        if ($user->role === \App\Models\User::ROLE_ADMIN) { return redirect()->route('admin.shops'); }

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

<div class="min-h-screen w-full pq-page flex items-center justify-center">
    <div class="flex items-center gap-2 text-stone-600 dark:text-stone-400 text-xs font-bold">
        <flux:icon name="arrow-path" class="size-4 animate-spin text-amber-600 dark:text-amber-500" />
        <span>Redirecting to your workspace...</span>
    </div>
</div>
