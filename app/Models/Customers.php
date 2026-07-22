<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


/**
 * @property integer $id
 * @property string $name
 * @property string $phone_number1
 * @property string $phone_number2
 * @property string $ref_phone_number
 * @property string $shirtlength
 * @property string $sleeve
 * @property string $sleevetop
 * @property string $shoulder
 * @property string $chest
 * @property string $shirtbottomwidth
 * @property string $shalwarlength
 * @property string $shalwarwidth
 * @property string $shalwarbottomopening
 * @property string $chestplatelength
 * @property string $chestplatewidth
 * @property string $neckwidth
 * @property string $neckheight
 * @property integer $comments
 * @property string $created_at
 * @property string $updated_at
 * @property CustomerOption[] $customerOptions
 */
class Customers extends Authenticatable
{
    use HasApiTokens, SoftDeletes, Notifiable;
    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['name','parent_id', 'phone_number1', 'phone_number2', 'ref_phone_number',
                           'shirtlength', 'sleeve', 'sleevetop', 'shoulder', 'chest',
                           'senaChorai','necktype', 'comments', 'created_at', 'updated_at',
                           'jeab','teraa','length','button','shirtbutton','damanchorai','chuta',
                           'swingtype','arms','user_id','pancha','shalwarGheer','shalwar','note','plate_type','Daaman'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customerOptions()
    {
        return $this->hasMany('App\CustomerOption');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,'customerId','id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class,'customerId','id');
    }

    public function servernotifi()
    {
        return $this->hasMany(ServerNotifications::class,'customer_id');
    }
}
