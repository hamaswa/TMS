<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkType extends Model
{
    protected $fillable = ['user_id', 'code', 'name', 'category', 'is_system', 'active'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'active' => 'boolean'];
    }

    public function workers()
    {
        return $this->belongsToMany(ProductionWorker::class, 'production_worker_skills')->withTimestamps();
    }
}
