<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        return view('Administrator.subscription-plans', [
            'plans' => SubscriptionPlan::withCount('subscriptions')->orderBy('name')->get(),
            'features' => SubscriptionPlan::FEATURES,
            'permissions' => BusinessRole::ENGLISH_PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $attributes = $this->validated($request);
            if ($attributes['is_recommended']) {
                SubscriptionPlan::where('is_recommended', true)->update(['is_recommended' => false]);
            }
            SubscriptionPlan::create([...$attributes, 'created_by_user_id' => Auth::id()]);
        });

        return back()->with('success', 'Subscription plan created. New subscriptions can now use it.');
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        DB::transaction(function () use ($request, $plan) {
            $attributes = $this->validated($request, $plan);
            if ($attributes['is_recommended']) {
                SubscriptionPlan::where('id', '!=', $plan->id)->where('is_recommended', true)
                    ->update(['is_recommended' => false]);
            }
            $plan->update($attributes);
        });

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
            'marketing_summary' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'billing_period_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_employees' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'max_business_roles' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'max_tailors' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(array_keys(SubscriptionPlan::FEATURES))],
            'allowed_permissions' => ['nullable', 'array'],
            'allowed_permissions.*' => [Rule::in(array_keys(BusinessRole::PERMISSIONS))],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_recommended' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'lifecycle_status' => ['nullable', Rule::in(SubscriptionPlan::LIFECYCLE_STATUSES)],
        ]);

        $features = array_fill_keys(array_keys(SubscriptionPlan::FEATURES), false);
        foreach ($validated['features'] ?? [] as $feature) {
            $features[$feature] = true;
        }
        if ($request->boolean('is_public') && (int) ($validated['trial_days'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'trial_days' => 'A public signup plan must include at least one free-trial day.',
            ]);
        }
        if ($request->boolean('is_public') && ! $features['allow_tailoring'] && ! $features['allow_clothing']) {
            throw ValidationException::withMessages([
                'features' => 'A public signup plan must include tailoring, clothing, or both.',
            ]);
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
        $validated['trial_days'] ??= $plan?->trial_days ?? 0;
        $validated['display_order'] ??= $plan?->display_order ?? 0;
        $validated['lifecycle_status'] ??= $plan?->lifecycle_status ?? SubscriptionPlan::LIFECYCLE_ACTIVE;

        return [
            ...$validated,
            ...$features,
            'allowed_permissions' => $permissions,
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'is_recommended' => $request->boolean('is_recommended') && $request->boolean('is_public'),
        ];
    }
}
