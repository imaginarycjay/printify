<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 font-sans antialiased dark:bg-stone-950 text-stone-900 dark:text-stone-100 selection:bg-amber-500 selection:text-white">
        <div class="relative grid min-h-svh w-full lg:grid-cols-12 overflow-hidden">
            <!-- Left Side: Integrated Print Operations Showcase -->
            <div class="relative hidden flex-col justify-between p-10 lg:flex lg:col-span-6 xl:col-span-7 bg-stone-900 text-white overflow-hidden border-r border-stone-800">
                <!-- Ambient Glow Background Effects -->
                <div class="absolute -top-32 -left-32 size-96 rounded-full bg-amber-500/20 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-32 -right-32 size-96 rounded-full bg-amber-600/10 blur-3xl pointer-events-none"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#383838_1px,transparent_1px)] [background-size:24px_24px] opacity-30 pointer-events-none"></div>

                <!-- Brand Header -->
                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-bold tracking-tight text-white group" wire:navigate>
                        <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform duration-200">
                            <x-app-logo-icon class="size-7 fill-current text-stone-950" />
                        </span>
                        <div class="flex flex-col">
                            <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white via-stone-100 to-stone-300 bg-clip-text text-transparent">
                                {{ config('app.name', 'Printify') }}
                            </span>
                            <span class="text-xs font-medium text-amber-400 tracking-wide uppercase">Unified Print Operations Engine</span>
                        </div>
                    </a>
                </div>

                <!-- Central Visual Showcase: 3 Integrated Pillars -->
                <div class="relative z-10 my-auto space-y-6 max-w-xl">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                            <flux:icon name="sparkles" class="size-3.5" />
                            Precision Print Architecture
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                            Unified Platform for Shop Owners, Staff & Clients
                        </h2>
                        <p class="text-sm text-stone-400 leading-relaxed">
                            Modular service configuration, real-time production queue management, and transparent order tracking.
                        </p>
                    </div>

                    <!-- 3 Integrated Pillars -->
                    <div class="grid gap-3 pt-2">
                        <!-- Pillar 1: Customer Order Portal -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-stone-800 bg-stone-900/80 p-4 backdrop-blur-md transition-all duration-200 hover:border-amber-500/50 hover:bg-stone-800/80">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-stone-950 transition-colors">
                                <flux:icon name="shopping-bag" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-white">1. Customer Order Portal</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-stone-800 text-stone-300">Customer</span>
                                </div>
                                <p class="text-xs text-stone-400 leading-relaxed">
                                    Mobile-first digital storefront for hardbound/softbound binding, document printing, ID services, spec customization, and live status tracking.
                                </p>
                            </div>
                        </div>

                        <!-- Pillar 2: Print Operations Hub -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-stone-800 bg-stone-900/80 p-4 backdrop-blur-md transition-all duration-200 hover:border-amber-500/50 hover:bg-stone-800/80">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-stone-950 transition-colors">
                                <flux:icon name="queue-list" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-white">2. Print Operations Hub</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-stone-800 text-amber-400">Production Staff</span>
                                </div>
                                <p class="text-xs text-stone-400 leading-relaxed">
                                    Real-time job queue processing, print stage status updates, and automated customer status notifications.
                                </p>
                            </div>
                        </div>

                        <!-- Pillar 3: Business Service Configurator -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-stone-800 bg-stone-900/80 p-4 backdrop-blur-md transition-all duration-200 hover:border-amber-500/50 hover:bg-stone-800/80">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 group-hover:bg-amber-500 group-hover:text-stone-950 transition-colors">
                                <flux:icon name="squares-plus" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-white">3. Business Service Configurator</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-stone-800 text-emerald-400">Business Owner</span>
                                </div>
                                <p class="text-xs text-stone-400 leading-relaxed">
                                    Modular setup wizard and expandable service manager to seamlessly activate, scale, or edit available printing services on-demand.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer info -->
                <div class="relative z-10 flex items-center justify-between text-xs text-stone-500 border-t border-stone-800/60 pt-4">
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'Printify') }}. All rights reserved.</span>
                    <span class="flex items-center gap-1.5 text-stone-400">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Engine Active
                    </span>
                </div>
            </div>

            <!-- Right Side: Redesigned Auth Form Container -->
            <div class="flex flex-col justify-center items-center p-6 sm:p-12 lg:col-span-6 xl:col-span-5 min-h-svh bg-stone-50 dark:bg-stone-950">
                <!-- Mobile Top Brand Header -->
                <div class="w-full max-w-md mb-6 lg:hidden text-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-bold text-stone-900 dark:text-white" wire:navigate>
                        <span class="flex size-9 items-center justify-center rounded-lg bg-amber-500 text-stone-950">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        <span class="text-lg tracking-tight">{{ config('app.name', 'Printify') }}</span>
                    </a>
                </div>

                <!-- Auth Card Box -->
                <div class="w-full max-w-md space-y-6">
                    <div class="rounded-2xl border border-stone-200/80 dark:border-stone-800/80 bg-white/90 dark:bg-stone-900/90 p-6 sm:p-8 shadow-xl shadow-stone-900/5 backdrop-blur-xl">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
