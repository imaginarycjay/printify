<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-950 font-sans antialiased text-stone-100 selection:bg-amber-500 selection:text-white">
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
            <div class="p-6 max-w-6xl mx-auto space-y-6">
                <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-white">Print Operations Hub</h1>
                        <p class="text-xs text-stone-400">Production Queue & Job Processing Facility</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Production Staff</span>
                </div>
                <div class="p-12 text-center text-stone-400 text-sm border border-dashed border-stone-800 rounded-2xl bg-stone-900/60">
                    <flux:icon name="queue-list" class="size-12 mx-auto mb-3 text-amber-500" />
                    <p class="font-bold text-white text-lg mb-1">Production Staff Job Queue Ready</p>
                    <p class="text-xs text-stone-400 max-w-md mx-auto">Staff members can view incoming printing jobs, update production stages, and dispatch notifications.</p>
                </div>
            </div>
        @else
            <div class="p-6 max-w-6xl mx-auto space-y-6">
                <div class="flex items-center justify-between border-b border-stone-800 pb-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-white">Customer Order Portal</h1>
                        <p class="text-xs text-stone-400">Browse Printing Services & Track Custom Orders</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/30">Customer Account</span>
                </div>
                <div class="p-12 text-center text-stone-400 text-sm border border-dashed border-stone-800 rounded-2xl bg-stone-900/60">
                    <flux:icon name="shopping-bag" class="size-12 mx-auto mb-3 text-amber-500" />
                    <p class="font-bold text-white text-lg mb-1">Customer Printing Storefront Ready</p>
                    <p class="text-xs text-stone-400 max-w-md mx-auto">Browse available services from registered print shops, order thesis binding, document printing, stickers, or mugs, and track live order status.</p>
                </div>
            </div>
        @endif

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
