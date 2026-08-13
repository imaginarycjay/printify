<div class="min-h-screen w-full bg-stone-950 text-stone-100 font-sans antialiased flex flex-col md:flex-row relative selection:bg-amber-500 selection:text-white">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 left-1/3 -translate-x-1/2 size-[600px] rounded-full bg-amber-500/10 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
    @endphp

    <!-- Settings Desktop Sidebar -->
    <aside class="hidden md:flex flex-col w-72 bg-stone-900/90 border-r border-stone-800/80 backdrop-blur-xl p-5 justify-between shrink-0 min-h-screen sticky top-0 z-30 space-y-6">
        <div class="space-y-6">
            <!-- Header & Back to App Launcher -->
            <div class="space-y-3 border-b border-stone-800/80 pb-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-stone-400 hover:text-amber-400 transition-colors">
                    <flux:icon name="arrow-left" class="size-3.5" />
                    <span>Back to App Launcher</span>
                </a>
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-stone-950 font-bold shadow-md shadow-amber-500/20">
                        <flux:icon name="cog-6-tooth" class="size-6 stroke-[2]" />
                    </span>
                    <div>
                        <h2 class="text-sm font-extrabold text-white leading-tight">Account Settings</h2>
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Business Owner</span>
                    </div>
                </div>
            </div>

            <!-- Settings Navigation Items -->
            <div class="space-y-2">
                <span class="px-2 text-[10px] font-extrabold text-stone-400 tracking-wider uppercase flex items-center gap-1.5">
                    <flux:icon name="adjustments-horizontal" class="size-3 text-amber-500" />
                    Settings Navigation
                </span>

                <nav class="space-y-1">
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('profile.edit') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="user" class="size-4 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>Profile Information</span>
                    </a>

                    <a
                        href="{{ route('security.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('security.edit') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="lock-closed" class="size-4 shrink-0 {{ request()->routeIs('security.edit') ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>Password &amp; Security</span>
                    </a>

                    <a
                        href="{{ route('appearance.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('appearance.edit') ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-md shadow-amber-500/20' : 'text-stone-300 hover:text-white hover:bg-stone-800/60' }}"
                    >
                        <flux:icon name="swatch" class="size-4 shrink-0 {{ request()->routeIs('appearance.edit') ? 'text-stone-950' : 'text-amber-400' }}" />
                        <span>Appearance</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar Bottom User Profile Pill -->
        <div class="border-t border-stone-800/80 pt-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover border border-amber-500/40 shadow-md" />
                @else
                    <span class="size-8 rounded-full bg-amber-500 text-stone-950 text-xs font-extrabold flex items-center justify-center shadow-md">
                        {{ $user?->initials() }}
                    </span>
                @endif
                <div class="grid leading-tight">
                    <span class="text-xs font-bold text-white truncate max-w-[120px]">{{ $user?->name }}</span>
                    <span class="text-[10px] text-stone-400 truncate max-w-[120px]">{{ $user?->email }}</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Settings Body -->
    <main class="flex-1 p-6 sm:p-10 max-w-5xl w-full min-w-0 relative z-20 space-y-8">
        <!-- Top Workspace Bar -->
        <div class="flex items-center justify-between pb-4 border-b border-stone-800/80">
            <div>
                <h1 class="text-xl font-extrabold text-white tracking-tight">{{ $heading ?? 'Settings' }}</h1>
                <p class="text-xs text-stone-400">{{ $subheading ?? 'Manage your account preferences and security credentials' }}</p>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate class="px-3 py-1.5 rounded-xl border border-stone-800 bg-stone-900/80 text-xs font-bold text-stone-300 hover:text-white transition-all">
                Exit Settings
            </a>
        </div>

        <!-- Content Card Slot -->
        <div class="rounded-3xl border border-stone-800 bg-stone-900/80 p-6 sm:p-8 shadow-xl backdrop-blur-md">
            {{ $slot }}
        </div>
    </main>
</div>
