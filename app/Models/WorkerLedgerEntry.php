<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerLedgerEntry extends Model
{
    protected $fillable = [
        'user_id', 'production_worker_id', 'assignment_id', 'legacy_key',
        'entry_type', 'amount', 'entry_date', 'reference_type', 'reference_id', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'entry_date' => 'date'];
    }

    public function worker()
    {
        return $this->belongsTo(ProductionWorker::class, 'production_worker_id');
    }

    public function assignment()
    {
        return $this->belongsTo(OrderWorkAssignment::class, 'assignment_id');
    }
}
