<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cloth_type_id',
        'counter_sale_receipt_id',
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
        'cloth_id',
        'cloth_color_id',
        'cost_per_meter',
        'cost_total',
    ];

    protected $table = 'sale_stocks';

    public function scopeFinanciallyActive($query)
    {
        return $query->where(function ($nested) {
            $nested->whereNull('counter_sale_receipt_id')
                ->orWhereHas('receipt', fn ($receipt) => $receipt->where('status', 'completed'));
        });
    }

    public function receipt()
    {
        return $this->belongsTo(CounterSaleReceipt::class, 'counter_sale_receipt_id');
    }

    public function clothColor()
    {
        return $this->belongsTo(ClothColor::class, 'cloth_color_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function type()
    {
        return $this->belongsTo(ClothType::class, 'cloth_type_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(ClothBrand::class, 'cloth_brand_id', 'id');
    }
}
