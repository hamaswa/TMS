<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubscription extends Model
{
    use HasFactory;
    /** Written on a period that an upgrade replaced. */
    public const UPGRADE_REPLACED_NOTE = 'Replaced by upgrade to';


    public const EXPIRY_WARNING_DAYS = 14;

    protected $fillable = [
        'business_id',
        'subscription_plan_id',
        'plan_name',
        'plan_code',
        'starts_on',
        'ends_on',
        'fee',
        'is_trial',
        'trial_started_at',
        'trial_ends_at',
        'trial_converted_at',
        'max_employees',
        'max_business_roles',
        'max_tailors',
        'max_customers',
        'allow_tailoring',
        'allow_clothing',
        'allow_storefront',
        'allow_financial_reports',
        'allow_team_management',
        'allow_activity_log',
        'allowed_permissions',
        'notes',
        'created_by_user_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'cancel_requested_at',
        'cancel_requested_by_user_id',
        'cancel_request_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'fee' => 'decimal:2',
            'is_trial' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_converted_at' => 'datetime',
            'max_employees' => 'integer',
            'max_business_roles' => 'integer',
            'max_tailors' => 'integer',
            'max_customers' => 'integer',
            'allow_tailoring' => 'boolean',
            'allow_clothing' => 'boolean',
            'allow_storefront' => 'boolean',
            'allow_financial_reports' => 'boolean',
            'allow_team_management' => 'boolean',
            'allow_activity_log' => 'boolean',
            'allowed_permissions' => 'array',
            'cancelled_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function activePayments()
    {
        return $this->hasMany(SubscriptionPayment::class)->whereNull('reversed_at');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /** The shop's plan request that created this period, if any. */
    public function planRequest()
    {
        return $this->hasOne(SubscriptionPlanRequest::class, 'business_subscription_id');
    }

    /** The shop asked to stop at the end of this period. */
    public function isEndingByShopRequest(): bool
    {
        return $this->cancel_requested_at !== null && ! $this->cancelled_at;
    }

    public function cancelRequestedBy()
    {
        return $this->belongsTo(User::class, 'cancel_requested_by_user_id');
    }

    /** Started from an approved upgrade request. */
    public function isUpgrade(): bool
    {
        return (bool) $this->planRequest?->is_upgrade;
    }

    /** Ended early or cancelled because the shop upgraded. */
    public function wasReplacedByUpgrade(): bool
    {
        $marker = self::UPGRADE_REPLACED_NOTE;

        return str_contains((string) $this->cancellation_reason, $marker) || str_contains((string) $this->notes, $marker);
    }

    /** The "Replaced by upgrade to …" line written when this period was replaced. */
    public function upgradeReplacedNote(): ?string
    {
        foreach ([$this->cancellation_reason, ...explode("\n", (string) $this->notes)] as $line) {
            if (str_contains((string) $line, self::UPGRADE_REPLACED_NOTE)) {
                return trim($line);
            }
        }

        return null;
    }

    /**
     * Label for the admin history: [key, label, Bootstrap colour, icon].
     *
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    public function historyStatus(): array
    {
        if ($this->wasReplacedByUpgrade()) {
            return ['upgraded', 'Upgraded away', 'info', 'fa-level-up-alt'];
        }

        return match (true) {
            (bool) $this->cancelled_at => ['cancelled', 'Cancelled', 'danger', 'fa-ban'],
            $this->isEndingByShopRequest() && ! $this->ends_on->isBefore(now()->startOfDay()) => ['ending', 'Ends '.$this->ends_on->format('d M'), 'warning', 'fa-door-open'],
            $this->starts_on->isAfter(now()->endOfDay()) => ['upcoming', 'Upcoming', 'primary', 'fa-hourglass-start'],
            default => match ($this->state()) {
                'expired' => ['expired', 'Expired', 'secondary', 'fa-history'],
                'expiring' => ['expiring', 'Expiring soon', 'warning', 'fa-exclamation-triangle'],
                default => ['active', 'Active', 'success', 'fa-check-circle'],
            },
        };
    }

    public function amountPaid(): float
    {
        if (array_key_exists('active_payments_sum_amount', $this->attributes)) {
            return (float) ($this->active_payments_sum_amount ?? 0);
        }

        return (float) $this->activePayments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->fee - $this->amountPaid());
    }

    public function daysRemaining(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->ends_on->copy()->startOfDay(), false);
    }

    public function state(): string
    {
        if ($this->cancelled_at) {
            return 'cancelled';
        }
        if ($this->ends_on->isBefore(now()->startOfDay())) {
            return 'expired';
        }
        if ($this->daysRemaining() <= self::EXPIRY_WARNING_DAYS) {
            return 'expiring';
        }

        return 'active';
    }

    public function allowsFeature(string $feature): bool
    {
        $value = $this->getAttribute($feature);

        return $value === null || (bool) $value;
    }

    public function allowsPermission(string $permission): bool
    {
        return $this->allowed_permissions === null
            || in_array($permission, $this->allowed_permissions, true);
    }
}
