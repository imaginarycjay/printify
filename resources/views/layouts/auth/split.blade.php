<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-100 font-sans antialiased dark:bg-[#0b1120] text-slate-900 dark:text-slate-100 selection:bg-blue-600 selection:text-white">
        <div class="relative grid min-h-svh w-full lg:grid-cols-12 overflow-hidden">
            <!-- Theme Toggle Upper Right Corner -->
            <div class="absolute top-4 right-4 z-50">
                <x-theme-toggle />
            </div>
            <!-- Left Side: Integrated Print Operations Showcase -->
            <div class="relative hidden flex-col justify-between p-10 lg:flex lg:col-span-6 xl:col-span-7 bg-white dark:bg-slate-900 text-slate-900 dark:text-white overflow-hidden border-r border-slate-200 dark:border-slate-800">
                <!-- Brand Header -->
                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-bold tracking-tight text-slate-900 dark:text-white group">
                        <span class="flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-800 group-hover:border-blue-600 dark:group-hover:border-blue-400 transition-colors">
                            <x-app-logo-icon class="size-7" />
                        </span>
                        <div class="flex flex-col">
                            <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                {{ config('app.name', 'PressQueue') }}
                            </span>
                            <span class="text-xs font-medium text-blue-700 dark:text-blue-400 tracking-wide uppercase">Unified Print Operations Engine</span>
                        </div>
                    </a>
                </div>

                <!-- Central Visual Showcase: 3 Integrated Pillars -->
                <div class="relative z-10 my-auto space-y-6 max-w-xl">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-950/60 dark:border-blue-800 dark:text-blue-300 text-xs font-semibold uppercase tracking-wider">
                            <flux:icon name="sparkles" class="size-3.5" />
                            Precision Print Architecture
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                            Unified Platform for Shop Owners, Staff & Clients
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Modular service configuration, real-time production queue management, and transparent order tracking.
                        </p>
                    </div>

                    <!-- 3 Integrated Pillars -->
                    <div class="grid gap-3 pt-2">
                        <!-- Pillar 1: Customer Order Portal -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 p-4 transition-colors duration-200 hover:border-blue-600/50 dark:hover:border-blue-500/50">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-colors">
                                <flux:icon name="shopping-bag" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">1. Customer Order Portal</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200/80 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Customer</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Mobile-first digital storefront for hardbound/softbound binding, document printing, ID services, spec customization, and live status tracking.
                                </p>
                            </div>
                        </div>

                        <!-- Pillar 2: Print Operations Hub -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 p-4 transition-colors duration-200 hover:border-blue-600/50 dark:hover:border-blue-500/50">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-colors">
                                <flux:icon name="queue-list" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">2. Print Operations Hub</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">Production Staff</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Real-time job queue processing, print stage status updates, and automated customer status notifications.
                                </p>
                            </div>
                        </div>

                        <!-- Pillar 3: Business Service Configurator -->
                        <div class="group relative flex items-start gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 p-4 transition-colors duration-200 hover:border-blue-600/50 dark:hover:border-blue-500/50">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-colors">
                                <flux:icon name="squares-plus" class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">3. Business Service Configurator</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">Business Owner</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Modular setup wizard and expandable service manager to seamlessly activate, scale, or edit available printing services on-demand.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer info -->
                <div class="relative z-10 flex items-center justify-between text-xs text-slate-500 border-t border-slate-200 dark:border-slate-800 pt-4">
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'PressQueue') }}. All rights reserved.</span>
                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Engine Active
                    </span>
                </div>
            </div>

            <!-- Right Side: Auth Form Container -->
            <div class="flex flex-col justify-center items-center p-6 sm:p-12 lg:col-span-6 xl:col-span-5 min-h-svh bg-slate-100 dark:bg-[#0b1120]">
                <!-- Mobile Top Brand Header -->
                <div class="w-full max-w-md mb-6 lg:hidden text-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 font-bold text-slate-900 dark:text-white">
                        <span class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-800">
                            <x-app-logo-icon class="size-5" />
                        </span>
                        <span class="text-lg tracking-tight">{{ config('app.name', 'PressQueue') }}</span>
                    </a>
                </div>

                <!-- Auth Card Box -->
                <div class="w-full max-w-md space-y-6">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-xs">
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
