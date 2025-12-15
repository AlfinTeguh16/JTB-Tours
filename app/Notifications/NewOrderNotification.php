<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue; // Optional if we want queueing later
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderNotification extends Notification
{
    use Queueable;

    public $order;
    public $creator;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $creator)
    {
        $this->order = $order;
        $this->creator = $creator;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; 
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'created_by' => $this->creator ? $this->creator->name : 'System',
            'message' => 'Order baru #' . $this->order->id . ' telah dibuat oleh ' . ($this->creator ? $this->creator->name : 'System'),
            'link' => route('orders.show', $this->order->id), // Ensure route exists or is correct
        ];
    }
}
