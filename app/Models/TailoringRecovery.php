<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TailoringRecovery extends Model
{
    public const METHODS = ['unpaid_earnings', 'future_pay', 'security_deposit', 'cash_recovered', 'shop_absorbed'];

    protected $fillable = ['complaint_id', 'order_id', 'tailor_id', 'user_id', 'recorded_by_user_id', 'method', 'amount', 'status', 'note', 'recovered_at'];

    protected $casts = ['amount' => 'decimal:2', 'recovered_at' => 'datetime'];

    public function complaint() { return $this->belongsTo(CounterOrderComplaint::class, 'complaint_id'); }
    public function tailor() { return $this->belongsTo(Tailor::class); }
}
