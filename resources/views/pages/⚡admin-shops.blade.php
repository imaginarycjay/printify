<?php

use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public string $status = 'pending';

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

<div class="min-h-screen bg-stone-100 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
    <x-platform-header title="Platform Administration" subtitle="Shop applications & review" :back-url="route('home')" />
    <main class="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <x-platform-feedback />
        <div class="space-y-2"><h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Shop applications</h1><p class="text-sm text-stone-500 dark:text-stone-400">Review each shop's setup and contact details before publishing it to the directory.</p></div>
        <nav aria-label="Filter applications" class="flex flex-wrap gap-2">
            @foreach (['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Needs changes', '' => 'All shops'] as $key => $label)
                <a href="{{ route('admin.shops', ['status' => $key]) }}" wire:navigate @if($this->status === $key) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-bold {{ $this->status === $key ? 'border-amber-500 bg-amber-500 text-stone-950' : 'border-stone-200 bg-white text-stone-600 hover:border-amber-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="grid items-start gap-5 lg:grid-cols-2">
            @forelse (App\Models\PrintShop::with('user')->when(in_array($this->status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('approval_status', $this->status))->latest()->get() as $shop)
                <article wire:key="application-{{ $shop->id }}" class="space-y-5 rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="break-words text-lg font-extrabold">{{ $shop->name }}</h2><p class="break-words text-sm text-stone-500 dark:text-stone-400">{{ $shop->user?->email }}</p></div>
                        <flux:badge :color="match ($shop->getAttribute('approval_status')) { 'approved' => 'emerald', 'rejected' => 'red', default => 'amber' }" size="sm">{{ ucfirst($shop->getAttribute('approval_status')) }}</flux:badge>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div><dt class="text-xs font-semibold text-stone-500 dark:text-stone-400">Address</dt><dd class="mt-1">{{ $shop->getAttribute('address') ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-xs font-semibold text-stone-500 dark:text-stone-400">Contact</dt><dd class="mt-1">{{ $shop->getAttribute('contact') ?: 'Not provided' }}</dd></div>
                    </dl>
                    @if ($shop->getAttribute('description'))<p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">{{ $shop->getAttribute('description') }}</p>@endif
                    @if (! $shop->is_setup_completed)
                        <flux:callout icon="information-circle">Setup incomplete. This owner must finish shop setup before the application can be reviewed.</flux:callout>
                    @else
                        <form method="POST" action="{{ route('admin.shops.review', $shop->id) }}" class="space-y-4 border-t border-stone-200 pt-4 dark:border-stone-800">
                            @csrf
                            <flux:textarea name="reason" label="Feedback for the owner" rows="2" placeholder="Required when requesting changes" />
                            <div class="flex flex-wrap gap-2">
                                <flux:button type="submit" name="decision" value="approved" variant="primary" class="!bg-amber-500 !text-stone-950">Approve shop</flux:button>
                                <flux:button type="submit" name="decision" value="rejected" variant="danger">Request changes</flux:button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-stone-300 p-12 text-center lg:col-span-2 dark:border-stone-700"><flux:icon name="check-circle" class="mx-auto mb-3 size-10 text-stone-400" /><h2 class="font-bold">No applications in this view</h2><p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Choose another status to see other shop applications.</p></div>
            @endforelse
        </div>
    </main>
</div>
