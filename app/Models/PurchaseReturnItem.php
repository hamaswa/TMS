<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = ['purchase_return_id', 'purchase_item_id', 'cloth_color_id', 'quantity', 'unit_cost', 'line_total'];
}
