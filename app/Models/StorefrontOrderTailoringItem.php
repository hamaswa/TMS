<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontOrderTailoringItem extends Model
{
    protected $fillable = [
        'storefront_order_id', 'tailoring_service_id', 'clothing_order_item_id',
        'measurement_template_id', 'standard_measurement_profile_id', 'service_name', 'measurement_method', 'standard_size',
        'quantity', 'unit_price', 'line_total', 'estimated_days', 'measurement_values',
        'notes', 'preferred_date',
    ];

    protected $casts = [
        'quantity' => 'integer', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2',
        'estimated_days' => 'integer', 'measurement_values' => 'array', 'preferred_date' => 'date',
    ];

    public function order() { return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id'); }
    public function service() { return $this->belongsTo(StorefrontTailoringService::class, 'tailoring_service_id'); }
    public function clothingItem() { return $this->belongsTo(StorefrontOrderItem::class, 'clothing_order_item_id'); }
    public function measurementTemplate() { return $this->belongsTo(MeasurementTemplate::class); }
    public function standardMeasurementProfile() { return $this->belongsTo(StandardMeasurementProfile::class); }
}
