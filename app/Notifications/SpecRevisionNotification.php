<?php

namespace App\Notifications;

use App\Models\SpecRevision;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpecRevisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $specRevisionId;

    public int $orderId;

    public string $orderNumber;

    public float $deltaAmount;

    public function __construct(
        SpecRevision $revision,
        public string $event,
        public ?string $detail = null,
    ) {
        $revision->loadMissing('orderItem.order');
        $order = $revision->orderItem?->order;
        $this->specRevisionId = $revision->id;
        $this->orderId = $order->id ?? 0;
        $this->orderNumber = $order->order_number ?? 'Order';
        $this->deltaAmount = round((float) $revision->delta_amount, 2);
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
            ->action('View Revision', (string) $payload['action_url']);
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
            'requested' => [
                "Spec Revision Requested: {$this->orderNumber}",
                'A specification revision was submitted and placed the item on hold awaiting shop approval.',
            ],
            'applied' => [
                "Spec Revision Approved: {$this->orderNumber}",
                'Your pre-print specification revision was approved. Price delta: ₱'.number_format($this->deltaAmount, 2).'.',
            ],
            'refund_settled' => [
                "Manual Refund Settled: {$this->orderNumber}",
                $this->detail !== null && $this->detail !== ''
                    ? $this->detail
                    : 'Your manual refund from specification downgrade has been settled.',
            ],
            default => [
                "Spec Revision Declined: {$this->orderNumber}",
                $this->detail !== null && $this->detail !== ''
                    ? "Reason: {$this->detail}"
                    : 'Your specification revision request was declined and original specs were restored.',
            ],
        };

        return [
            'category' => 'spec_revision',
            'event' => $this->event,
            'title' => $title,
            'message' => $message,
            'spec_revision_id' => $this->specRevisionId,
            'order_id' => $this->orderId,
            'action_url' => $actionUrl,
        ];
    }
}
