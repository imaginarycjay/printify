@if (app()->isLocal())
    <div class="mt-6 border-t border-amber-500/20 pt-4 dark:border-amber-500/30">
        <div class="rounded-xl bg-amber-500/10 p-3.5 backdrop-blur-md border border-amber-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                    <flux:icon name="command-line" class="size-3.5" />
                    Dev Role Switcher
                </span>
                <span class="text-[10px] text-amber-700/70 dark:text-amber-300/70">1-Click Test Login</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <!-- Business Owner -->
                <form method="POST" action="{{ route('dev.login', 'business_owner') }}">
                    @csrf
                    <button type="submit" class="w-full text-left rounded-lg bg-amber-500/10 hover:bg-amber-500/20 p-2 transition-all border border-amber-500/20 group cursor-pointer">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <flux:icon name="building-storefront" class="size-3.5 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform" />
                            <span class="text-xs font-medium text-stone-900 dark:text-stone-100 truncate">Owner</span>
                        </div>
                        <p class="text-[10px] text-stone-500 dark:text-stone-400 truncate">owner@capstone.test</p>
                    </button>
                </form>

                <!-- Production Staff -->
                <form method="POST" action="{{ route('dev.login', 'production_staff') }}">
                    @csrf
                    <button type="submit" class="w-full text-left rounded-lg bg-amber-500/10 hover:bg-amber-500/20 p-2 transition-all border border-amber-500/20 group cursor-pointer">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <flux:icon name="wrench-screwdriver" class="size-3.5 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform" />
                            <span class="text-xs font-medium text-stone-900 dark:text-stone-100 truncate">Staff</span>
                        </div>
                        <p class="text-[10px] text-stone-500 dark:text-stone-400 truncate">staff@capstone.test</p>
                    </button>
                </form>

                <!-- Customer -->
                <form method="POST" action="{{ route('dev.login', 'customer') }}">
                    @csrf
                    <button type="submit" class="w-full text-left rounded-lg bg-amber-500/10 hover:bg-amber-500/20 p-2 transition-all border border-amber-500/20 group cursor-pointer">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <flux:icon name="user" class="size-3.5 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform" />
                            <span class="text-xs font-medium text-stone-900 dark:text-stone-100 truncate">Customer</span>
                        </div>
                        <p class="text-[10px] text-stone-500 dark:text-stone-400 truncate">customer@capstone.test</p>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
