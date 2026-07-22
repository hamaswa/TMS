<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothBrand extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'user_id',
        'brand_logo',
        'brand_slug',
    ];
    public function stock()
    {
        return $this->hasMany(Stock::class, 'cloth_id');
    }
}
