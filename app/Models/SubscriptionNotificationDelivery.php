<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionNotificationDelivery extends Model
{
    protected $fillable = [
        'business_subscription_id',
        'user_id',
        'threshold_days',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }
}
