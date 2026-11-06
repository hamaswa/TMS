<?php

use App\Models\BusinessRole;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MONTHLY_DAYS = 30;

    private const ANNUAL_DAYS = 365;

    private const TRIAL_DAYS = 14;

    public function up(): void
    {
        $now = now();

        foreach ($this->plans() as $plan) {
            if (DB::table('subscription_plans')->where('code', $plan['code'])->exists()) {
                continue;
            }

            $features = array_intersect_key($plan, SubscriptionPlan::FEATURES);

            DB::table('subscription_plans')->insert([
                ...$plan,
                'allowed_permissions' => json_encode(
                    SubscriptionPlan::permissionsForFeatures($features, array_keys(BusinessRole::PERMISSIONS))
                ),
                'lifecycle_status' => SubscriptionPlan::LIFECYCLE_ACTIVE,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('subscription_plans')
            ->whereIn('code', array_column($this->plans(), 'code'))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('business_subscriptions')
                ->whereColumn('business_subscriptions.subscription_plan_id', 'subscription_plans.id'))
            ->delete();
    }

    /**
     * Each tier is offered monthly (public, with a free trial) and annually
     * (private, assigned by the super admin at roughly two months free).
     */
    private function plans(): array
    {
        $tiers = [
            [
                'code' => 'starter-tailoring',
                'name' => 'Starter – Tailoring',
                'description' => 'Tailoring workspace only, for a single small shop.',
                'marketing_summary' => 'Tailoring workspace · up to 500 customers · 2 employees',
                'monthly' => 1200,
                'annual' => 12000,
                'max_employees' => 2,
                'max_customers' => 500,
                'allow_tailoring' => true,
                'allow_clothing' => false,
                'allow_storefront' => false,
                'allow_financial_reports' => false,
                'allow_activity_log' => false,
                'is_recommended' => false,
                'order' => 1,
            ],
            [
                'code' => 'starter-clothing',
                'name' => 'Starter – Clothing',
                'description' => 'Clothing sales and purchases workspace only, for a single small shop.',
                'marketing_summary' => 'Clothing workspace · up to 500 customers · 2 employees',
                'monthly' => 1200,
                'annual' => 12000,
                'max_employees' => 2,
                'max_customers' => 500,
                'allow_tailoring' => false,
                'allow_clothing' => true,
                'allow_storefront' => false,
                'allow_financial_reports' => false,
                'allow_activity_log' => false,
                'is_recommended' => false,
                'order' => 2,
            ],
            [
                'code' => 'business-pro',
                'name' => 'Business Pro',
                'description' => 'Both tailoring and clothing workspaces with the online storefront and financial reports.',
                'marketing_summary' => 'Both workspaces · up to 2,500 customers · 10 employees · storefront · financial reports',
                'monthly' => 2500,
                'annual' => 24000,
                'max_employees' => 10,
                'max_customers' => 2500,
                'allow_tailoring' => true,
                'allow_clothing' => true,
                'allow_storefront' => true,
                'allow_financial_reports' => true,
                'allow_activity_log' => false,
                'is_recommended' => true,
                'order' => 3,
            ],
            [
                'code' => 'elite-boutique',
                'name' => 'Elite Boutique',
                'description' => 'Everything included with no limits on customers, employees, roles or tailors.',
                'marketing_summary' => 'Both workspaces · unlimited customers and staff · storefront · reports · activity log',
                'monthly' => 5000,
                'annual' => 48000,
                'max_employees' => null,
                'max_customers' => null,
                'allow_tailoring' => true,
                'allow_clothing' => true,
                'allow_storefront' => true,
                'allow_financial_reports' => true,
                'allow_activity_log' => true,
                'is_recommended' => false,
                'order' => 4,
            ],
        ];

        $plans = [];
        foreach ($tiers as $tier) {
            $shared = [
                'description' => $tier['description'],
                'marketing_summary' => $tier['marketing_summary'],
                'max_employees' => $tier['max_employees'],
                'max_business_roles' => null,
                'max_tailors' => null,
                'max_customers' => $tier['max_customers'],
                'allow_tailoring' => $tier['allow_tailoring'],
                'allow_clothing' => $tier['allow_clothing'],
                'allow_storefront' => $tier['allow_storefront'],
                'allow_financial_reports' => $tier['allow_financial_reports'],
                'allow_team_management' => true,
                'allow_activity_log' => $tier['allow_activity_log'],
            ];

            $plans[] = [
                ...$shared,
                'code' => $tier['code'].'-monthly',
                'name' => $tier['name'],
                'price' => $tier['monthly'],
                'billing_period_days' => self::MONTHLY_DAYS,
                'trial_days' => self::TRIAL_DAYS,
                'is_public' => true,
                'is_recommended' => $tier['is_recommended'],
                'display_order' => $tier['order'],
            ];
            $plans[] = [
                ...$shared,
                'code' => $tier['code'].'-annual',
                'name' => $tier['name'].' (Annual)',
                'price' => $tier['annual'],
                'billing_period_days' => self::ANNUAL_DAYS,
                'trial_days' => 0,
                'is_public' => false,
                'is_recommended' => false,
                'display_order' => $tier['order'] + 10,
            ];
        }

        return $plans;
    }
};
