<?php

namespace App\Notifications;

use App\Models\StorefrontInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStorefrontTailoringBookingNotification extends Notification
{
    use Queueable;

    public function __construct(private StorefrontInquiry $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'storefront_tailoring_booking',
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'service' => $this->booking->service?->name,
            'customer_name' => $this->booking->customer_name,
            'status' => $this->booking->status,
            'message' => 'نئی آن لائن ٹیلرنگ بکنگ موصول ہوئی ہے۔',
        ];
    }
}
