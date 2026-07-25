<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tailorsalary extends Model
{
    use HasFactory;

    protected $fillable=[
        'tailor_id',
        'options_id',
        'type',
        'price'
    ];

    public function tailor()
    {
        return $this->belongsTo("App\Models\Tailor");
    }

    public function options()
    {
        return $this->belongsTo('App\Models\Options');
    }
}
