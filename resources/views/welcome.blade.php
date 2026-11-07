<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-100 dark:bg-stone-950 font-sans antialiased text-stone-900 dark:text-stone-100 selection:bg-amber-500 selection:text-white flex flex-col justify-between relative overflow-x-hidden">
        <!-- Ambient Radial Glow -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] max-w-full rounded-full bg-amber-500/15 blur-[120px] pointer-events-none"></div>

        <x-platform-header />

        <!-- Main Hero Section -->
        <main class="w-full max-w-5xl mx-auto px-6 py-12 text-center relative z-10 my-auto space-y-8">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider shadow-sm">
                <flux:icon name="sparkles" class="size-4" />
                Unified Printing Services System
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-stone-900 dark:text-white max-w-3xl mx-auto leading-tight">
                Streamlined Printing Ecosystem for <span class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-600 dark:from-amber-400 dark:via-amber-300 dark:to-amber-500 bg-clip-text text-transparent">Owners, Staff & Clients</span>
            </h1>

            <p class="text-base sm:text-lg text-stone-600 dark:text-stone-400 max-w-2xl mx-auto leading-relaxed">
                Configure printing services dynamically, process job queues in real-time, and empower customers with transparent order tracking.
            </p>

            <!-- CTA Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                <a
                    href="{{ route('shops.index') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-amber-500 text-stone-950 text-sm font-bold shadow-xl shadow-amber-500/25 hover:bg-amber-400 transition-all cursor-pointer"
                >
                    <flux:icon name="building-storefront" class="size-4" />
                    Find a print shop
                </a>
                <a
                    href="{{ auth()->check() ? route('dashboard') : route('owner.register') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-stone-200 dark:border-stone-800 bg-white/90 dark:bg-stone-900/80 text-sm font-semibold text-stone-800 dark:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 hover:border-stone-300 dark:hover:border-stone-700 transition-all cursor-pointer shadow-sm"
                >
                    <flux:icon name="user-plus" class="size-4" />
                    {{ auth()->check() ? 'Open my dashboard' : 'Register your print shop' }}
                </a>
            </div>

            <!-- Role Badge Features Preview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-12 text-left max-w-4xl mx-auto">
                <div class="p-5 rounded-2xl border border-stone-200/80 dark:border-stone-800/80 bg-white/80 dark:bg-stone-900/50 backdrop-blur-md shadow-xs">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="building-storefront" class="size-4" />
                        Business Owner
                    </div>
                    <h3 class="text-sm font-bold text-stone-900 dark:text-white mb-1">Business Service Configurator</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                        Modular setup wizard to select active shop capabilities and expand services on-demand.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-stone-200/80 dark:border-stone-800/80 bg-white/80 dark:bg-stone-900/50 backdrop-blur-md shadow-xs">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="wrench-screwdriver" class="size-4" />
                        Production Staff
                    </div>
                    <h3 class="text-sm font-bold text-stone-900 dark:text-white mb-1">Print Operations Hub</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                        Process job queues, update printing stages live, and dispatch customer notifications automatically.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-stone-200/80 dark:border-stone-800/80 bg-white/80 dark:bg-stone-900/50 backdrop-blur-md shadow-xs">
                    <div class="flex items-center gap-2 text-sky-600 dark:text-sky-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="shopping-bag" class="size-4" />
                        Customer
                    </div>
                    <h3 class="text-sm font-bold text-stone-900 dark:text-white mb-1">Customer Order Portal</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                        Browse book binding, paper printing, ID services, customize print specs, and track order status live.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-6 text-center text-xs text-stone-500 dark:text-stone-600 border-t border-stone-200 dark:border-stone-900 relative z-20">
            &copy; {{ date('Y') }} {{ config('app.name', 'Printify') }}. All rights reserved.
        </footer>

        @fluxScripts
    </body>
</html>
