@if (app()->isLocal())
    <details data-theme-keep class="group rounded-xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <summary data-theme-keep class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-2.5 text-xs font-semibold text-stone-600 transition-colors hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200 [&::-webkit-details-marker]:hidden">
            <span class="inline-flex items-center gap-2">
                <flux:icon name="command-line" class="size-3.5 text-amber-600 dark:text-amber-400" />
                <span>{{ __('Dev quick login') }}</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span data-theme-keep class="rounded-md bg-stone-100 px-1.5 py-0.5 text-[10px] font-medium text-stone-500 dark:bg-stone-800 dark:text-stone-400">{{ __('Local only') }}</span>
                <flux:icon name="chevron-down" class="size-3.5 transition-transform duration-150 group-open:rotate-180" />
            </span>
        </summary>

        <div data-theme-keep class="border-t border-stone-200 p-3 dark:border-stone-800">
            <div class="grid grid-cols-3 gap-2">
                @foreach ([
                    ['role' => 'business_owner', 'label' => 'Owner', 'email' => 'owner@capstone.test', 'icon' => 'building-storefront'],
                    ['role' => 'production_staff', 'label' => 'Staff', 'email' => 'staff@capstone.test', 'icon' => 'wrench-screwdriver'],
                    ['role' => 'customer', 'label' => 'Customer', 'email' => 'customer@capstone.test', 'icon' => 'user'],
                ] as $account)
                    <form method="POST" action="{{ route('dev.login', $account['role']) }}">
                        @csrf
                        <button type="submit" data-theme-keep class="group/btn w-full cursor-pointer rounded-lg border border-stone-200 bg-stone-50 px-2.5 py-2 text-left transition-colors hover:border-amber-500/60 hover:bg-amber-500/10 dark:border-stone-800 dark:bg-stone-950/70 dark:hover:border-amber-500/50">
                            <div class="flex items-center gap-1.5">
                                <flux:icon :name="$account['icon']" class="size-3.5 shrink-0 text-amber-700 dark:text-amber-400" />
                                <span data-theme-keep class="truncate text-xs font-semibold text-stone-900 dark:text-stone-100">{{ $account['label'] }}</span>
                            </div>
                            <p data-theme-keep class="mt-0.5 truncate text-[10px] text-stone-500 dark:text-stone-400">{{ $account['email'] }}</p>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </details>
@endif
