<?php

namespace App\Models;

use App\Models\Cloth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClothImage extends Model
{
    use HasFactory;
    protected $fillable = ['cloth_id', 'images','image_color','user_id'];

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }
}
