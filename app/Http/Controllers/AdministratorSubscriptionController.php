<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministratorSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', Rule::in(['active', 'expiring', 'expired', 'unconfigured'])],
        ]);
        $today = now()->toDateString();
        $warningDate = now()->addDays(BusinessSubscription::EXPIRY_WARNING_DAYS)->toDateString();

        $businesses = Business::query()
            ->with([
                'owner:id,name,email',
                'latestSubscription' => fn ($query) => $query
                    ->withSum('activePayments', 'amount'),
            ])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($owner) => $owner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))))
            ->when($filters['state'] ?? null, function ($query, $state) use ($today, $warningDate) {
                if ($state === 'unconfigured') {
                    $query->whereDoesntHave('subscriptions', fn ($subscription) => $subscription->whereNull('cancelled_at'));
                } elseif ($state === 'expired') {
                    $query->whereHas('latestSubscription', fn ($subscription) => $subscription->whereDate('ends_on', '<', $today));
                } elseif ($state === 'expiring') {
                    $query->whereHas('latestSubscription', fn ($subscription) => $subscription
                        ->whereDate('ends_on', '>=', $today)
                        ->whereDate('ends_on', '<=', $warningDate));
                } elseif ($state === 'active') {
                    $query->whereHas('latestSubscription', fn ($subscription) => $subscription->whereDate('ends_on', '>', $warningDate));
                }
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $metrics = [
            'clients' => Business::count(),
            'expiring' => Business::whereHas('latestSubscription', fn ($subscription) => $subscription
                ->whereDate('ends_on', '>=', $today)
                ->whereDate('ends_on', '<=', $warningDate))->count(),
            'expired' => Business::whereHas('latestSubscription', fn ($subscription) => $subscription
                ->whereDate('ends_on', '<', $today))->count(),
            'received' => (float) SubscriptionPayment::whereNull('reversed_at')->sum('amount'),
        ];

        return view('Administrator.subscriptions', compact('businesses', 'metrics'));
    }

    public function storeSubscription(Request $request, int $id)
    {
        $user = $this->client($id);
        $business = $user->ownedBusiness()->firstOrFail();
        $validated = $request->validate([
            'subscription_plan_id' => ['nullable', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'plan_name' => ['nullable', 'required_without:subscription_plan_id', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $plan = ! empty($validated['subscription_plan_id'])
            ? SubscriptionPlan::where('is_active', true)->findOrFail($validated['subscription_plan_id'])
            : null;

        DB::transaction(function () use ($business, $validated, $plan) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);
            $overlap = $lockedBusiness->subscriptions()
                ->whereNull('cancelled_at')
                ->whereDate('starts_on', '<=', $validated['ends_on'])
                ->whereDate('ends_on', '>=', $validated['starts_on'])
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_on' => 'This subscription period overlaps an existing active subscription.',
                ]);
            }

            $attributes = [
                ...$validated,
                'plan_name' => $plan?->name ?? $validated['plan_name'],
                'created_by_user_id' => Auth::id(),
            ];
            if ($plan) {
                $attributes = [
                    ...$attributes,
                    'subscription_plan_id' => $plan->id,
                    ...$plan->entitlementSnapshot(),
                ];
            }

            $lockedBusiness->subscriptions()->create($attributes);
        });

        return redirect()->route('administrator.clients.show', $user)
            ->with('success', 'Subscription period created. You can now record its payment.');
    }

    public function storePayment(Request $request, int $id, BusinessSubscription $subscription)
    {
        $user = $this->client($id);
        $business = $user->ownedBusiness()->firstOrFail();
        abort_unless($subscription->business_id === $business->id && ! $subscription->cancelled_at, 404);
        $errorBag = 'subscription_payment_'.$subscription->id;
        $validated = $request->validateWithBag($errorBag, [
            'paid_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(SubscriptionPayment::METHODS))],
            'reference' => [
                Rule::requiredIf(fn () => $request->input('payment_method') !== 'cash'),
                'nullable',
                'string',
                'max:150',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($business, $subscription, $validated) {
            $locked = BusinessSubscription::query()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->findOrFail($subscription->id);
            $balance = $locked->balanceDue();
            if ((float) $validated['amount'] > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the outstanding subscription balance of Rs '.number_format($balance, 2).'.',
                ])->errorBag('subscription_payment_'.$subscription->id);
            }

            $locked->payments()->create([
                ...$validated,
                'business_id' => $business->id,
                'recorded_by_user_id' => Auth::id(),
            ]);
        });

        return redirect()->route('administrator.clients.show', $user)
            ->with('success', 'Subscription payment recorded.');
    }

    public function reversePayment(Request $request, int $id, SubscriptionPayment $payment)
    {
        $user = $this->client($id);
        $business = $user->ownedBusiness()->firstOrFail();
        abort_unless($payment->business_id === $business->id && ! $payment->reversed_at, 404);
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $locked = SubscriptionPayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_if($locked->reversed_at, 422, 'This payment is already reversed.');
            $locked->forceFill([
                'reversed_at' => now(),
                'reversed_by_user_id' => Auth::id(),
                'reversal_reason' => $validated['reversal_reason'],
            ])->save();
        });

        return redirect()->route('administrator.clients.show', $user)
            ->with('success', 'Subscription payment reversed. The audit record was preserved.');
    }

    public function cancelSubscription(Request $request, int $id, BusinessSubscription $subscription)
    {
        $user = $this->client($id);
        $business = $user->ownedBusiness()->firstOrFail();
        abort_unless($subscription->business_id === $business->id && ! $subscription->cancelled_at, 404);
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $subscription->forceFill([
            'cancelled_at' => now(),
            'cancelled_by_user_id' => Auth::id(),
            'cancellation_reason' => $validated['cancellation_reason'],
        ])->save();

        return redirect()->route('administrator.clients.show', $user)
            ->with('success', 'Subscription cancelled without deleting its payment history.');
    }

    private function client(int|string $id): User
    {
        return User::role('shop_owner')->findOrFail($id);
    }
}
