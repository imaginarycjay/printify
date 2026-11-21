@props(['title' => null])

<x-layouts::auth.card :title="$title">
    {{ $slot }}

    @isset($footer)
        <x-slot:footer>
            {{ $footer }}
        </x-slot:footer>
    @endisset

    @isset($below)
        <x-slot:below>
            {{ $below }}
        </x-slot:below>
    @endisset
</x-layouts::auth.card>
