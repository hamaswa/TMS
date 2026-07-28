<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    public const FEATURES = [
        'allow_tailoring' => 'Tailoring workspace',
        'allow_clothing' => 'Clothing sales and purchases',
        'allow_storefront' => 'Public storefront and online requests',
        'allow_financial_reports' => 'Financial reports and reconciliation',
        'allow_team_management' => 'Employee and role management',
        'allow_activity_log' => 'Employee activity log',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'billing_period_days',
        'max_employees',
        'max_business_roles',
        'max_tailors',
        'allow_tailoring',
        'allow_clothing',
        'allow_storefront',
        'allow_financial_reports',
        'allow_team_management',
        'allow_activity_log',
        'allowed_permissions',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_period_days' => 'integer',
            'max_employees' => 'integer',
            'max_business_roles' => 'integer',
            'max_tailors' => 'integer',
            'allow_tailoring' => 'boolean',
            'allow_clothing' => 'boolean',
            'allow_storefront' => 'boolean',
            'allow_financial_reports' => 'boolean',
            'allow_team_management' => 'boolean',
            'allow_activity_log' => 'boolean',
            'allowed_permissions' => 'array',
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
            'allow_tailoring' => $this->allow_tailoring,
            'allow_clothing' => $this->allow_clothing,
            'allow_storefront' => $this->allow_storefront,
            'allow_financial_reports' => $this->allow_financial_reports,
            'allow_team_management' => $this->allow_team_management,
            'allow_activity_log' => $this->allow_activity_log,
            'allowed_permissions' => $this->allowed_permissions,
        ];
    }
}
