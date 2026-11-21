<?php

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

new class extends Component {
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->whereKey($notificationId)->first();
        if ($notification instanceof DatabaseNotification) {
            $notification->markAsRead();
        }
    }

    public function markAllRead(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}; ?>

<div class="relative" @click.outside="$wire.set('open', false)">
    @php
        $user = auth()->user();
        $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
        $recentNotifications = $user
            ? $user->notifications()->latest()->take(10)->get()
            : collect();
    @endphp

    <button
        type="button"
        wire:click="toggle"
        aria-label="Notifications"
        class="relative flex size-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-700 hover:border-amber-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 cursor-pointer transition-colors"
    >
        <flux:icon name="bell" class="size-4.5" />
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-black text-stone-950">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 z-50 mt-2 w-80 sm:w-96 rounded-2xl border border-stone-200 bg-white p-3 shadow-2xl dark:border-stone-800 dark:bg-stone-900">
            <div class="flex items-center justify-between border-b border-stone-200 pb-2.5 dark:border-stone-800">
                <span class="text-xs font-extrabold text-stone-900 dark:text-white">
                    Notifications ({{ $unreadCount }} unread)
                </span>
                @if ($unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllRead"
                        class="text-[11px] font-bold text-amber-600 hover:text-amber-500 dark:text-amber-400 cursor-pointer"
                    >
                        Mark all read
                    </button>
                @endif
            </div>

            <div class="mt-2 max-h-80 space-y-2 overflow-y-auto">
                @forelse ($recentNotifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : [];
                        $isUnread = $notification->read_at === null;
                    @endphp
                    <div wire:key="notif-{{ $notification->id }}" class="rounded-xl border p-2.5 text-xs transition-colors {{ $isUnread ? 'border-amber-500/40 bg-amber-500/10' : 'border-stone-200 bg-stone-50 dark:border-stone-800 dark:bg-stone-950/60' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-bold text-stone-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</p>
                            <span class="shrink-0 text-[10px] text-stone-500">{{ $notification->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-[11px] leading-relaxed text-stone-600 dark:text-stone-400">{{ $data['message'] ?? '' }}</p>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            @if (! empty($data['action_url']))
                                <a href="{{ $data['action_url'] }}" class="text-[11px] font-bold text-amber-700 hover:underline dark:text-amber-400">Open &rarr;</a>
                            @else
                                <span></span>
                            @endif
                            @if ($isUnread)
                                <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="text-[10px] font-bold text-stone-500 hover:text-stone-900 dark:hover:text-white cursor-pointer">
                                    Mark read
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-stone-500 dark:text-stone-400">
                        You're all caught up. No notifications yet.
                    </p>
                @endforelse
            </div>
        </div>
    @endif
</div>
