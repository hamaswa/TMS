<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Business extends Model
{
    use HasFactory;

    /**
     * Permission checks run many times while the shared admin navigation is
     * rendered. Cache subscription lookups on this model instance so one page
     * request does not issue the same EXISTS/query dozens of times.
     */
    protected ?bool $subscriptionManagedCache = null;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_REJECTED];
    /** Stored as the status reason when a shop is suspended for not choosing a plan by the deadline. */
    public const NO_PLAN_SUSPENSION_REASON = 'No subscription plan chosen before the deadline.';
    public const TAILORING_STATUS_SIMPLE = 'simple';
    public const TAILORING_STATUS_DETAILED = 'detailed';
    public const TAILORING_STATUS_MODES = [self::TAILORING_STATUS_SIMPLE, self::TAILORING_STATUS_DETAILED];

    protected $fillable = [
        'name',
        'signup_mobile_normalized',
        'self_registered_at',
        'shop_code',
        'owner_user_id',
        'tailoring_enabled',
        'clothing_enabled',
        'tailoring_status_mode',
        'status',
        'is_demo',
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
        return sprintf('SHOP-%06d', $businessId);
    }

    protected function casts(): array
    {
        return [
            'tailoring_enabled' => 'boolean',
            'clothing_enabled' => 'boolean',
            'is_demo' => 'boolean',
            'self_registered_at' => 'datetime',
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

    public function planRequests()
    {
        return $this->hasMany(SubscriptionPlanRequest::class);
    }

    /** The plan request (with its payment) waiting for the super admin, if any. */
    public function pendingPlanRequest()
    {
        return $this->hasOne(SubscriptionPlanRequest::class)
            ->where('status', SubscriptionPlanRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    public function latestPlanRequest()
    {
        return $this->hasOne(SubscriptionPlanRequest::class)->latestOfMany();
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

    public function activeSubscription()
    {
        return $this->hasOne(BusinessSubscription::class)
            ->whereNull('cancelled_at')
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->ofMany('ends_on', 'max');
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function subscriptionIsManaged(): bool
    {
        return $this->subscriptionManagedCache ??= $this->subscriptions()->exists();
    }

    public function currentSubscription(): ?BusinessSubscription
    {
        if ($this->relationLoaded('activeSubscription')) {
            return $this->getRelation('activeSubscription');
        }

        $subscription = $this->activeSubscription()->first();
        $this->setRelation('activeSubscription', $subscription);

        return $subscription;
    }

    public function hasActiveSubscriptionAccess(): bool
    {
        return ! $this->subscriptionIsManaged() || $this->currentSubscription() !== null;
    }

    public function subscriptionAllowsFeature(string $feature): bool
    {
        if (! $this->subscriptionIsManaged()) {
            return true;
        }

        return $this->currentSubscription()?->allowsFeature($feature) ?? false;
    }

    public function subscriptionAllowsPermission(string $permission): bool
    {
        if (! $this->subscriptionIsManaged()) {
            return true;
        }

        return $this->currentSubscription()?->allowsPermission($permission) ?? false;
    }

    public function subscriptionLimit(string $column): ?int
    {
        if (! $this->subscriptionIsManaged()) {
            return null;
        }

        $value = $this->currentSubscription()?->getAttribute($column);

        return $value === null ? null : (int) $value;
    }

    public function hasModule(string $module): bool
    {
        return match ($module) {
            User::MODULE_TAILORING => $this->tailoring_enabled,
            User::MODULE_CLOTHING => $this->clothing_enabled,
            default => false,
        };
    }

    public function usesDetailedTailoringWorkflow(): bool
    {
        return $this->tailoring_status_mode === self::TAILORING_STATUS_DETAILED;
    }

    public static function tailoringStatusModeForOwner(int $ownerId): string
    {
        return (string) (self::where('owner_user_id', $ownerId)->value('tailoring_status_mode')
            ?: self::TAILORING_STATUS_SIMPLE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    /** The date from which shops still without any plan are suspended, or null when no deadline is set. */
    public static function noPlanDeadline(): ?Carbon
    {
        $date = config('subscriptions.no_plan_deadline');

        return blank($date) ? null : Carbon::parse($date)->startOfDay();
    }

    /** An active, real shop that has never been put on a subscription. */
    public function needsPlan(): bool
    {
        return $this->isActive() && ! $this->isDemo() && ! $this->subscriptionIsManaged();
    }

    public function scopeWithoutPlan($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('is_demo', false)
            ->whereDoesntHave('subscriptions');
    }

    public function statusHistory()
    {
        return $this->hasMany(BusinessStatusHistory::class)->latest('created_at')->latest('id');
    }
}
