<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    public function detail()
    {
        return $this->hasMany("App\Models\Saledetail");
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'sale_id', 'id')
            ->where('Order_type', 'Sale');
    }

    public function cancellationTransaction()
    {
        return $this->hasOne(Transaction::class, 'sale_id', 'id')
            ->where('Order_type', 'Sale Cancellation');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }
}
