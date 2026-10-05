<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardMeasurementProfile extends Model
{
    protected $fillable = [
        'user_id', 'measurement_template_id', 'name', 'measurement_values',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'measurement_values' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function measurementTemplate()
    {
        return $this->belongsTo(MeasurementTemplate::class);
    }
}
