<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredOrderFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pressqueue:purge-expired-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired customer artwork files from private storage past each shop retention window while preserving order receipts.';

    public function __construct(
        private AuditLogger $auditLogger,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = PlatformSetting::current();
        $purgedCount = 0;

        OrderItem::query()
            ->whereNull('file_purged_at')
            ->whereNotNull('document_file_path')
            ->whereHas('order', function ($query): void {
                $query->where('order_status', Order::STATUS_COMPLETED)
                    ->whereNotNull('completed_at')
                    ->whereDoesntHave('items', function ($itemQuery): void {
                        $itemQuery->whereIn('production_stage', [
                            OrderItem::STAGE_QUEUE,
                            OrderItem::STAGE_ON_HOLD,
                            OrderItem::STAGE_PRINTING,
                            OrderItem::STAGE_BINDING,
                            OrderItem::STAGE_QUALITY_CHECK,
                            OrderItem::STAGE_READY_FOR_PICKUP,
                        ]);
                    });
            })
            ->with(['order.printShop'])
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($settings, &$purgedCount): void {
                foreach ($items as $item) {
                    $order = $item->order;
                    $shop = $order?->printShop;

                    if (! $order instanceof Order || $shop === null || $order->completed_at === null) {
                        continue;
                    }

                    $retentionDays = $shop->effectiveFileRetentionDays($settings);
                    $cutoff = now()->subDays($retentionDays);

                    if ($order->completed_at->gt($cutoff)) {
                        continue;
                    }

                    $path = $item->document_file_path;
                    if (is_string($path) && $path !== '' && ! str_contains($path, '..') && Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }

                    $item->update(['file_purged_at' => now()]);
                    $purgedCount++;

                    $this->auditLogger->log(
                        action: 'files.purged',
                        auditable: $item,
                        printShopId: $shop->id,
                        metadata: [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'retention_days' => $retentionDays,
                            'completed_at' => $order->completed_at->toIso8601String(),
                        ],
                    );
                }
            });

        $this->info("Purged {$purgedCount} expired order artwork file(s).");

        return self::SUCCESS;
    }
}
