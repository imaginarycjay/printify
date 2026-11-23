<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';
    public mixed $avatarUpload = null;

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatarUpload' => ['nullable', 'image', 'max:3072'], // 3MB Max
        ]);

        $user->name = $this->name;

        if ($this->email !== $user->email) {
            $user->email = $this->email;
            $user->email_verified_at = null;
        }

        if ($this->avatarUpload) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $this->avatarUpload->store('avatars', 'public');
            $user->avatar = $path;
            $this->avatarUpload = null;
        }

        $user->save();

        $this->dispatch('toast', message: 'Profile updated successfully!');
    }

    /**
     * Remove custom avatar image and reset to default initials.
     */
    public function removeAvatar(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        $this->dispatch('toast', message: 'Profile picture removed.');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    <x-pages::settings.layout :heading="__('Profile Details & Avatar')" :subheading="__('Update your account name, email address, and profile picture avatar')">
        <form wire:submit.prevent="updateProfileInformation" class="space-y-6">

            <!-- Profile Picture Avatar Section -->
            <div class="pq-well space-y-3 p-4">
                <label class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Profile Picture / Avatar</label>

                <div class="flex items-center gap-4">
                    <!-- Current Avatar Preview or Temp File Preview -->
                    <div class="relative shrink-0">
                        @if ($avatarUpload)
                            <img src="{{ $avatarUpload->temporaryUrl() }}" class="size-16 rounded-full object-cover border-2 border-blue-600 shadow-xs" />
                        @elseif (auth()->user()?->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" class="size-16 rounded-full object-cover border-2 border-blue-600/60 shadow-xs" />
                        @else
                            <span class="size-16 rounded-full bg-slate-900 text-white dark:bg-blue-600 text-xl font-extrabold flex items-center justify-center shadow-xs">
                                {{ auth()->user()?->initials() }}
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold cursor-pointer transition-colors inline-flex items-center gap-1.5 shadow-xs dark:hover:bg-blue-500">
                                <flux:icon name="arrow-up-tray" class="size-3.5" />
                                <span>Upload New Photo</span>
                                <input type="file" wire:model="avatarUpload" accept="image/*" class="hidden" />
                            </label>

                            @if (auth()->user()?->avatar)
                                <button type="button" wire:click="removeAvatar" class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 text-xs font-bold transition-colors cursor-pointer">
                                    Remove Photo
                                </button>
                            @endif
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">JPG, PNG, GIF up to 3MB</p>
                    </div>
                </div>

                @error('avatarUpload')
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Name Field -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Full Name</label>
                <input
                    wire:model="name"
                    type="text"
                    required
                    class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-blue-600 focus:outline-none"
                />
                @error('name')
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Email Address</label>
                <input
                    wire:model="email"
                    type="email"
                    required
                    class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-blue-600 focus:outline-none"
                />
                @error('email')
                    <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">{{ $message }}</p>
                @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="p-3 rounded-xl pq-badge-warning text-xs space-y-1 mt-2">
                        <p>{{ __('Your email address is unverified.') }}</p>
                        <button type="button" wire:click.prevent="resendVerificationNotification" class="font-bold underline cursor-pointer">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="text-emerald-700 dark:text-emerald-400 font-bold mt-1">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer dark:hover:bg-blue-500">
                    Save Profile Changes
                </button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <div class="mt-10 border-t border-slate-200 dark:border-slate-800 pt-6">
                <livewire:pages::settings.delete-user-form />
            </div>
        @endif
    </x-pages::settings.layout>
</section>
