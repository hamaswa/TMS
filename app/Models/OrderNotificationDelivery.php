<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderNotificationDelivery extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'customer_id',
        'stage',
        'channel',
        'status',
        'attempt_count',
        'notification_id',
        'last_error',
        'last_attempted_at',
        'sent_at',
    ];

    protected $casts = [
        'last_attempted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }
}
