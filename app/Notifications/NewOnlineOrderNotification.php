<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOnlineOrderNotification extends Notification
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }
    public function toArray($notifiable)
    {
        return [
            'type' => 'user',
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'cloth_id' => $this->order->cloth_id,
            'length' => $this->order->length,
            'price' => $this->order->price,
            'status' => 'pending',
            'created_at' => $this->order->created_at,
            'message' => 'A new Order has placed'
        ];
    }
}
