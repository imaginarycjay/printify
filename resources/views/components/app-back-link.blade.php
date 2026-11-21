@props([
    'href',
    'label' => 'Back',
    'navigate' => false,
])

<a
    href="{{ $href }}"
    @if ($navigate) wire:navigate @endif
    data-theme-keep
    data-test="universal-back-link"
    aria-label="{{ $label }}"
    {{ $attributes->class(['pq-back-btn print:hidden max-w-[14rem] sm:max-w-[18rem]']) }}
>
    <flux:icon name="arrow-left" class="size-4 shrink-0" />
    <span class="truncate">{{ $label }}</span>
</a>
