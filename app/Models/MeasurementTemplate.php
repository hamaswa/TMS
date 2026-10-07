<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementTemplate extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'system_fields', 'custom_field_ids',
        'is_builtin', 'field_layout', 'layout_columns', 'is_default', 'is_active',
    ];

    protected $casts = [
        'system_fields' => 'array',
        'custom_field_ids' => 'array',
        'is_builtin' => 'boolean',
        'field_layout' => 'array',
        'layout_columns' => 'integer',
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

    public function standardProfiles()
    {
        return $this->hasMany(StandardMeasurementProfile::class)
            ->orderBy('sort_order')->orderBy('name');
    }

    public function customFields()
    {
        return $this->hasMany(MeasurementField::class)->orderBy('sort_order')->orderBy('label');
    }

    public function templateOptions()
    {
        return $this->hasMany(Options::class)->orderBy('Name');
    }
}
