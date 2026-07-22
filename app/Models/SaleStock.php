<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleStock extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'cloth_type_id',
        'cloth_brand_id',
        'color',
        'c_name',
        'c_id',
        'phone',
        'user_id',
        'profit',
        'loss',
        'length',
        'sellDate',
        'clothes_rack',
        'selling_price',
    ];

    protected $table = 'sale_stocks';

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
    public function type()
    {
        return $this->belongsTo(ClothType::class,'cloth_type_id','id');
    }

    public function brand()
    {
        return $this->belongsTo(ClothBrand::class,'cloth_brand_id','id');
    }
}
