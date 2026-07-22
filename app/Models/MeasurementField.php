<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementField extends Model
{
    public const TYPES = ['number', 'text', 'select'];
    public const UNITS = ['inch', 'cm', 'none'];

    protected $fillable = [
        'user_id', 'label', 'key', 'field_type', 'unit', 'options',
        'is_required', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values()
    {
        return $this->hasMany(CustomerMeasurementValue::class);
    }
}
