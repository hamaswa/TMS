<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontTailoringService extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'name',
        'description',
        'price_from',
        'price_unit',
        'estimated_days',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'estimated_days' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function inquiries()
    {
        return $this->hasMany(StorefrontInquiry::class, 'tailoring_service_id');
    }
}
