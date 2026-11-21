<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AgingOperationalAlertNotification;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CheckAgingOperationalAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pressqueue:check-aging-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch alerts for aging unverified payment proofs (>2h) and unclaimed pickup items (>3d).';

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
        $paymentAlerts = 0;
        $pickupAlerts = 0;

        $paymentCutoff = now()->subHours(2);

        Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->where(function ($query) use ($paymentCutoff): void {
                $query->where('submitted_at', '<=', $paymentCutoff)
                    ->orWhere(function ($nested) use ($paymentCutoff): void {
                        $nested->whereNull('submitted_at')->where('created_at', '<=', $paymentCutoff);
                    });
            })
            ->with(['order', 'printShop.user'])
            ->get()
            ->each(function (Payment $payment) use (&$paymentAlerts): void {
                $owner = $payment->printShop?->user;
                $order = $payment->order;
                if (! $owner instanceof User || ! $order instanceof Order) {
                    return;
                }

                $alreadyAlerted = $owner->notifications()
                    ->where('type', AgingOperationalAlertNotification::class)
                    ->where('created_at', '>=', now()->subHours(6))
                    ->get()
                    ->contains(fn (DatabaseNotification $n): bool => ($n->data['alert_type'] ?? '') === 'aging_payment_proof'
                        && (int) ($n->data['meta']['payment_id'] ?? 0) === $payment->id);

                if ($alreadyAlerted) {
                    return;
                }

                $owner->notify(new AgingOperationalAlertNotification(
                    alertType: 'aging_payment_proof',
                    title: "Aging Payment Proof: {$order->order_number}",
                    message: "Payment proof (Ref: {$payment->reference_no}) has been pending verification for over 2 hours.",
                    meta: ['payment_id' => $payment->id, 'order_id' => $order->id],
                    actionUrl: route('owner.analytics-hub'),
                ));

                $this->auditLogger->log(
                    action: 'alerts.aging_payment_proof',
                    auditable: $payment,
                    printShopId: $payment->print_shop_id,
                    metadata: ['order_id' => $order->id, 'payment_id' => $payment->id],
                );

                $paymentAlerts++;
            });

        $pickupCutoff = now()->subDays(3);

        OrderItem::query()
            ->where('production_stage', OrderItem::STAGE_READY_FOR_PICKUP)
            ->whereNull('claimed_at')
            ->whereNotNull('ready_at')
            ->where('ready_at', '<=', $pickupCutoff)
            ->whereHas('order', function ($query): void {
                $query->whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED]);
            })
            ->with(['order.customer'])
            ->get()
            ->each(function (OrderItem $item) use (&$pickupAlerts): void {
                $order = $item->order;
                $customer = $order?->customer;
                if (! $order instanceof Order || ! $customer instanceof User) {
                    return;
                }

                $alreadyReminded = $customer->notifications()
                    ->where('type', AgingOperationalAlertNotification::class)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->get()
                    ->contains(fn (DatabaseNotification $n): bool => ($n->data['alert_type'] ?? '') === 'unclaimed_pickup_reminder'
                        && (int) ($n->data['meta']['order_item_id'] ?? 0) === $item->id);

                if ($alreadyReminded) {
                    return;
                }

                $shelf = $item->shelf_bin_code ?: 'Counter Bay';
                $customer->notify(new AgingOperationalAlertNotification(
                    alertType: 'unclaimed_pickup_reminder',
                    title: "Unclaimed Order Reminder: {$order->order_number}",
                    message: "Your printed item has been ready at Shelf/Bin {$shelf} for over 3 days. Please claim it at your earliest convenience.",
                    meta: ['order_item_id' => $item->id, 'order_id' => $order->id],
                    actionUrl: route('customer.dashboard'),
                ));

                $this->auditLogger->log(
                    action: 'alerts.unclaimed_pickup',
                    auditable: $item,
                    printShopId: $order->print_shop_id,
                    metadata: ['order_id' => $order->id, 'order_item_id' => $item->id, 'shelf_bin_code' => $shelf],
                );

                $pickupAlerts++;
            });

        $this->info("Dispatched {$paymentAlerts} aging proof alert(s) and {$pickupAlerts} pickup reminder(s).");

        return self::SUCCESS;
    }
}
