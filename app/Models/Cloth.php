<?php

namespace App\Models;

use App\Models\ClothColor;
use App\Models\ClothImage;
use App\Models\ClothVideo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Cloth extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable=[
        'name',
        'set_code',
        'cloth_type_id',
        'cloth_brand_id',
        'length',
        'price',
        'sale_price',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::created(function (Cloth $cloth) {
            if (! $cloth->set_code) {
                $cloth->forceFill([
                    'set_code' => 'BNS-'.str_pad((string) ($cloth->user_id ?? 0), 4, '0', STR_PAD_LEFT).'-'.str_pad((string) $cloth->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6)),
                ])->saveQuietly();
            }
        });
    }

    public function type()
    {
        return $this->belongsTo('App\Models\ClothType','cloth_type_id','id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\ClothBrand','cloth_brand_id','id');
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
