<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineOrder extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['user_id','cloth_id','length','price','status','admin_user_id','created_at','cancel_at','color','cost_per_meter','cost_total'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function cloth()
    {
        return $this->belongsTo(Cloth::class,'cloth_id');
    }
}
