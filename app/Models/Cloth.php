<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cloth extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'cloth_type_id',
        'cloth_brand_id',
        'length',
        'price',
        'sale_price',
        'user_id',
        'stock_code',
    ];

    protected static function booted(): void
    {
        static::created(function (Cloth $cloth) {
            if (! $cloth->stock_code) {
                $cloth->forceFill(['stock_code' => self::makeStockCode($cloth)])->saveQuietly();
            }
        });
    }

    public static function makeStockCode(Cloth $cloth): string
    {
        return sprintf('CLT-%d-%06d', (int) $cloth->user_id, (int) $cloth->id);
    }

    public function type()
    {
        return $this->belongsTo('App\Models\ClothType', 'cloth_type_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\ClothBrand', 'cloth_brand_id', 'id');
    }

    public function stocks()
    {
        return $this->hasMany('App\Models\Stock');
    }

    public function colors()
    {
        return $this->hasMany(ClothColor::class);
    }

    public function images()
    {
        return $this->hasMany(ClothImage::class);
    }

    public function videos()
    {
        return $this->hasMany(ClothVideo::class);
    }

    public function storefrontListings()
    {
        return $this->hasMany(StorefrontClothingListing::class);
    }
}
