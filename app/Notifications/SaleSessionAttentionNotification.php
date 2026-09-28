<?php

namespace App\Notifications;

use App\Models\SaleSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleSessionAttentionNotification extends Notification
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
            'type' => 'sale_session_attention',
            'subject' => 'Sale agent needs attention',
            'about' => $this->session->agent->name.' requested help with a live sale.',
            'sale_session_uuid' => $this->session->uuid,
            'action_url' => route('admin.sales-sessions.show', $this->session),
        ];
    }
}
