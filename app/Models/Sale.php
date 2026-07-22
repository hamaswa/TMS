<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function detail()
    {
        return $this->hasMany("App\Models\Saledetail");
    }


    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'sale_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }
}
