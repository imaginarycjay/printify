<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $orderId;

    public string $orderNumber;

    public float $balanceDue;

    public function __construct(
        Order $order,
        public string $event,
        public ?string $detail = null,
    ) {
        $this->orderId = $order->id;
        $this->orderNumber = $order->order_number;
        $this->balanceDue = round((float) $order->balance_due, 2);
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
            ->action('Open PressQueue', (string) $payload['action_url']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isOwnerOrStaff = $notifiable instanceof User && ($notifiable->isOwner() || $notifiable->isStaff());
        $actionUrl = $isOwnerOrStaff ? route('owner.analytics-hub') : route('customer.dashboard');

        [$title, $message] = match ($this->event) {
            'proof_submitted' => [
                "New Payment Proof: {$this->orderNumber}",
                'A customer submitted a payment proof requiring audit verification.',
            ],
            'verified' => [
                "Payment Verified: {$this->orderNumber}",
                $this->balanceDue > 0.0
                    ? 'Your downpayment has been verified and your order entered the queue. Remaining balance: ₱'.number_format($this->balanceDue, 2).'.'
                    : 'Your payment has been verified in full and your order entered the queue.',
            ],
            'rejected' => [
                "Payment Proof Rejected: {$this->orderNumber}",
                $this->detail !== null && $this->detail !== ''
                    ? "Reason: {$this->detail}. Please upload a valid payment reference."
                    : 'Your payment proof was rejected. Please upload a valid payment reference.',
            ],
            default => [
                "Counter Payment Recorded: {$this->orderNumber}",
                'Payment settled at counter. Remaining balance: ₱'.number_format($this->balanceDue, 2).'.',
            ],
        };

        return [
            'category' => 'payment',
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'action_url' => $actionUrl,
        ];
    }
}
