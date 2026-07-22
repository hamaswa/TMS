<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rack extends Model
{
    use HasFactory;

    protected $fillable = ['rack_no'];
}
