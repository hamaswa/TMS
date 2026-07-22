<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TailorJobStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $stage,
        private readonly ?string $note = null,
        private readonly ?string $shopName = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'tailor_job_status';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'Shop_Name' => $this->shopName ?: 'Tailor shop',
            'Order_Type' => 'Tailoring',
            'order_id' => $this->order->id,
            'customers' => ['name' => $notifiable->name],
            'suitQuantity' => $this->order->suitQuantity,
            'serialNumber' => $this->order->suitNum,
            'returnDate' => $this->order->returnDate,
            'status' => $this->stage,
            'message' => $this->messageForStage(),
            'note' => $this->note,
        ];
    }

    private function messageForStage(): string
    {
        return match ($this->stage) {
            'cutting' => 'Cutting has started on your order.',
            'trial' => 'Your order is ready for a trial fitting.',
            'ready' => 'Your order is ready for collection.',
            'delivered' => 'Your order has been delivered. Thank you.',
            default => 'Your tailoring order status has been updated.',
        };
    }
}
