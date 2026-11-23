@props([
    'title' => config('app.name', 'PressQueue'),
    'subtitle' => 'Find a shop. Make it print.',
    'backUrl' => null,
    'backLabel' => null,
    'autoBack' => false,
    'breadcrumbs' => [],
    'brandUrl' => null,
])

@php
    $resolvedTarget = ($backUrl === null && $autoBack)
        ? \App\Support\Navigation::backTarget(auth()->user())
        : null;
    $resolvedBackUrl = $backUrl ?? ($resolvedTarget['url'] ?? null);
    $resolvedBackLabel = $backLabel ?? ($resolvedTarget['label'] ?? ($resolvedBackUrl ? 'Back' : null));
    $resolvedBreadcrumbs = ! empty($breadcrumbs) ? $breadcrumbs : ($resolvedTarget['breadcrumbs'] ?? []);
    $resolvedBrandUrl = $brandUrl ?? route('home');
@endphp

<header {{ $attributes->class(['relative z-20 mx-auto flex w-full max-w-6xl flex-col gap-4 border-b border-slate-200 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between dark:border-slate-800']) }}>
    <div class="flex min-w-0 items-center gap-3">
        @if ($resolvedBackUrl)
            <x-app-back-link :href="$resolvedBackUrl" :label="$resolvedBackLabel ?? 'Back'" />
        @else
            <a href="{{ $resolvedBrandUrl }}" data-theme-keep aria-label="PressQueue home" class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-xs transition-colors hover:border-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-400">
                <x-app-logo-icon class="size-6" />
            </a>
        @endif
        <div class="min-w-0">
            @if (count($resolvedBreadcrumbs) > 1)
                <p class="truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ implode(' / ', array_column($resolvedBreadcrumbs, 'label')) }}
                </p>
            @endif
            <p class="truncate text-base font-extrabold tracking-tight text-slate-900 dark:text-slate-100">{{ $title }}</p>
            <p class="truncate text-xs text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
        </div>
    </div>
    <nav aria-label="Main navigation" class="flex flex-wrap items-center gap-2 [&_a:focus-visible]:outline-2 [&_a:focus-visible]:outline-offset-4 [&_a:focus-visible]:outline-blue-700 [&_button:focus-visible]:outline-2 [&_button:focus-visible]:outline-offset-4 [&_button:focus-visible]:outline-blue-700 dark:[&_a:focus-visible]:outline-blue-400 dark:[&_button:focus-visible]:outline-blue-400">
        @if (! $resolvedBackUrl)
            <a href="{{ route('shops.index') }}" data-theme-keep class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-200/70 data-current:bg-blue-50 data-current:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:data-current:bg-blue-950/60 dark:data-current:text-blue-400">
                <flux:icon name="building-storefront" class="size-4" /> Find shops
            </a>
        @endif
        {{ $slot }}
        @auth
            @if (! $resolvedBackUrl)
                <a href="{{ route('dashboard') }}" data-theme-keep class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-200/70 dark:text-slate-300 dark:hover:bg-slate-800">My dashboard</a>
            @endif
            <livewire:notification-bell />
            <a href="{{ route('profile.edit') }}" data-theme-keep aria-label="Account settings" class="flex size-11 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-bold text-blue-700 hover:border-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-400">{{ auth()->user()->initials() }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Log out" class="flex size-11 cursor-pointer items-center justify-center rounded-xl text-slate-500 hover:bg-rose-500/10 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400"><flux:icon name="arrow-right-start-on-rectangle" class="size-5" /></button>
            </form>
        @else
            <a href="{{ route('owner.register') }}" data-theme-keep class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-slate-600 hover:text-blue-700 dark:text-slate-400 dark:hover:text-blue-400">Register a shop</a>
            <a href="{{ route('login') }}" data-theme-keep class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700 dark:hover:bg-blue-500">Sign in</a>
        @endauth
        <x-theme-toggle class="size-11" />
    </nav>
</header>
