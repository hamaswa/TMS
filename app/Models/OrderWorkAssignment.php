<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderWorkAssignment extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'production_worker_id', 'work_type_id',
        'compensation_plan_id', 'legacy_key', 'active_assignment_key', 'quantity', 'rate', 'amount',
        'status', 'assigned_at', 'completed_at', 'notes',
    ];

    public static function activeKey(int $userId, int $orderId, int $workerId, int $workTypeId): string
    {
        return implode(':', [$userId, $orderId, $workerId, $workTypeId]);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3', 'rate' => 'decimal:2', 'amount' => 'decimal:2',
            'assigned_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function worker()
    {
        return $this->belongsTo(ProductionWorker::class, 'production_worker_id');
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
