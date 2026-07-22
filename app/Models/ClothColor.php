<?php

namespace App\Models;

use App\Models\Cloth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClothColor extends Model
{
    use HasFactory;
    protected $fillable = ['cloth_id', 'color','user_id','length', 'average_unit_cost'];

    protected $casts = ['length' => 'decimal:2', 'average_unit_cost' => 'decimal:4'];

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }
}
