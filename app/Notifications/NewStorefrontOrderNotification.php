<?php

namespace App\Notifications;

use App\Models\StorefrontOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStorefrontOrderNotification extends Notification
{
    use Queueable;

    public function __construct(private StorefrontOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'storefront_order',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'customer_id' => $this->order->customer_id,
            'amount' => (float) $this->order->subtotal,
            'status' => $this->order->status,
            'message' => 'نیا آن لائن کپڑے کا آرڈر موصول ہوا ہے۔',
        ];
    }
}
