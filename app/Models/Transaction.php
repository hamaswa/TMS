<?php

namespace App\Models;

use App\Models\Saledetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable=['remainingBalance','recivedPayment','customerId','userId','orderId','tailorId','comment','Order_type','sale_id'];



     public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class, 'sale_id', 'id');
     }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customerId');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
