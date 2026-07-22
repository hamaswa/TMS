<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMeasurementValue extends Model
{
    protected $fillable = [
        'order_id', 'measurement_field_id', 'source_key', 'label',
        'value', 'unit', 'sort_order',
    ];

    public function field()
    {
        return $this->belongsTo(MeasurementField::class, 'measurement_field_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
