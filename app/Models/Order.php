<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'sub_customer', 'suitNum', 'designPrice', 'design','customerId', 'suitQuantity', 'totalPayment', 'userId', 'returnDate', 'tailorId', 'rateId', 'remarks', 'tailor_price'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'orderId', 'id');
    }

    public function tailor()
    {
        return $this->belongsTo(Tailor::class, 'tailorId', 'id');
    }

    public function customers()
    {
        return $this->belongsTo(Customers::class, 'sub_customer', 'id');
    }

    public function rate()
    {
        return $this->belongsTo('App\Models\Tailorsalary','rateId','id');
    }
    public function tailorRecords()
    {
        return $this->hasMany(TailorRecord::class, 'order_id', 'id');
    }
}
