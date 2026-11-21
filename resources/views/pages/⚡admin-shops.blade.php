<?php

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public string $status = 'pending';

    public function rendering(mixed $view): void
    {
        $view->layout('layouts.blank');
    }
}; ?>

@php
    $settings = PlatformSetting::current();
    $settingsErrors = $errors->getBag('platformSettings');

    $statusCounts = PrintShop::query()
        ->selectRaw('approval_status, COUNT(*) as aggregate')
        ->groupBy('approval_status')
        ->pluck('aggregate', 'approval_status');

    $totalShops = (int) $statusCounts->sum();
    $approvedShops = (int) ($statusCounts['approved'] ?? 0);
    $pendingShops = (int) ($statusCounts['pending'] ?? 0);
    $suspendedShops = (int) ($statusCounts['suspended'] ?? 0);

    $activeArtworkCount = OrderItem::whereNotNull('document_file_path')->count();
    $paymentProofCount = Order::whereNotNull('payment_proof_path')->count();

    $storageBytes = 0;
    try {
        $localDisk = Storage::disk('local');
        foreach ($localDisk->allFiles('orders') as $storedFile) {
            try {
                $storageBytes += $localDisk->size($storedFile);
            } catch (\Throwable) {
                // Skip inaccessible files cleanly.
            }
        }
    } catch (\Throwable) {
        $storageBytes = 0;
    }

    $storageFootprintLabel = $storageBytes >= 1048576
        ? number_format($storageBytes / 1048576, 2).' MB'
        : number_format($storageBytes / 1024, 1).' KB';

    $shops = PrintShop::with('user')
        ->when(
            in_array($this->status, ['pending', 'approved', 'rejected', 'suspended'], true),
            fn ($query) => $query->where('approval_status', $this->status)
        )
        ->latest()
        ->get();

    $recentAudits = AuditLog::with(['actor', 'printShop'])
        ->latest()
        ->limit(10)
        ->get();
@endphp

