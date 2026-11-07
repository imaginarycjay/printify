@props(['offering'])

@php
    $paused = $offering->isPaused();
    $status = $paused ? 'Paused' : $offering->workload();
    $color = match ($status) {
        'Normal' => 'emerald',
        'Busy' => 'amber',
        'At capacity' => 'orange',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" size="sm" :icon="$paused ? 'pause-circle' : 'signal'" {{ $attributes }}>{{ $status }}</flux:badge>
