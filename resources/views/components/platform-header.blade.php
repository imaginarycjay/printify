@props(['title' => config('app.name', 'Printify'), 'subtitle' => 'Find a shop. Make it print.', 'backUrl' => null])

<header {{ $attributes->class(['relative z-20 mx-auto flex w-full max-w-6xl flex-col gap-4 border-b border-stone-200 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between dark:border-stone-800']) }}>
    <div class="flex min-w-0 items-center gap-3">
        @if ($backUrl)
            <a href="{{ $backUrl }}" wire:navigate aria-label="Back" class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-600 hover:border-amber-500 focus-visible:outline-2 focus-visible:outline-amber-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
        @else
            <a href="{{ route('home') }}" wire:navigate aria-label="Printify home" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20 focus-visible:outline-2 focus-visible:outline-amber-500">
                <x-app-logo-icon class="size-6 fill-current" />
            </a>
        @endif
        <div class="min-w-0">
            <p class="truncate text-base font-extrabold tracking-tight">{{ $title }}</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ $subtitle }}</p>
        </div>
    </div>
    <nav aria-label="Main navigation" class="flex flex-wrap items-center gap-2">
        @if (! $backUrl)
            <a href="{{ route('shops.index') }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-stone-700 hover:bg-stone-200/60 data-current:bg-amber-500/10 data-current:text-amber-700 dark:text-stone-300 dark:hover:bg-stone-800 dark:data-current:text-amber-400">
                <flux:icon name="building-storefront" class="size-4" /> Find shops
            </a>
        @endif
        {{ $slot }}
        @auth
            @if (! $backUrl)
                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-stone-700 hover:bg-stone-200/60 dark:text-stone-300 dark:hover:bg-stone-800">My dashboard</a>
            @endif
            <a href="{{ route('profile.edit') }}" wire:navigate aria-label="Account settings" class="flex size-11 items-center justify-center rounded-full border border-stone-200 bg-white text-xs font-bold text-amber-700 dark:border-stone-800 dark:bg-stone-900 dark:text-amber-400">{{ auth()->user()->initials() }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Log out" class="flex size-11 cursor-pointer items-center justify-center rounded-xl text-stone-500 hover:bg-red-500/10 hover:text-red-600 dark:text-stone-400"><flux:icon name="arrow-right-start-on-rectangle" class="size-5" /></button>
            </form>
        @else
            <a href="{{ route('owner.register') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-stone-600 hover:text-amber-700 dark:text-stone-400 dark:hover:text-amber-400">For shop owners</a>
            <a href="{{ route('login') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-xl bg-amber-500 px-4 text-sm font-bold text-stone-950 hover:bg-amber-400">Sign in</a>
        @endauth
        <x-theme-toggle class="size-11" />
    </nav>
</header>
