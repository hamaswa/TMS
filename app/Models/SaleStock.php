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
        'sale_price_basis',
        'suit_quantity',
        'suit_unit_price',
        'suit_length',
        'cloth_id',
        'cloth_color_id',
        'cost_per_meter',
        'cost_total',
    ];

    protected $table = 'sale_stocks';

    protected $casts = [
        'suit_quantity' => 'decimal:2',
        'suit_unit_price' => 'decimal:2',
        'suit_length' => 'decimal:2',
    ];

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

    public function returnItems()
    {
        return $this->hasMany(CounterSaleReturnItem::class);
    }

    /**
     * How this line was sold and what is left to return, using the same per-suit/per-meter
     * rules as CounterSaleReturnService. Expects returned_quantity from withSum('returnItems').
     */
    public function returnLine(): object
    {
        $perSuit = $this->sale_price_basis === 'per_suit' && (float) $this->suit_quantity > 0;
        $sold = $perSuit ? (float) $this->suit_quantity : (float) $this->length;
        $unitPrice = $perSuit && (float) $this->suit_unit_price > 0 ? (float) $this->suit_unit_price : (float) $this->selling_price;
        $returned = (float) ($this->returned_quantity ?? 0);
        $quantity = fn (float $value) => rtrim(rtrim(number_format($value, 2), '0'), '.');

        return (object) [
            'id' => $this->id,
            'name' => collect([$this->brand?->name, $this->type?->name, $this->color])->filter()->implode(' · ') ?: 'کپڑا #'.$this->id,
            'perSuit' => $perSuit,
            'unit' => $perSuit ? 'سوٹ' : 'میٹر',
            'sold' => $sold,
            'soldLabel' => $perSuit
                ? $quantity($sold).' سوٹ × '.number_format((float) $this->suit_length, 2).' میٹر'
                : number_format($sold, 2).' میٹر',
            'unitPrice' => $unitPrice,
            'lineTotal' => round($sold * $unitPrice, 2),
            'returned' => $returned,
            'returnedLabel' => $quantity($returned),
            'remaining' => max(0, round($sold - $returned, 2)),
        ];
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
