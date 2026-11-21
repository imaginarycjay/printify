<?php

namespace App\Notifications;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrepressActionRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $orderId;

    public int $orderItemId;

    public string $orderNumber;

    public function __construct(
        OrderItem $item,
        public string $event,
        public ?string $reason = null,
    ) {
        $item->loadMissing('order');
        $this->orderId = $item->order_id;
        $this->orderItemId = $item->id;
        $this->orderNumber = $item->order->order_number ?? "ORD-{$item->order_id}";
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
            ->action('Review Order', (string) $payload['action_url']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isOperator = $notifiable instanceof User && ($notifiable->isOwner() || $notifiable->isStaff());
        $actionUrl = $isOperator ? route('staff.dashboard') : route('customer.dashboard');

        [$title, $message] = match ($this->event) {
            'rejected' => [
                "Artwork Re-upload Needed: {$this->orderNumber}",
                $this->reason !== null && $this->reason !== ''
                    ? "Prepress rejected artwork: {$this->reason}"
                    : 'Your artwork was rejected during prepress inspection. Please upload a corrected PDF.',
            ],
            'shifted_to_shop_paper' => [
                "Shifted to Shop Paper: {$this->orderNumber}",
                $this->reason !== null && $this->reason !== ''
                    ? "Supplied paper rejected ({$this->reason}); job shifted to full shop paper with updated total."
                    : 'Supplied paper was shifted to full shop paper with updated total.',
            ],
            default => [
                "Corrected Artwork Uploaded: {$this->orderNumber}",
                'Customer uploaded a revised artwork PDF ready for prepress verification.',
            ],
        };

        return [
            'category' => 'prepress',
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'order_id' => $this->orderId,
            'order_item_id' => $this->orderItemId,
            'action_url' => $actionUrl,
        ];
    }
}
