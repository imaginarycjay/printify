<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Printify') }} - Dynamic Printing Services</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-stone-950 font-sans antialiased text-stone-100 selection:bg-amber-500 selection:text-white flex flex-col justify-between relative overflow-x-hidden">
        <!-- Ambient Radial Glow -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/15 blur-[120px] pointer-events-none"></div>

        <!-- Top Navigation -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between relative z-20">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 font-bold text-lg text-white group">
                <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform duration-200">
                    <x-app-logo-icon class="size-6 fill-current text-stone-950" />
                </span>
                <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white to-stone-300 bg-clip-text text-transparent">
                    {{ config('app.name', 'Printify') }}
                </span>
            </a>

            <nav class="flex items-center gap-3">
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-stone-950 text-xs font-bold shadow-md shadow-amber-500/20 hover:bg-amber-400 transition-colors"
                    >
                        <flux:icon name="squares-2x2" class="size-4" />
                        Go to Dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl border border-stone-800 bg-stone-900/60 text-xs font-semibold text-stone-300 hover:text-white hover:border-stone-700 transition-all"
                    >
                        Sign In
                    </a>
                    @if (Route::has('register'))
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 text-stone-950 text-xs font-bold shadow-md shadow-amber-500/20 hover:bg-amber-400 transition-colors"
                        >
                            Create Account
                            <flux:icon name="arrow-right" class="size-3.5" />
                        </a>
                    @endif
                @endauth
            </nav>
        </header>

        <!-- Main Hero Section -->
        <main class="w-full max-w-5xl mx-auto px-6 py-12 text-center relative z-10 my-auto space-y-8">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider shadow-sm">
                <flux:icon name="sparkles" class="size-4" />
                Unified Printing Services System
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight">
                Streamlined Printing Ecosystem for <span class="bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500 bg-clip-text text-transparent">Owners, Staff & Clients</span>
            </h1>

            <p class="text-base sm:text-lg text-stone-400 max-w-2xl mx-auto leading-relaxed">
                Configure printing services dynamically, process job queues in real-time, and empower customers with transparent order tracking.
            </p>

            <!-- CTA Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                <a
                    href="{{ route('login') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-amber-500 text-stone-950 text-sm font-bold shadow-xl shadow-amber-500/25 hover:bg-amber-400 transition-all cursor-pointer"
                >
                    <flux:icon name="arrow-right-end-on-rectangle" class="size-4" />
                    Access Auth Portal
                </a>
                <a
                    href="{{ route('register') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-stone-800 bg-stone-900/80 text-sm font-semibold text-stone-200 hover:bg-stone-800 hover:border-stone-700 transition-all cursor-pointer"
                >
                    <flux:icon name="user-plus" class="size-4" />
                    Register as Customer
                </a>
            </div>

            <!-- Role Badge Features Preview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-12 text-left max-w-4xl mx-auto">
                <div class="p-5 rounded-2xl border border-stone-800/80 bg-stone-900/50 backdrop-blur-md">
                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="building-storefront" class="size-4" />
                        Business Owner
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">Business Service Configurator</h3>
                    <p class="text-xs text-stone-400 leading-relaxed">
                        Modular setup wizard to select active shop capabilities and expand services on-demand.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-stone-800/80 bg-stone-900/50 backdrop-blur-md">
                    <div class="flex items-center gap-2 text-amber-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="wrench-screwdriver" class="size-4" />
                        Production Staff
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">Print Operations Hub</h3>
                    <p class="text-xs text-stone-400 leading-relaxed">
                        Process job queues, update printing stages live, and dispatch customer notifications automatically.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-stone-800/80 bg-stone-900/50 backdrop-blur-md">
                    <div class="flex items-center gap-2 text-sky-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <flux:icon name="shopping-bag" class="size-4" />
                        Customer
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">Customer Order Portal</h3>
                    <p class="text-xs text-stone-400 leading-relaxed">
                        Browse book binding, paper printing, ID services, customize print specs, and track order status live.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-6 text-center text-xs text-stone-600 border-t border-stone-900 relative z-20">
            &copy; {{ date('Y') }} {{ config('app.name', 'Printify') }}. All rights reserved.
        </footer>
    </body>
</html>
