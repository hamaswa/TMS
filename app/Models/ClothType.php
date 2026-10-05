<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothType extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'name_ur',
        'name_en',
        'type_slug',
        'user_id'
    ];

    public function localizedName(?string $locale = null): string
    {
        $locale = in_array($locale ?: app()->getLocale(), ['ur', 'en'], true) ? ($locale ?: app()->getLocale()) : 'ur';

        return $this->getAttribute('name_'.$locale) ?: $this->name;
    }
}
