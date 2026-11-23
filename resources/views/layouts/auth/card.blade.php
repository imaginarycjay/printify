<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body data-theme-keep class="pressqueue-auth min-h-svh font-sans antialiased selection:bg-blue-600 selection:text-white">
        <header data-theme-keep class="w-full border-b border-[var(--auth-border-subtle)] bg-[var(--auth-bar)]">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('home') }}" data-theme-keep class="inline-flex items-center gap-2.5 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 dark:focus-visible:outline-blue-400">
                        <span data-theme-keep class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-700 dark:bg-slate-800">
                            <x-app-logo-icon class="size-5" />
                        </span>
                        <span data-theme-keep class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white">{{ config('app.name', 'PressQueue') }}</span>
                    </a>
                    <span data-theme-keep class="hidden border-s border-[var(--auth-border)] ps-2.5 text-xs font-medium text-slate-500 dark:text-slate-400 sm:inline">
                        {{ __('Print shop platform') }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    @guest
                        <a href="{{ route('shops.index') }}" data-theme-keep class="inline-flex min-h-9 items-center gap-1.5 rounded-xl px-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <flux:icon name="building-storefront" class="size-4" />
                            <span>{{ __('Browse shops') }}</span>
                        </a>
                    @endguest
                    <x-theme-toggle />
                </div>
            </div>
        </header>

        <main class="flex min-h-[calc(100svh-3.85rem)] flex-col items-center justify-center px-4 py-5 sm:px-6 sm:py-8">
            <div class="w-full max-w-[448px] space-y-3">
                <div data-theme-keep class="overflow-hidden rounded-2xl border border-[var(--auth-border)] bg-[var(--auth-card)] shadow-xs">
                    <div class="p-6 sm:p-7">
                        {{ $slot }}
                    </div>

                    @isset($footer)
                        <div data-theme-keep class="border-t border-[var(--auth-border-subtle)] bg-[var(--auth-card-footer)] px-6 py-4 sm:px-7">
                            {{ $footer }}
                        </div>
                    @endisset
                </div>

                @isset($below)
                    <div class="w-full">
                        {{ $below }}
                    </div>
                @endisset

                <p data-theme-keep class="text-center text-xs text-slate-500 dark:text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'PressQueue') }}
                </p>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group position="top end">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
