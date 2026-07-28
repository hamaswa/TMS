<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public const MODULE_TAILORING = 'tailoring';
    public const MODULE_CLOTHING = 'clothing';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'password',
        'business_id',
        'business_role_id',
        'is_business_owner',
        'employee_active',
        'must_change_password',
        'password_changed_at',
        'password_reset_at',
        'password_reset_by_user_id',
        'job_title',
        'preferred_workspace',
        'tailoring_access',
        'clothing_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_business_owner' => 'boolean',
            'employee_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'password_reset_at' => 'datetime',
            'tailoring_access' => 'boolean',
            'clothing_access' => 'boolean',
        ];
    }

    public function hasModule(string $module): bool
    {
        if ($this->business) {
            if (! $this->business->hasModule($module)) {
                return false;
            }

            $feature = match ($module) {
                self::MODULE_TAILORING => 'allow_tailoring',
                self::MODULE_CLOTHING => 'allow_clothing',
                default => null,
            };
            if (! $feature || ! $this->business->subscriptionAllowsFeature($feature)) {
                return false;
            }

            if ($this->isBusinessOwner()) {
                return true;
            }

            if (! $this->employee_active) {
                return false;
            }

            return $this->businessRole?->hasPermission(match ($module) {
                self::MODULE_TAILORING => BusinessRole::TAILORING_ACCESS,
                self::MODULE_CLOTHING => BusinessRole::CLOTHING_ACCESS,
                default => '',
            }) ?? false;
        }

        return match ($module) {
            self::MODULE_TAILORING => (bool) $this->tailoring_access,
            self::MODULE_CLOTHING => (bool) $this->clothing_access,
            default => false,
        };
    }

    public function enabledModules(): array
    {
        return array_values(array_filter([
            $this->hasModule(self::MODULE_TAILORING) ? self::MODULE_TAILORING : null,
            $this->hasModule(self::MODULE_CLOTHING) ? self::MODULE_CLOTHING : null,
        ]));
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function ownedBusiness()
    {
        return $this->hasOne(Business::class, 'owner_user_id');
    }

    public function businessRole()
    {
        return $this->belongsTo(BusinessRole::class);
    }

    public function businessOwnerId(): int
    {
        return (int) ($this->business?->owner_user_id ?? $this->id);
    }

    public function isBusinessOwner(): bool
    {
        return (bool) $this->is_business_owner || $this->hasRole('shop_owner');
    }

    public function isBusinessMember(): bool
    {
        return $this->isBusinessOwner() || ($this->business_id && $this->employee_active);
    }

    public function hasBusinessPermission(string $permission): bool
    {
        if ($this->business && ! $this->business->subscriptionAllowsPermission($permission)) {
            return false;
        }

        if ($this->isBusinessOwner()) {
            return true;
        }

        return $this->isBusinessMember() && ($this->businessRole?->hasPermission($permission) ?? false);
    }

    public function employeePasswordExpiresAt()
    {
        if (! $this->business_id || $this->isBusinessOwner() || ! $this->business?->password_expiry_days) {
            return null;
        }

        $baseline = collect([
            $this->password_changed_at,
            $this->password_reset_at,
            $this->business->password_policy_updated_at,
        ])->filter()->sortByDesc(fn ($date) => $date->getTimestamp())->first();

        $baseline ??= $this->created_at;

        return $baseline?->copy()->addDays($this->business->password_expiry_days);
    }

    public function employeePasswordExpired(): bool
    {
        return $this->employeePasswordExpiresAt()?->isPast() ?? false;
    }

    public function carts()
    {
        return $this->hasMany(Cart::class,'user_id');
    }

    public function onlineorder()
    {
        return $this->hasMany(OnlineOrder::class,'user_id');
    }

    public function servernoti()
    {
        return $this->hasMany(ServerNotifications::class,'user_id');
    }
}
