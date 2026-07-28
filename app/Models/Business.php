<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Business extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_REJECTED];

    protected $fillable = [
        'name',
        'shop_code',
        'owner_user_id',
        'tailoring_enabled',
        'clothing_enabled',
        'status',
        'approved_at',
        'approved_by_user_id',
        'status_changed_at',
        'status_changed_by_user_id',
        'status_reason',
        'password_expiry_days',
        'password_policy_updated_at',
    ];

    protected static function booted(): void
    {
        static::created(function (Business $business) {
            if (! $business->shop_code && Schema::hasColumn('businesses', 'shop_code')) {
                $business->forceFill(['shop_code' => self::makeShopCode($business->id)])->saveQuietly();
            }
        });
    }

    public static function makeShopCode(int $businessId): string
    {
        return sprintf('TMS-%06d', $businessId);
    }

    protected function casts(): array
    {
        return [
            'tailoring_enabled' => 'boolean',
            'clothing_enabled' => 'boolean',
            'approved_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'password_expiry_days' => 'integer',
            'password_policy_updated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(BusinessRole::class);
    }

    public function storefront()
    {
        return $this->hasOne(Storefront::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(BusinessSubscription::class);
    }

    public function latestSubscription()
    {
        return $this->hasOne(BusinessSubscription::class)
            ->whereNull('cancelled_at')
            ->ofMany('ends_on', 'max');
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function hasModule(string $module): bool
    {
        return match ($module) {
            User::MODULE_TAILORING => $this->tailoring_enabled,
            User::MODULE_CLOTHING => $this->clothing_enabled,
            default => false,
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusHistory()
    {
        return $this->hasMany(BusinessStatusHistory::class)->latest('created_at')->latest('id');
    }
}
