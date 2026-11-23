@props([
    'title',
    'description' => null,
])

<div class="space-y-1.5">
    <h1 data-theme-keep class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        {{ $title }}
    </h1>
    @if ($description)
        <p data-theme-keep class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
            {{ $description }}
        </p>
    @endif
</div>
