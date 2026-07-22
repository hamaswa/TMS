<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMeasurementHistory extends Model
{
    protected $fillable = [
        'user_id', 'customer_id', 'measurement_template_id',
        'recorded_by_user_id', 'source',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function template()
    {
        return $this->belongsTo(MeasurementTemplate::class, 'measurement_template_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function values()
    {
        return $this->hasMany(CustomerMeasurementHistoryValue::class)->orderBy('sort_order');
    }
}
