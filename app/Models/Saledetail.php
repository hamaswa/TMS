<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;

class Saledetail extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function sale()
    {
        return $this->belongsTo("App\Models\Sale");
    }
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'sale_id', 'id');
    }
}
