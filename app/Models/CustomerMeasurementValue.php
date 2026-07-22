<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMeasurementValue extends Model
{
    protected $fillable = ['customer_id', 'measurement_field_id', 'value'];

    public function field()
    {
        return $this->belongsTo(MeasurementField::class, 'measurement_field_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
}
