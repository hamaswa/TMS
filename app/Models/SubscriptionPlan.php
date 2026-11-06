<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    public const LIFECYCLE_DRAFT = 'draft';

    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_ARCHIVED = 'archived';

    public const LIFECYCLE_STATUSES = [self::LIFECYCLE_DRAFT, self::LIFECYCLE_ACTIVE, self::LIFECYCLE_ARCHIVED];

    public const FEATURES = [
        'allow_tailoring' => 'Tailoring workspace',
        'allow_clothing' => 'Clothing sales and purchases',
        'allow_storefront' => 'Public storefront and online requests',
        'allow_financial_reports' => 'Financial reports and reconciliation',
        'allow_team_management' => 'Employee and role management',
        'allow_activity_log' => 'Employee activity log',
    ];

    public const WORKSPACES = [
        'tailoring' => 'Tailoring only',
        'clothing' => 'Clothing only',
        'both' => 'Tailoring + Clothing',
    ];

    /** Common billing periods offered in the plan form, in days. */
    public const BILLING_PERIODS = [
        30 => 'Monthly',
        90 => 'Every 3 months',
        180 => 'Every 6 months',
        365 => 'Yearly',
    ];

    /** Optional features shown as simple on/off switches (workspaces are chosen separately). */
    public const EXTRA_FEATURES = [
        'allow_storefront' => 'Online storefront',
        'allow_financial_reports' => 'Financial reports',
        'allow_activity_log' => 'Employee activity log',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'marketing_summary',
        'price',
        'billing_period_days',
        'trial_days',
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
        'is_public',
        'is_recommended',
        'display_order',
        'lifecycle_status',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_period_days' => 'integer',
            'trial_days' => 'integer',
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
            'is_public' => 'boolean',
            'is_recommended' => 'boolean',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(BusinessSubscription::class);
    }

    public function entitlementSnapshot(): array
    {
        return [
            'plan_code' => $this->code,
            'max_employees' => $this->max_employees,
            'max_business_roles' => $this->max_business_roles,
            'max_tailors' => $this->max_tailors,
            'max_customers' => $this->max_customers,
            'allow_tailoring' => $this->allow_tailoring,
            'allow_clothing' => $this->allow_clothing,
            'allow_storefront' => $this->allow_storefront,
            'allow_financial_reports' => $this->allow_financial_reports,
            'allow_team_management' => $this->allow_team_management,
            'allow_activity_log' => $this->allow_activity_log,
            'allowed_permissions' => $this->allowed_permissions,
        ];
    }

    public function workspace(): ?string
    {
        return match (true) {
            $this->allow_tailoring && $this->allow_clothing => 'both',
            (bool) $this->allow_tailoring => 'tailoring',
            (bool) $this->allow_clothing => 'clothing',
            default => null,
        };
    }

    public function billingPeriodLabel(): string
    {
        return match ((int) $this->billing_period_days) {
            30 => 'month',
            365 => 'year',
            default => $this->billing_period_days.' days',
        };
    }

    /** True when the plan allows every permission its features cover. */
    public function allowsAllFeaturePermissions(): bool
    {
        $all = self::permissionsForFeatures($this->only(array_keys(self::FEATURES)), array_keys(BusinessRole::PERMISSIONS));

        return array_diff($all, $this->allowed_permissions ?? []) === [];
    }

    /**
     * Drop permissions whose feature the plan does not include.
     *
     * @param  array<string, bool>  $features
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function permissionsForFeatures(array $features, array $permissions): array
    {
        return array_values(array_filter(array_unique($permissions), function (string $permission) use ($features) {
            if (str_starts_with($permission, 'tailoring.')) {
                return (bool) ($features['allow_tailoring'] ?? false);
            }
            if (str_starts_with($permission, 'clothing.')) {
                return (bool) ($features['allow_clothing'] ?? false);
            }

            return match ($permission) {
                BusinessRole::STOREFRONT_MANAGE => (bool) ($features['allow_storefront'] ?? false),
                BusinessRole::FINANCE_VIEW => (bool) ($features['allow_financial_reports'] ?? false),
                BusinessRole::TEAM_MANAGE => (bool) ($features['allow_team_management'] ?? false),
                BusinessRole::ACTIVITY_VIEW => (bool) ($features['allow_activity_log'] ?? false),
                default => true,
            };
        }));
    }

    public function scopePubliclyAvailable($query)
    {
        return $query->where('is_active', true)
            ->where('is_public', true)
            ->where('lifecycle_status', self::LIFECYCLE_ACTIVE)
            ->where('trial_days', '>', 0);
    }
}
