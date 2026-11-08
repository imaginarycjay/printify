<button
    type="button"
    x-data
    @click="$flux.dark = ! $flux.dark"
    :title="$flux.dark ? 'Switch to light mode' : 'Switch to dark mode'"
    aria-label="Toggle theme"
    :aria-pressed="$flux.dark"
    data-theme-keep
    data-test="theme-toggle"
    {{ $attributes->merge(['class' => 'group relative flex size-9 items-center justify-center rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 hover:bg-stone-100 dark:hover:bg-stone-800 hover:border-amber-500/50 text-stone-600 dark:text-stone-300 hover:text-amber-600 dark:hover:text-amber-400 transition-all duration-200 shadow-xs cursor-pointer shrink-0']) }}
>
    <!-- Sun icon: shown in dark mode -->
    <flux:icon name="sun" class="size-4 text-amber-700 dark:text-amber-400 hidden dark:block motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:rotate-45" />

    <!-- Moon icon: shown in light mode -->
    <flux:icon name="moon" class="size-4 text-stone-700 block dark:hidden motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:-rotate-12" />

    <span class="sr-only">Toggle theme</span>
</button>
