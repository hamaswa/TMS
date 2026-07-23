<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontClothingListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'cloth_id',
        'public_name',
        'description',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->public_name
            ?: collect([$this->cloth?->brand?->name, $this->cloth?->type?->name])->filter()->implode(' — ')
            ?: 'کپڑا';
    }
}
