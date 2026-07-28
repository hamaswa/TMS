<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        return view('Administrator.subscription-plans', [
            'plans' => SubscriptionPlan::withCount('subscriptions')->orderBy('name')->get(),
            'features' => SubscriptionPlan::FEATURES,
            'permissions' => BusinessRole::PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        SubscriptionPlan::create([
            ...$this->validated($request),
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Subscription plan created. New subscriptions can now use it.');
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $plan->update($this->validated($request, $plan));

        return back()->with('success', 'Plan updated. Existing subscriptions keep their original entitlement snapshot.');
    }

    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('subscription_plans', 'code')->ignore($plan?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'billing_period_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'max_employees' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'max_business_roles' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'max_tailors' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(array_keys(SubscriptionPlan::FEATURES))],
            'allowed_permissions' => ['nullable', 'array'],
            'allowed_permissions.*' => [Rule::in(array_keys(BusinessRole::PERMISSIONS))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $features = array_fill_keys(array_keys(SubscriptionPlan::FEATURES), false);
        foreach ($validated['features'] ?? [] as $feature) {
            $features[$feature] = true;
        }
        $permissions = array_values(array_unique($validated['allowed_permissions'] ?? []));
        $permissions = array_values(array_filter($permissions, function (string $permission) use ($features) {
            if (str_starts_with($permission, 'tailoring.')) {
                return $features['allow_tailoring'];
            }
            if (str_starts_with($permission, 'clothing.')) {
                return $features['allow_clothing'];
            }

            return match ($permission) {
                BusinessRole::STOREFRONT_MANAGE => $features['allow_storefront'],
                BusinessRole::FINANCE_VIEW => $features['allow_financial_reports'],
                BusinessRole::TEAM_MANAGE => $features['allow_team_management'],
                BusinessRole::ACTIVITY_VIEW => $features['allow_activity_log'],
                default => true,
            };
        }));

        unset($validated['features']);

        return [
            ...$validated,
            ...$features,
            'allowed_permissions' => $permissions,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
