<?php

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockReorderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $inventoryItemId;

    public string $itemName;

    public float $availableQty;

    public float $reorderLevel;

    public string $unit;

    public function __construct(InventoryItem $item)
    {
        $this->inventoryItemId = $item->id;
        $this->itemName = $item->name;
        $this->availableQty = $item->availableQty();
        $this->reorderLevel = round((float) $item->reorder_level, 2);
        $this->unit = $item->unit;
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
        return (new MailMessage)
            ->subject("Low Stock Reorder Alert: {$this->itemName}")
            ->line("Material '{$this->itemName}' has reached its reorder threshold.")
            ->line("Available quantity: {$this->availableQty} {$this->unit} (Reorder level: {$this->reorderLevel} {$this->unit}).")
            ->action('Open Inventory Hub', route('owner.inventory-hub'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'inventory',
            'title' => "Low Stock: {$this->itemName}",
            'message' => "Available {$this->availableQty} {$this->unit} is at/below reorder point ({$this->reorderLevel} {$this->unit}).",
            'inventory_item_id' => $this->inventoryItemId,
            'available_qty' => $this->availableQty,
            'reorder_level' => $this->reorderLevel,
            'action_url' => route('owner.inventory-hub'),
        ];
    }
}
