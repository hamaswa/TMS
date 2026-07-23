<?php

namespace App\Models;

use App\Models\Cloth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ClothImage extends Model
{
    use HasFactory;
    protected $fillable = ['cloth_id', 'images','image_color','user_id'];

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->images) {
            return null;
        }

        if (Storage::disk('public')->exists($this->images)) {
            return Storage::disk('public')->url($this->images);
        }

        $legacyPath = ltrim($this->images, '/');

        return is_file(public_path($legacyPath)) ? asset($legacyPath) : null;
    }
}
