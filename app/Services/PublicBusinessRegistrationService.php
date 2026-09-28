<?php

namespace App\Services;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\PakistanPhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class PublicBusinessRegistrationService
{
    public function __construct(
        private readonly TailoringOptionDefaultsService $tailoringDefaults,
        private readonly ClothingBrandDefaultsService $clothingBrandDefaults,
        private readonly ClothingTypeDefaultsService $clothingTypeDefaults,
    ) {}

    public function register(array $data): User
    {
        $mobile = PakistanPhoneNumber::normalize($data['mobile']);
        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => __('storefront.signup.mobile_invalid')]);
        }

        return DB::transaction(function () use ($data, $mobile) {
            $plan = SubscriptionPlan::publiclyAvailable()->lockForUpdate()->findOrFail($data['subscription_plan_id']);
            $modules = array_values(array_intersect($data['modules'], array_filter([
                $plan->allow_tailoring ? User::MODULE_TAILORING : null,
                $plan->allow_clothing ? User::MODULE_CLOTHING : null,
            ])));
            if ($modules === []) {
                throw ValidationException::withMessages(['modules' => __('storefront.signup.modules_invalid')]);
            }
            if (Business::where('signup_mobile_normalized', $mobile)->exists()) {
                throw ValidationException::withMessages(['mobile' => __('storefront.signup.trial_used')]);
            }

            $user = User::create([
                'name' => $data['owner_name'],
                'email' => strtolower($data['email']),
                'phone' => PakistanPhoneNumber::local($mobile),
                'password' => Hash::make($data['password']),
                'tailoring_access' => in_array(User::MODULE_TAILORING, $modules, true),
                'clothing_access' => in_array(User::MODULE_CLOTHING, $modules, true),
                'is_business_owner' => true,
            ]);
            $user->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));

            $business = Business::create([
                'name' => $data['business_name'],
                'signup_mobile_normalized' => $mobile,
                'self_registered_at' => now(),
                'owner_user_id' => $user->id,
                'tailoring_enabled' => $user->tailoring_access,
                'clothing_enabled' => $user->clothing_access,
                'status' => Business::STATUS_PENDING,
                'status_changed_at' => now(),
                'status_reason' => 'Public signup awaiting email verification.',
            ]);
            $business->statusHistory()->create([
                'from_status' => null,
                'to_status' => Business::STATUS_PENDING,
                'reason' => 'Public signup awaiting email verification.',
                'created_at' => now(),
            ]);
            $user->forceFill(['business_id' => $business->id])->save();

            $startsOn = now()->startOfDay();
            $endsOn = $startsOn->copy()->addDays($plan->trial_days - 1);
            $business->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
                'fee' => 0,
                'is_trial' => true,
                'trial_started_at' => $startsOn,
                'trial_ends_at' => $endsOn->copy()->endOfDay(),
                'notes' => 'Self-service free trial.',
                ...$plan->entitlementSnapshot(),
            ]);

            if ($user->tailoring_access) {
                $this->tailoringDefaults->seedForOwner($user->id);
            }
            if ($user->clothing_access) {
                $this->clothingBrandDefaults->seedForOwner($user->id);
                $this->clothingTypeDefaults->seedForOwner($user->id);
            }

            return $user->fresh(['ownedBusiness.latestSubscription']);
        });
    }
}
