<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerNotifications extends Model
{
    public $fillable = ['user_id','customer_id','message','is_send'];

    public function users()
    {
        return $this->belongsTo(User::class,'user_id');
    }


    public function customer()
    {
        return $this->belongsTo(Customers::class,'customer_id');
    }
}
