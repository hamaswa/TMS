<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterOrderComplaint extends Model
{
    public const OPEN_STATUSES = ['pending', 'investigating', 'approved', 'rework'];

    protected $fillable = ['counter_order_id', 'counter_order_item_id', 'order_id', 'user_id',
        'created_by_user_id', 'reason', 'category', 'inspection_notes', 'responsible_party',
        'responsible_tailor_id', 'resolution', 'decision_notes', 'rework_due_on',
        'rework_completed_at', 'approved_by_user_id', 'approved_at', 'closed_at', 'customer_acknowledged_at', 'requested_tailoring_refund',
        'requested_cloth_compensation', 'status', 'approved_tailoring_refund',
        'approved_cloth_compensation', 'actual_cash_payout', 'payment_method',
        'payment_reference', 'settled_by_user_id', 'settled_at'];

    public function item() { return $this->belongsTo(CounterOrderItem::class, 'counter_order_item_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function responsibleTailor() { return $this->belongsTo(Tailor::class, 'responsible_tailor_id'); }
    public function evidence() { return $this->hasMany(TailoringComplaintEvidence::class, 'complaint_id'); }
    public function recoveries() { return $this->hasMany(TailoringRecovery::class, 'complaint_id'); }

    protected function casts(): array
    {
        return ['rework_due_on' => 'date', 'rework_completed_at' => 'datetime', 'approved_at' => 'datetime', 'settled_at' => 'datetime', 'closed_at' => 'datetime', 'customer_acknowledged_at' => 'datetime'];
    }
}
