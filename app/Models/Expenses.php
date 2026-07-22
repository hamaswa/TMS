<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory;

    protected $fillable = ['Monthly_Bill', 'Monthly_Rent', 'expense_day', 'expense_date', 'Extra_Expenses','user_id'];
}
