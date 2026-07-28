<?php

namespace App\Notifications;

use App\Models\BusinessSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public BusinessSubscription $subscription,
        public int $thresholdDays,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $expired = $this->thresholdDays < 0;

        return [
            'type' => 'subscription',
            'subject' => $expired ? 'سبسکرپشن کی میعاد ختم ہو گئی' : 'سبسکرپشن جلد ختم ہونے والی ہے',
            'about' => $expired
                ? 'آپ کی TMS سبسکرپشن کی میعاد ختم ہو چکی ہے۔ تجدید کے لیے سپر ایڈمن سے رابطہ کریں۔'
                : "آپ کی TMS سبسکرپشن {$this->subscription->ends_on->format('d-m-Y')} کو ختم ہوگی۔",
            'subscription_id' => $this->subscription->id,
            'ends_on' => $this->subscription->ends_on->toDateString(),
            'days_remaining' => $this->subscription->daysRemaining(),
            'action_url' => route('admin.subscription.index'),
        ];
    }
}
