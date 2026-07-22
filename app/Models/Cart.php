<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'carts';
    protected $fillable = ['user_id','cloth_id','length','price','shop_name','color'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function cloth()
    {
        return $this->belongsTo(Cloth::class,'cloth_id');
    }
}
