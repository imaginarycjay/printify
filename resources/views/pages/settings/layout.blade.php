@php
    $user = auth()->user();
    $workspace = \App\Support\Navigation::workspaceHome($user);
    $roleBadge = \App\Support\Navigation::roleLabel($user);
@endphp

<div class="pq-page flex flex-col md:flex-row">
    <!-- Settings Desktop Sidebar -->
    <aside class="hidden md:flex w-72 shrink-0 flex-col justify-between border-r border-slate-200 bg-white p-5 min-h-screen sticky top-0 z-30 space-y-6 dark:border-slate-800 dark:bg-slate-900">
        <div class="space-y-6">
            <div class="space-y-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                <x-app-back-link :href="$workspace['url']" :label="'Back to '.$workspace['label']" :navigate="true" class="w-full justify-start" />

                <div class="flex items-center gap-3">
                    <span data-theme-keep class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                        <flux:icon name="cog-6-tooth" class="size-5 stroke-[2]" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-slate-100 leading-tight">Account Settings</h2>
                        <span class="mt-0.5 inline-block text-xs font-semibold text-blue-700 dark:text-blue-400">{{ $roleBadge }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <span class="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <flux:icon name="adjustments-horizontal" class="size-3.5 text-blue-600 dark:text-blue-400" />
                    Settings Navigation
                </span>

                <nav aria-label="Settings navigation" class="space-y-1">
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        data-theme-keep
                        @if (request()->routeIs('profile.edit')) aria-current="page" @endif
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                    >
                        <flux:icon name="user" class="size-4 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}" />
                        <span>Profile Information</span>
                    </a>

                    <a
                        href="{{ route('security.edit') }}"
                        wire:navigate
                        data-theme-keep
                        @if (request()->routeIs('security.edit')) aria-current="page" @endif
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('security.edit') ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                    >
                        <flux:icon name="lock-closed" class="size-4 shrink-0 {{ request()->routeIs('security.edit') ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}" />
                        <span>Password &amp; Security</span>
                    </a>

                    <a
                        href="{{ route('appearance.edit') }}"
                        wire:navigate
                        data-theme-keep
                        @if (request()->routeIs('appearance.edit')) aria-current="page" @endif
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                    >
                        <flux:icon name="swatch" class="size-4 shrink-0 {{ request()->routeIs('appearance.edit') ? 'text-white' : 'text-blue-600 dark:text-blue-400' }}" />
                        <span>Appearance</span>
                    </a>
                </nav>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-4 flex items-center gap-2.5 dark:border-slate-800">
            @if ($user?->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover border border-blue-600/40" />
            @else
                <span data-theme-keep class="size-8 shrink-0 rounded-full bg-slate-900 text-white dark:bg-blue-600 text-xs font-extrabold flex items-center justify-center">
                    {{ $user?->initials() }}
                </span>
            @endif
            <div class="min-w-0 leading-tight">
                <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ $user?->name }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $user?->email }}</p>
            </div>
        </div>
    </aside>

    <!-- Main Settings Body -->
    <main class="flex-1 p-4 sm:p-8 lg:p-10 max-w-5xl w-full min-w-0 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex min-w-0 items-center gap-3">
                <x-app-back-link :href="$workspace['url']" :label="'Back to '.$workspace['label']" :navigate="true" class="md:hidden" />
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">{{ $heading ?? 'Settings' }}</h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">{{ $subheading ?? 'Manage your account preferences and security credentials' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-theme-toggle class="size-10" />
                <x-app-back-link :href="$workspace['url']" :label="'Back to '.$workspace['label']" :navigate="true" class="hidden md:inline-flex" />
            </div>
        </div>

        <!-- Mobile Settings Tabs -->
        <nav aria-label="Mobile settings navigation" class="flex flex-wrap gap-2 md:hidden">
            <a href="{{ route('profile.edit') }}" wire:navigate data-theme-keep class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ request()->routeIs('profile.edit') ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}">Profile</a>
            <a href="{{ route('security.edit') }}" wire:navigate data-theme-keep class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ request()->routeIs('security.edit') ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}">Security</a>
            <a href="{{ route('appearance.edit') }}" wire:navigate data-theme-keep class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ request()->routeIs('appearance.edit') ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}">Appearance</a>
        </nav>

        <div class="pq-card p-6 sm:p-8">
            {{ $slot }}
        </div>
    </main>
</div>
