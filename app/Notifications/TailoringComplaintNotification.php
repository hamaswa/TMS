<?php

namespace App\Notifications;

use App\Models\CounterOrderComplaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TailoringComplaintNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CounterOrderComplaint $complaint, private readonly string $event) {}

    public function via(object $notifiable): array { return ['database']; }

    public function databaseType(object $notifiable): string { return 'tailoring_complaint'; }

    public function toArray(object $notifiable): array
    {
        return [
            'Order_Type' => 'Tailoring Complaint',
            'order_id' => $this->complaint->order_id,
            'complaint_id' => $this->complaint->id,
            'status' => $this->complaint->status,
            'resolution' => $this->complaint->resolution,
            'message' => match ($this->event) {
                'opened' => 'Your tailoring complaint has been recorded for review.',
                'rework' => 'Rework has been approved for your tailoring order.',
                'rejected' => 'Your tailoring complaint review has been completed.',
                default => 'Your tailoring complaint has been resolved.',
            },
            'acknowledge_url' => URL::temporarySignedRoute('tailoring-complaints.acknowledge', now()->addDays(30), ['complaint' => $this->complaint->id]),
        ];
    }
}
