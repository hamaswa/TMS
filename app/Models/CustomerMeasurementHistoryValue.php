<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMeasurementHistoryValue extends Model
{
    protected $fillable = [
        'customer_measurement_history_id', 'measurement_field_id', 'source_key',
        'label', 'value', 'unit', 'sort_order',
    ];

    public function history()
    {
        return $this->belongsTo(CustomerMeasurementHistory::class, 'customer_measurement_history_id');
    }
}
