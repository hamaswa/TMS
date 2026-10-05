<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontCartTailoringItem extends Model
{
    protected $fillable = [
        'storefront_cart_id', 'tailoring_service_id', 'clothing_cart_item_id',
        'measurement_template_id', 'standard_measurement_profile_id', 'measurement_method', 'standard_size', 'quantity',
        'unit_price_snapshot', 'measurement_values', 'notes', 'preferred_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'measurement_values' => 'array',
        'preferred_date' => 'date',
    ];

    public function cart() { return $this->belongsTo(StorefrontCart::class, 'storefront_cart_id'); }
    public function service() { return $this->belongsTo(StorefrontTailoringService::class, 'tailoring_service_id'); }
    public function clothingItem() { return $this->belongsTo(StorefrontCartItem::class, 'clothing_cart_item_id'); }
    public function measurementTemplate() { return $this->belongsTo(MeasurementTemplate::class); }
    public function standardMeasurementProfile() { return $this->belongsTo(StandardMeasurementProfile::class); }

    public function getLineTotalAttribute(): float
    {
        return round((float) $this->unit_price_snapshot * (int) $this->quantity, 2);
    }
}
