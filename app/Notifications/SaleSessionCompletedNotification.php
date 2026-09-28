<?php

namespace App\Notifications;

use App\Models\SaleSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleSessionCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SaleSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sale_session_completed',
            'subject' => 'Sale completed',
            'about' => 'Your sale was completed by '.$this->session->completedBy->name.'.',
            'sale_session_uuid' => $this->session->uuid,
            'receipt_number' => $this->session->receipt?->receipt_number,
        ];
    }
}
