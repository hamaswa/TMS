<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionWorker extends Model
{
    protected $fillable = [
        'user_id', 'legacy_tailor_id', 'name', 'phone', 'email',
        'relationship_type', 'active', 'notes',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function legacyTailor()
    {
        return $this->belongsTo(Tailor::class, 'legacy_tailor_id');
    }

    public function skills()
    {
        return $this->belongsToMany(WorkType::class, 'production_worker_skills')->withTimestamps();
    }

    public function compensationPlans()
    {
        return $this->hasMany(WorkerCompensationPlan::class);
    }

    public function assignments()
    {
        return $this->hasMany(OrderWorkAssignment::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(WorkerLedgerEntry::class);
    }
}
