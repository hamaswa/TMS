<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PublicBusinessRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PublicBusinessSignupController extends Controller
{
    public function create(Request $request)
    {
        $plans = SubscriptionPlan::publiclyAvailable()
            ->orderByDesc('is_recommended')->orderBy('display_order')->orderBy('price')->get();
        $selectedPlan = $request->integer('plan');

        return view('storefront.public.business-signup', compact('plans', 'selectedPlan'));
    }

    public function store(Request $request, PublicBusinessRegistrationService $registration)
    {
        $validated = $request->validate([
            'subscription_plan_id' => [
                'required',
                Rule::exists('subscription_plans', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_public', true)
                    ->where('lifecycle_status', SubscriptionPlan::LIFECYCLE_ACTIVE)
                    ->where('trial_days', '>', 0)),
            ],
            'owner_name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => [Rule::in([User::MODULE_TAILORING, User::MODULE_CLOTHING])],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = $registration->register($validated);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('status', __('storefront.signup.verification_sent'));
    }
}
