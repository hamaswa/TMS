<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    protected $casts = [
        'subscriptions' => 'array'
    ];
}
