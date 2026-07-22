<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable=[
        'cloth_id',
        'length',
        'per_meter',
        'note',
       'brand_name',
       'user_id'
    ];

    public function cloth()
    {
        return $this->belongsTo('App\Models\Cloth');
    }
    public function saleStocks()
    {
        return $this->hasMany(SaleStock::class, 'stock_id');
    }
}
