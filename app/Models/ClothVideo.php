<?php

namespace App\Models;

use App\Models\Cloth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClothVideo extends Model
{
    use HasFactory;
    protected $fillable = ['cloth_id', 'video','user_id','video_color'];

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }
}
