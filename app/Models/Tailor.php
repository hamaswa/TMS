<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @property integer $id
 * @property string $Name
 * @property string $phone_number1
 * @property string $created_at
 * @property string $updated_at
 */
class Tailor extends Model
{
    use HasFactory;

    protected $fillable = ['name','phone_number1','updated_at','user_id','email','password','advance'];

    public function orders()
    {
        return $this->hasMany(Order::class,'tailorId','id');
    }

    public function customers()
    {
        return $this->belongsTo(Customers::class,'customerId','id');
    }

    public function tailorsalary()
    {
        return $this->hasMany("App\Models\Tailorsalary");
    }

    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction','id','userId');
    }

    public function tailortransactions()
    {
        return $this->hasMany(Transaction::class, 'tailorId', 'id');
    }

}
