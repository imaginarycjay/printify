<?php

use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

<div class="min-h-screen bg-stone-100 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
    @php
        $shop = auth()->user()->operationalShop();
        abort_unless($shop, 403);
        $approval = $shop->getAttribute('approval_status');
    @endphp
    <x-platform-header title="Shop Settings" :subtitle="$shop->name" :back-url="route('owner.dashboard')">
        @if ($approval === 'approved' && $shop->is_setup_completed)
            <a href="{{ route('shops.show', $shop->id) }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-stone-200 px-3 text-sm font-semibold dark:border-stone-800"><flux:icon name="globe-alt" class="size-4" /> View storefront</a>
        @endif
    </x-platform-header>
    <main class="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <x-platform-feedback />
        <div class="space-y-2">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Manage your shop</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">Your public profile, service availability and production team, in one place.</p>
        </div>
        <nav aria-label="Shop settings sections" class="flex flex-wrap gap-2">
            @foreach (['profile' => 'Profile & approval', 'availability' => 'Service availability', 'team' => 'Team access'] as $section => $label)
                <a href="#{{ $section }}" class="inline-flex min-h-11 items-center rounded-xl border border-stone-200 bg-white px-4 text-sm font-semibold hover:border-amber-500 dark:border-stone-800 dark:bg-stone-900">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="grid items-start gap-6 lg:grid-cols-3">
            <section id="profile" class="scroll-mt-6 space-y-5 rounded-3xl border border-stone-200 bg-white p-6 lg:col-span-2 dark:border-stone-800 dark:bg-stone-900/80">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-lg font-extrabold"><flux:icon name="building-storefront" class="size-5 text-amber-600 dark:text-amber-400" /> Shop profile</h2>
                    <flux:badge :color="match ($approval) { 'approved' => 'emerald', 'rejected' => 'red', default => 'amber' }" size="sm">{{ ucfirst($approval) }}</flux:badge>
                </div>
                <p class="text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                    @if ($approval === 'approved')
                        Your approved shop is listed for customers once setup is complete.
                    @elseif ($approval === 'rejected')
                        Your application needs changes. Update the details below and resubmit for review.
                    @else
                        Your shop stays hidden from customers until setup is complete and an administrator approves it.
                    @endif
                </p>
                @if ($shop->getAttribute('rejection_reason'))
                    <flux:callout variant="danger" icon="exclamation-circle">{{ $shop->getAttribute('rejection_reason') }}</flux:callout>
                @endif
                <form method="POST" action="{{ route('owner.platform.save') }}" class="space-y-4">
                    @csrf
                    <flux:input name="address" label="Shop address" value="{{ old('address', $shop->getAttribute('address')) }}" required />
                    <flux:input name="contact" label="Contact information" value="{{ old('contact', $shop->getAttribute('contact')) }}" required />
                    <flux:textarea name="description" label="About your shop" rows="3">{{ old('description', $shop->getAttribute('description')) }}</flux:textarea>
                    <div class="flex justify-end border-t border-stone-200 pt-4 dark:border-stone-800">
                        <flux:button type="submit" variant="primary" class="!bg-amber-500 !text-stone-950">{{ $approval === 'rejected' ? 'Save & resubmit application' : 'Save shop profile' }}</flux:button>
                    </div>
                </form>
            </section>
            <aside class="space-y-4 rounded-3xl border border-amber-500/20 bg-amber-500/5 p-6">
                <h2 class="font-extrabold">Get your shop ready</h2>
                <p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">Set up your services and rates, complete your contact details, then wait for application review. Approval makes your storefront visible to customers.</p>
                <a href="{{ route('owner.wizard') }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-amber-700 dark:text-amber-400">Review shop setup <flux:icon name="arrow-right" class="size-4" /></a>
            </aside>
        </div>
        <section id="availability" class="scroll-mt-6 space-y-4">
            <div><h2 class="text-xl font-extrabold">Service availability</h2><p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Workload updates automatically. Pause a service when you need to stop accepting new orders.</p></div>
            <div class="grid items-start gap-5 md:grid-cols-2">
                @forelse ($shop->services()->whereIn('service_key', ['thesis_binding', 'document_printing'])->get() as $offering)
                    @php
                        $restoring = (string) old('availability_service') === (string) $offering->id;
                        $errorBag = 'availability-'.$offering->id;
                        $serviceErrors = $errors->getBag($errorBag);
                    @endphp
                    <form wire:key="availability-{{ $offering->id }}" method="POST" action="{{ route('owner.availability', $offering->id) }}" class="space-y-5 rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
                        @csrf
                        <input type="hidden" name="availability_service" value="{{ $offering->id }}" />
                        <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-extrabold">{{ ucwords(str_replace('_', ' ', $offering->service_key)) }}</h3><x-service-status :offering="$offering" /></div>
                        @if (! $offering->is_active)
                            <p class="text-sm text-stone-500 dark:text-stone-400">This service is disabled in your shop setup.</p>
                        @endif
                        <flux:input type="number" min="1" max="100000" name="capacity" :error:bag="$errorBag" :invalid="$serviceErrors->has('capacity')" label="Active-job workload threshold" value="{{ $restoring ? old('capacity') : $offering->getAttribute('capacity') }}" required />
                        <p class="text-xs leading-relaxed text-stone-500 dark:text-stone-400">The number of active jobs that displays “At capacity.” This is separate from a daily production quota and does not block new orders.</p>
                        <details class="rounded-2xl border border-stone-200 p-4 dark:border-stone-700" @if($offering->isPaused() || $serviceErrors->any()) open @endif>
                            <summary class="cursor-pointer text-sm font-bold">Pause new orders</summary>
                            <div class="mt-4 space-y-4">
                                <flux:input name="pause_reason" :error:bag="$errorBag" :invalid="$serviceErrors->has('pause_reason')" label="Reason shown to customers" value="{{ $restoring ? old('pause_reason') : ($offering->isPaused() ? $offering->getAttribute('pause_reason') : '') }}" placeholder="For example, printer maintenance" />
                                <flux:input type="datetime-local" name="paused_until" :error:bag="$errorBag" :invalid="$serviceErrors->has('paused_until')" label="Resume automatically at" value="{{ $restoring ? old('paused_until') : ($offering->isPaused() ? $offering->getAttribute('paused_until')->format('Y-m-d\TH:i') : '') }}" />
                                <p class="text-xs leading-relaxed text-stone-500 dark:text-stone-400">Times use {{ config('app.timezone') }}. A new pause lasts 24 hours if no time is selected. Leave the reason empty to keep automatic availability.</p>
                            </div>
                        </details>
                        <div class="flex flex-wrap gap-2 border-t border-stone-200 pt-4 dark:border-stone-800">
                            <flux:button type="submit" variant="primary" class="!bg-amber-500 !text-stone-950">Save availability</flux:button>
                            @if ($offering->isPaused())
                                <flux:button type="submit" name="resume" value="1">Resume automatic status</flux:button>
                            @endif
                        </div>
                        <a href="{{ route($offering->service_key === 'thesis_binding' ? 'owner.thesis-binding' : 'owner.document-printing') }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-stone-500 hover:text-amber-700 dark:text-stone-400 dark:hover:text-amber-400">Configure service & rates <flux:icon name="arrow-right" class="size-4" /></a>
                    </form>
                @empty
                    <p class="text-sm text-stone-500">Select services in your shop setup to manage availability.</p>
                @endforelse
            </div>
        </section>
        <section id="team" class="scroll-mt-6 space-y-5 rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
            <div><h2 class="flex items-center gap-2 text-xl font-extrabold"><flux:icon name="users" class="size-5 text-amber-600 dark:text-amber-400" /> Production team</h2><p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Invite staff to this shop's production workspace. Invitations expire after 48 hours.</p></div>
            <form method="POST" action="{{ route('owner.staff.invite') }}" class="flex flex-col items-start gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="w-full sm:max-w-sm"><flux:input name="email" type="email" label="Staff email" value="{{ old('email') }}" placeholder="staff@example.com" required /></div>
                <flux:button type="submit" icon="envelope">Send invitation</flux:button>
            </form>
            <div class="divide-y divide-stone-200 border-t border-stone-200 dark:divide-stone-800 dark:border-stone-800">
                @forelse (App\Models\User::where('staff_shop_id', $shop->id)->orderBy('name')->get() as $staff)
                    <div wire:key="staff-{{ $staff->id }}" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0"><p class="font-bold">{{ $staff->name }}</p><p class="break-words text-sm text-stone-500 dark:text-stone-400">{{ $staff->email }}</p></div>
                        <form method="POST" action="{{ route('owner.staff.revoke', $staff->id) }}" x-data x-on:submit="if (! confirm('Remove this staff member’s access to your shop?')) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <flux:button type="submit" variant="danger">Remove access</flux:button>
                        </form>
                    </div>
                @empty
                    <p class="py-5 text-sm text-stone-500 dark:text-stone-400">No staff members yet. Send an invitation to add your first team member.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
