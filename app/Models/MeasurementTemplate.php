<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementTemplate extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'system_fields', 'custom_field_ids',
        'is_default', 'is_active',
    ];

    protected $casts = [
        'system_fields' => 'array',
        'custom_field_ids' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function customers()
    {
        return $this->hasMany(Customers::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