<div class="min-h-screen bg-stone-100 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
    <x-platform-header title="Platform Administration" subtitle="Tenant governance, retention & storage health" :brand-url="route('admin.shops')" />
    <main class="mx-auto w-full max-w-6xl space-y-8 px-4 py-8 sm:px-6">
        <x-platform-feedback />

        <div class="space-y-2">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Tenant Governance &amp; Storage Health</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">Approve, moderate, or suspend print shops and configure platform-wide file retention without accessing private shop financial ledgers.</p>
        </div>

        <section aria-label="Storage health and tenant summary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Registered Tenants</p>
                <p class="mt-2 text-2xl font-extrabold">{{ $totalShops }}</p>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $approvedShops }} approved &bull; {{ $pendingShops }} pending &bull; {{ $suspendedShops }} suspended</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Private Order Files</p>
                <p class="mt-2 text-2xl font-extrabold">{{ $activeArtworkCount + $paymentProofCount }}</p>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $activeArtworkCount }} artwork files &bull; {{ $paymentProofCount }} payment proofs</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Private Disk Footprint</p>
                <p class="mt-2 text-2xl font-extrabold">{{ $storageFootprintLabel }}</p>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Isolated local storage under <code class="font-mono">orders/</code></p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Active Retention Window</p>
                <p class="mt-2 text-2xl font-extrabold">{{ $settings->default_retention_days }} days</p>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Allowed {{ $settings->min_retention_days }}&ndash;{{ $settings->max_retention_days }} days &bull; Max {{ $settings->max_upload_mb }} MB upload</p>
            </div>
        </section>

        <section aria-labelledby="retention-policy-heading" class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-200 pb-4 dark:border-stone-800">
                <div>
                    <h2 id="retention-policy-heading" class="text-lg font-extrabold">Global File Retention &amp; Upload Policy</h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Controls default artwork purge TTL, allowable per-shop retention bounds, and maximum customer upload size.</p>
                </div>
                <flux:badge color="amber" size="sm">System-wide defaults</flux:badge>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <flux:input
                    type="number"
                    min="1"
                    max="365"
                    name="min_retention_days"
                    label="Minimum retention (days)"
                    :error:bag="'platformSettings'"
                    :invalid="$settingsErrors->has('min_retention_days')"
                    value="{{ old('min_retention_days', $settings->min_retention_days) }}"
                    required
                />
                <flux:input
                    type="number"
                    min="1"
                    max="365"
                    name="default_retention_days"
                    label="Default retention (days)"
                    :error:bag="'platformSettings'"
                    :invalid="$settingsErrors->has('default_retention_days')"
                    value="{{ old('default_retention_days', $settings->default_retention_days) }}"
                    required
                />
                <flux:input
                    type="number"
                    min="1"
                    max="365"
                    name="max_retention_days"
                    label="Maximum retention (days)"
                    :error:bag="'platformSettings'"
                    :invalid="$settingsErrors->has('max_retention_days')"
                    value="{{ old('max_retention_days', $settings->max_retention_days) }}"
                    required
                />
                <flux:input
                    type="number"
                    min="1"
                    max="250"
                    name="max_upload_mb"
                    label="Max upload size (MB)"
                    :error:bag="'platformSettings'"
                    :invalid="$settingsErrors->has('max_upload_mb')"
                    value="{{ old('max_upload_mb', $settings->max_upload_mb) }}"
                    required
                />
                <div class="flex items-center justify-between gap-3 sm:col-span-2 lg:col-span-4 border-t border-stone-200 pt-4 dark:border-stone-800">
                    <p class="text-xs text-stone-500 dark:text-stone-400">Financial ledgers and revenue figures remain isolated per shop tenant.</p>
                    <flux:button type="submit" variant="primary" class="!bg-amber-500 !text-stone-950">Save retention policy</flux:button>
                </div>
            </form>
        </section>

        <section aria-labelledby="shop-applications-heading" class="space-y-4">
            <div>
                <h2 id="shop-applications-heading" class="text-xl font-extrabold">Shop Applications &amp; Tenant Moderation</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Review each shop's setup and contact details before publishing, or suspend non-compliant tenants.</p>
            </div>

            <nav aria-label="Filter applications" class="flex flex-wrap gap-2">
                @foreach (['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Needs changes', 'suspended' => 'Suspended', '' => 'All shops'] as $key => $label)
                    <a href="{{ route('admin.shops', ['status' => $key]) }}" wire:navigate @if($this->status === $key) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-bold {{ $this->status === $key ? 'border-amber-500 bg-amber-500 text-stone-950' : 'border-stone-200 bg-white text-stone-600 hover:border-amber-500 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="grid items-start gap-5 lg:grid-cols-2">
                @forelse ($shops as $shop)
                    @php
                        $shopStatus = (string) $shop->getAttribute('approval_status');
                    @endphp
                    <article wire:key="application-{{ $shop->id }}" class="space-y-5 rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-words text-lg font-extrabold">{{ $shop->name }}</h3>
                                <p class="break-words text-sm text-stone-500 dark:text-stone-400">{{ $shop->user?->email }}</p>
                            </div>
                            <flux:badge :color="match ($shopStatus) { 'approved' => 'emerald', 'rejected' => 'red', 'suspended' => 'zinc', default => 'amber' }" size="sm">{{ ucfirst($shopStatus) }}</flux:badge>
                        </div>

                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs font-semibold text-stone-500 dark:text-stone-400">Address</dt><dd class="mt-1">{{ $shop->getAttribute('address') ?: 'Not provided' }}</dd></div>
                            <div><dt class="text-xs font-semibold text-stone-500 dark:text-stone-400">Contact</dt><dd class="mt-1">{{ $shop->getAttribute('contact') ?: 'Not provided' }}</dd></div>
                        </dl>

                        @if ($shop->getAttribute('description'))
                            <p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">{{ $shop->getAttribute('description') }}</p>
                        @endif

                        @if ($shop->getAttribute('rejection_reason'))
                            <flux:callout variant="danger" icon="exclamation-circle">
                                {{ $shopStatus === 'suspended' ? 'Suspension reason: ' : 'Review feedback: ' }}{{ $shop->getAttribute('rejection_reason') }}
                            </flux:callout>
                        @endif

                        @if (! $shop->is_setup_completed)
                            <flux:callout icon="information-circle">Setup incomplete. This owner must finish shop setup before the application can be reviewed.</flux:callout>
                        @else
                            <form method="POST" action="{{ route('admin.shops.review', $shop->id) }}" class="space-y-4 border-t border-stone-200 pt-4 dark:border-stone-800">
                                @csrf
                                <flux:textarea name="reason" label="Feedback or suspension reason" rows="2" placeholder="Required when requesting changes or suspending a shop" />
                                <div class="flex flex-wrap gap-2">
                                    @if ($shop->isSuspended())
                                        <flux:button type="submit" name="decision" value="reactivated" variant="primary" class="!bg-emerald-600 !text-white">Reactivate shop</flux:button>
                                    @elseif ($shop->isApproved())
                                        <flux:button type="submit" name="decision" value="suspended" variant="danger">Suspend shop</flux:button>
                                        <flux:button type="submit" name="decision" value="rejected">Request changes</flux:button>
                                    @else
                                        <flux:button type="submit" name="decision" value="approved" variant="primary" class="!bg-amber-500 !text-stone-950">Approve shop</flux:button>
                                        <flux:button type="submit" name="decision" value="rejected" variant="danger">Request changes</flux:button>
                                        <flux:button type="submit" name="decision" value="suspended">Suspend shop</flux:button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-stone-300 p-12 text-center lg:col-span-2 dark:border-stone-700">
                        <flux:icon name="check-circle" class="mx-auto mb-3 size-10 text-stone-400" />
                        <h3 class="font-bold">No applications in this view</h3>
                        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">Choose another status to see other shop applications.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="audit-log-heading" class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900/80">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-200 pb-4 dark:border-stone-800">
                <div>
                    <h2 id="audit-log-heading" class="text-lg font-extrabold">Recent Governance Audit Log</h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Immutable record of tenant moderation decisions and global policy changes.</p>
                </div>
            </div>

            <div class="divide-y divide-stone-200 dark:divide-stone-800">
                @forelse ($recentAudits as $audit)
                    @php
                        $auditReason = is_array($audit->metadata) ? ($audit->metadata['reason'] ?? null) : null;
                    @endphp
                    <div wire:key="audit-{{ $audit->id }}" class="flex flex-col gap-1 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-0.5 text-sm">
                            <p class="font-bold">
                                <span class="font-mono text-xs uppercase text-amber-700 dark:text-amber-400">{{ $audit->action }}</span>
                                &bull; {{ $audit->printShop?->name ?? 'Platform Policy' }}
                            </p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                By {{ $audit->actor?->name ?? 'System' }}
                                @if ($auditReason)
                                    &mdash; {{ $auditReason }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xs text-stone-500 dark:text-stone-400">{{ $audit->created_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-5 text-sm text-stone-500 dark:text-stone-400">No governance actions recorded yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
