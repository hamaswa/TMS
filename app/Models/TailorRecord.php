<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TailorRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tailor_id',
        'order_id',
        'amount',
        'comment',
        'Note',
    ];

    // Define the relationship with the Tailor model
    public function tailor()
    {
        return $this->belongsTo(Tailor::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
