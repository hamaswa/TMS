<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaliyExpenses extends Model
{
    use HasFactory;

    protected $fillable = ['Expense_name','Expense_payment','user_id'];
}
