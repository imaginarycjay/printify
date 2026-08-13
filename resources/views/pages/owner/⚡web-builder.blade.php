<?php

use App\Models\PrintShop;
use Livewire\Component;

new class extends Component {
    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }

    public string $storefront_title = '';

    public string $tagline = 'Fast, high-quality printing & binding services for students and professionals.';

    public string $announcement_banner = '🎉 Special Discount: 10% off on Thesis Hardbound printing this week!';

    public string $primary_color = 'amber';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->printShop) {
            $this->storefront_title = $user->printShop->name;
        } else {
            $this->storefront_title = 'My Custom Print Shop';
        }
    }

    public function saveChanges(): void
    {
        $this->dispatch('toast', message: 'Web Builder: Storefront design and layout settings saved successfully!');
    }
}; ?>

<div class="min-h-screen bg-stone-950 text-stone-100 font-sans antialiased flex flex-col justify-between p-4 sm:p-8 relative overflow-x-hidden selection:bg-amber-500 selection:text-white">
    <!-- Ambient Lighting Glow -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] rounded-full bg-violet-500/15 blur-[140px] pointer-events-none"></div>
    <div class="absolute -bottom-40 right-10 size-[500px] rounded-full bg-indigo-500/10 blur-[140px] pointer-events-none"></div>

    @php
        $user = auth()->user();
        $shop = $user?->printShop;
    @endphp

    <!-- Web Builder Header -->
    <header class="w-full max-w-7xl mx-auto flex items-center justify-between py-4 border-b border-stone-800/80 relative z-20">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="size-10 rounded-xl bg-stone-900 hover:bg-stone-800 border border-stone-800 text-stone-300 hover:text-white flex items-center justify-center transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 via-purple-600 to-indigo-700 text-white font-bold shadow-lg shadow-purple-500/20">
                <flux:icon name="globe-alt" class="size-6" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-extrabold text-white tracking-tight">Web Builder</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-violet-500/20 text-violet-300 border border-violet-500/30">Pre-installed App</span>
                </div>
                <p class="text-[11px] text-stone-400">Customer Storefront Visual Editor & Page Builder</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="px-3.5 py-2 rounded-xl border border-stone-800 bg-stone-900/60 text-xs font-semibold text-stone-300 hover:text-white transition-all">
                Exit to Launcher
            </a>
            <flux:button wire:click="saveChanges" variant="primary" class="bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold px-5 py-2 rounded-xl shadow-lg shadow-purple-500/20">
                <flux:icon name="check" class="size-4 me-1.5" />
                Publish Changes
            </flux:button>
        </div>
    </header>

    <!-- Main Builder Workspace -->
    <main class="w-full max-w-7xl mx-auto my-6 relative z-20 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Control Panel -->
        <div class="lg:col-span-4 space-y-6">
            <div class="rounded-3xl border border-stone-800 bg-stone-900/90 p-6 shadow-xl space-y-5 backdrop-blur-xl">
                <div class="flex items-center gap-2.5 border-b border-stone-800 pb-3">
                    <flux:icon name="adjustments-horizontal" class="size-5 text-violet-400" />
                    <div>
                        <h2 class="text-sm font-bold text-white">Storefront Customization</h2>
                        <p class="text-[11px] text-stone-400">Modify branding & customer UI</p>
                    </div>
                </div>

                <!-- Control 1: Shop Display Name -->
                <flux:field>
                    <flux:label class="text-xs font-semibold text-stone-300">Storefront Title</flux:label>
                    <flux:input wire:model.live="storefront_title" type="text" placeholder="Print Shop Title..." />
                </flux:field>

                <!-- Control 2: Tagline -->
                <flux:field>
                    <flux:label class="text-xs font-semibold text-stone-300">Hero Tagline</flux:label>
                    <flux:textarea wire:model.live="tagline" rows="2" placeholder="Sub-heading description..." />
                </flux:field>

                <!-- Control 3: Top Announcement Banner -->
                <flux:field>
                    <flux:label class="text-xs font-semibold text-stone-300">Top Announcement Banner</flux:label>
                    <flux:input wire:model.live="announcement_banner" type="text" placeholder="Promo banner text..." />
                </flux:field>

                <!-- Control 4: Theme Color Selection -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-stone-300 block">Accent Color Theme</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            wire:click="$set('primary_color', 'amber')"
                            class="p-2 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all {{ $primary_color === 'amber' ? 'border-amber-500 bg-amber-500/20 text-amber-300' : 'border-stone-800 bg-stone-950 text-stone-400' }}"
                        >
                            <span class="size-2.5 rounded-full bg-amber-500"></span> Amber
                        </button>
                        <button
                            wire:click="$set('primary_color', 'violet')"
                            class="p-2 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all {{ $primary_color === 'violet' ? 'border-violet-500 bg-violet-500/20 text-violet-300' : 'border-stone-800 bg-stone-950 text-stone-400' }}"
                        >
                            <span class="size-2.5 rounded-full bg-violet-500"></span> Violet
                        </button>
                        <button
                            wire:click="$set('primary_color', 'emerald')"
                            class="p-2 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all {{ $primary_color === 'emerald' ? 'border-emerald-500 bg-emerald-500/20 text-emerald-300' : 'border-stone-800 bg-stone-950 text-stone-400' }}"
                        >
                            <span class="size-2.5 rounded-full bg-emerald-500"></span> Emerald
                        </button>
                        <button
                            wire:click="$set('primary_color', 'sky')"
                            class="p-2 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all {{ $primary_color === 'sky' ? 'border-sky-500 bg-sky-500/20 text-sky-300' : 'border-stone-800 bg-stone-950 text-stone-400' }}"
                        >
                            <span class="size-2.5 rounded-full bg-sky-500"></span> Sky
                        </button>
                    </div>
                </div>

                <div class="pt-2 border-t border-stone-800">
                    <div class="p-3 rounded-2xl bg-violet-500/10 border border-violet-500/20 text-xs text-violet-300 flex items-start gap-2.5">
                        <flux:icon name="information-circle" class="size-4 shrink-0 mt-0.5" />
                        <p class="leading-relaxed">Web Builder allows you to format customer layout blocks. Live preview updates on the right in real time.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Live Preview Canvas -->
        <div class="lg:col-span-8 space-y-3">
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <span class="size-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-bold text-stone-300 uppercase tracking-wider">Live Customer Preview</span>
                </div>
                <span class="text-[11px] text-stone-500">Desktop Device Frame</span>
            </div>

            <!-- Simulated Browser Canvas Frame -->
            <div class="rounded-3xl border border-stone-800 bg-stone-900 shadow-2xl overflow-hidden">
                <!-- Browser Title Bar -->
                <div class="px-4 py-3 bg-stone-950 border-b border-stone-800 flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="size-3 rounded-full bg-red-500/80"></span>
                        <span class="size-3 rounded-full bg-yellow-500/80"></span>
                        <span class="size-3 rounded-full bg-green-500/80"></span>
                    </div>
                    <div class="flex-1 max-w-sm mx-auto bg-stone-900 border border-stone-800 rounded-lg px-3 py-1 text-center text-[11px] text-stone-400 font-mono truncate">
                        https://printify.store/{{ Str::slug($storefront_title ?: 'shop') }}
                    </div>
                </div>

                <!-- Simulated Storefront Page -->
                <div class="p-6 sm:p-10 space-y-8 bg-stone-950">
                    <!-- Top Announcement Bar -->
                    @if ($announcement_banner)
                        <div class="p-2.5 rounded-xl text-center text-xs font-semibold bg-violet-500/10 border border-violet-500/20 text-violet-300">
                            {{ $announcement_banner }}
                        </div>
                    @endif

                    <!-- Hero Section -->
                    <div class="text-center space-y-4 max-w-xl mx-auto py-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-stone-900 border border-stone-800 text-stone-300">
                            <flux:icon name="building-storefront" class="size-3.5 text-violet-400" />
                            Official Print Storefront
                        </div>
                        <h3 class="text-3xl font-extrabold text-white tracking-tight">
                            {{ $storefront_title }}
                        </h3>
                        <p class="text-xs sm:text-sm text-stone-400 leading-relaxed">
                            {{ $tagline }}
                        </p>

                        <div class="pt-2 flex items-center justify-center gap-3">
                            <button class="px-5 py-2.5 rounded-xl font-bold text-xs bg-amber-500 text-stone-950 shadow-lg shadow-amber-500/20">
                                Browse Printing Services
                            </button>
                            <button class="px-5 py-2.5 rounded-xl font-bold text-xs bg-stone-900 border border-stone-800 text-stone-300">
                                Contact Shop
                            </button>
                        </div>
                    </div>

                    <!-- Services Grid Mockup -->
                    <div class="space-y-3 pt-4 border-t border-stone-800/60">
                        <h4 class="text-xs font-bold text-stone-400 uppercase tracking-wider">Available Services Preview</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="p-3 rounded-2xl bg-stone-900/60 border border-stone-800/80 space-y-2">
                                <div class="size-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                                    <flux:icon name="book-open" class="size-4" />
                                </div>
                                <p class="text-xs font-bold text-white">Thesis Binding</p>
                                <p class="text-[10px] text-stone-400 line-clamp-1">Hardbound & softbound</p>
                            </div>

                            <div class="p-3 rounded-2xl bg-stone-900/60 border border-stone-800/80 space-y-2">
                                <div class="size-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
                                    <flux:icon name="document-text" class="size-4" />
                                </div>
                                <p class="text-xs font-bold text-white">Document Print</p>
                                <p class="text-[10px] text-stone-400 line-clamp-1">Colored & monochrome</p>
                            </div>

                            <div class="p-3 rounded-2xl bg-stone-900/60 border border-stone-800/80 space-y-2">
                                <div class="size-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                                    <flux:icon name="photo" class="size-4" />
                                </div>
                                <p class="text-xs font-bold text-white">Tarpaulin</p>
                                <p class="text-[10px] text-stone-400 line-clamp-1">Large format banners</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Bar -->
    <footer class="w-full max-w-7xl mx-auto flex items-center justify-between text-xs text-stone-600 border-t border-stone-800/80 pt-4 relative z-20">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'Printify') }} &bull; Web Builder App</span>
        <span class="text-stone-500">Core Admin Tool</span>
    </footer>
</div>
