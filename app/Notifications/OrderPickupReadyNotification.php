<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPickupReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $orderId;

    public int $orderItemId;

    public string $orderNumber;

    public string $shelfBinCode;

    public function __construct(
        OrderItem $item,
        public bool $isPartiallyReady = false,
    ) {
        $item->loadMissing('order');
        $this->orderId = $item->order_id;
        $this->orderItemId = $item->id;
        $this->orderNumber = $item->order->order_number ?? "ORD-{$item->order_id}";
        $this->shelfBinCode = (string) ($item->shelf_bin_code ?? 'Counter Bay');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject((string) $payload['title'])
            ->line((string) $payload['message'])
            ->action('Track My Order', (string) $payload['action_url']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $prefix = $this->isPartiallyReady ? 'Item Ready for Early Pickup' : 'Order Ready for Pickup';

        return [
            'category' => 'pickup',
            'title' => "{$prefix}: {$this->orderNumber}",
            'message' => "Item staged at Shelf/Bin {$this->shelfBinCode}. Please bring your tracking code and settle any remaining balance.",
            'order_id' => $this->orderId,
            'order_item_id' => $this->orderItemId,
            'shelf_bin_code' => $this->shelfBinCode,
            'is_partially_ready' => $this->isPartiallyReady,
            'action_url' => route('customer.dashboard'),
        ];
    }
}
