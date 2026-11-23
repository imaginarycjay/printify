@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or'),
    'position' => 'below',
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    class="contents"
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div class="flex flex-col gap-5">
            <div class="relative {{ $position === 'above' ? 'order-2' : 'order-1' }}">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div data-theme-keep class="w-full border-t border-[var(--auth-border)]"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase tracking-wider">
                    <span data-theme-keep class="bg-[var(--auth-card)] px-2.5 text-slate-500 dark:text-slate-400">
                        {{ $separator }}
                    </span>
                </div>
            </div>

            <div class="grid gap-2 {{ $position === 'above' ? 'order-1' : 'order-2' }}">
                <flux:button
                    variant="outline"
                    icon="finger-print"
                    data-theme-keep
                    class="w-full !border-slate-300 !bg-white !text-slate-800 hover:!bg-slate-50 dark:!border-slate-700 dark:!bg-slate-800/70 dark:!text-slate-100 dark:hover:!bg-slate-800"
                    x-on:click="verify()"
                    x-bind:disabled="loading"
                >
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </flux:button>
                <p x-show="error" x-text="error" x-cloak class="text-center text-xs text-red-600 dark:text-red-400"></p>
            </div>
        </div>
    </template>
</div>
