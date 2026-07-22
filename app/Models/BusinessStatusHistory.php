<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'business_id', 'from_status', 'to_status', 'changed_by_user_id', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
