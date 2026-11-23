<button
    type="button"
    x-data
    @click="$flux.dark = ! $flux.dark"
    :title="$flux.dark ? 'Switch to light mode' : 'Switch to dark mode'"
    aria-label="Toggle theme"
    :aria-pressed="$flux.dark"
    data-theme-keep
    data-test="theme-toggle"
    {{ $attributes->merge(['class' => 'group relative flex size-9 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 hover:border-blue-600/60 dark:hover:border-blue-400/60 text-slate-600 dark:text-slate-300 hover:text-blue-700 dark:hover:text-blue-400 transition-all duration-200 shadow-xs cursor-pointer shrink-0']) }}
>
    <!-- Sun icon: shown in dark mode -->
    <flux:icon name="sun" class="size-4 text-slate-300 group-hover:text-blue-400 hidden dark:block motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:rotate-45" />

    <!-- Moon icon: shown in light mode -->
    <flux:icon name="moon" class="size-4 text-slate-700 group-hover:text-blue-700 block dark:hidden motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:-rotate-12" />

    <span class="sr-only">Toggle theme</span>
</button>
