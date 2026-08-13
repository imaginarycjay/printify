<?php

use Livewire\Component;

new class extends Component {
}; ?>

<div class="p-6 max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between border-b border-stone-800 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Print Operations Hub</h1>
            <p class="text-xs text-stone-400">Production Queue & Job Processing Facility</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">Production Staff</span>
    </div>
    <div class="p-12 text-center text-stone-400 text-sm border border-dashed border-stone-800 rounded-2xl bg-stone-900/60">
        <flux:icon name="queue-list" class="size-12 mx-auto mb-3 text-amber-500" />
        <p class="font-bold text-white text-lg mb-1">Production Staff Job Queue Ready</p>
        <p class="text-xs text-stone-400 max-w-md mx-auto">Staff members can view incoming printing jobs, update production stages, and dispatch notifications.</p>
    </div>
</div>
