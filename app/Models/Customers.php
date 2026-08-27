<?php

namespace App\Models;

use App\Support\PakistanPhoneNumber;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
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
 * @property int $comments
 * @property string $created_at
 * @property string $updated_at
 * @property CustomerOption[] $customerOptions
 */
class Customers extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * Keep the legacy production `Chuta` column compatible with the canonical
     * lowercase `chuta` attribute used throughout the application.
     */
    public function getChutaAttribute($value)
    {
        return $value ?? ($this->attributes['Chuta'] ?? null);
    }

    public function setChutaAttribute($value): void
    {
        $column = array_key_exists('Chuta', $this->attributes)
            && ! array_key_exists('chuta', $this->attributes)
                ? 'Chuta'
                : 'chuta';

        $this->attributes[$column] = $value;
    }

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['name', 'parent_id', 'phone_number1', 'phone_number2', 'ref_phone_number',
        'shirtlength', 'sleeve', 'sleevetop', 'shoulder', 'chest',
        'senaChorai', 'necktype', 'comments', 'created_at', 'updated_at',
        'jeab', 'teraa', 'length', 'button', 'shirtbutton', 'damanchorai', 'chuta',
        'swingtype', 'arms', 'user_id', 'pancha', 'shalwarGheer', 'shalwar', 'note', 'plate_type', 'Daaman',
        'mobile_pin', 'measurement_template_id'];

    protected $hidden = ['mobile_pin', 'pin_failed_attempts', 'pin_locked_until'];

    protected $casts = [
        'pin_locked_until' => 'datetime',
        'pin_changed_at' => 'datetime',
        'self_registered_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'phone_normalization_conflict' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Customers $customer) {
            if ($customer->isDirty('phone_number1') || $customer->isDirty('parent_id')) {
                if ($customer->parent_id !== null) {
                    $customer->phone_number1_normalized = null;
                    $customer->phone_normalization_conflict = true;
                } else {
                    $customer->phone_number1_normalized = PakistanPhoneNumber::normalize($customer->phone_number1);
                    $customer->phone_normalization_conflict = false;
                }
            }
        });
    }

    public static function findByPhoneForOwner(int $ownerId, string $phone): ?self
    {
        $normalized = PakistanPhoneNumber::normalize($phone);
        if (! $normalized) {
            return static::where('user_id', $ownerId)
                ->where('phone_number1', trim($phone))
                ->first();
        }

        $matches = static::where('user_id', $ownerId)
            ->whereNull('parent_id')
            ->where('phone_number1_normalized', $normalized)
            ->limit(2)
            ->get();
        $legacyConflicts = static::where('user_id', $ownerId)
            ->whereNull('parent_id')
            ->where('phone_normalization_conflict', true)
            ->whereNull('phone_number1_normalized')
            ->get()
            ->filter(fn (Customers $customer) => PakistanPhoneNumber::normalize($customer->phone_number1) === $normalized);
        $matches = $matches->concat($legacyConflicts)->unique('id');

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return HasMany
     */
    public function customerOptions()
    {
        return $this->hasMany('App\CustomerOption');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customerId', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customerId', 'id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function storefrontOrders()
    {
        return $this->hasMany(StorefrontOrder::class, 'customer_id');
    }

    public function measurementValues()
    {
        return $this->hasMany(CustomerMeasurementValue::class, 'customer_id');
    }

    public function measurementTemplate()
    {
        return $this->belongsTo(MeasurementTemplate::class);
    }

    public function measurementHistories()
    {
        return $this->hasMany(CustomerMeasurementHistory::class, 'customer_id')->orderByDesc('id');
    }

    public function servernotifi()
    {
        return $this->hasMany(ServerNotifications::class, 'customer_id');
    }
}
