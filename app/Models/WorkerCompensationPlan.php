<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerCompensationPlan extends Model
{
    protected $fillable = [
        'user_id', 'production_worker_id', 'work_type_id', 'legacy_tailor_rate_id',
        'method', 'rate', 'fixed_salary', 'commission_percent',
        'effective_from', 'effective_to', 'active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2', 'fixed_salary' => 'decimal:2',
            'commission_percent' => 'decimal:4', 'effective_from' => 'date',
            'effective_to' => 'date', 'active' => 'boolean',
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
}
