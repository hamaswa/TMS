<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanRequest;
use App\Services\ShopSubscriptionCancellationService;
use App\Services\SubscriptionPlanRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientSubscriptionController extends Controller
{
    /** Urdu names for the billing-period tabs (English mode translates them). */
    private const PERIOD_LABELS = [30 => 'ماہانہ', 90 => 'سہ ماہی', 180 => 'ششماہی', 365 => 'سالانہ'];

    public function index()
    {
        $business = Auth::user()->ownedBusiness()->with(['pendingPlanRequest.plan', 'latestPlanRequest.plan'])->firstOrFail();
        $subscriptions = $business->subscriptions()
            ->with(['payments.recordedBy', 'payments.reversedBy'])
            ->withSum('activePayments', 'amount')
            ->latest('ends_on')
            ->get();
        // The plan running today; otherwise the last one that already started (to show it expired). Queued plans are not "current".
        $currentSubscription = $business->currentSubscription()
            ?? $subscriptions->first(fn ($subscription) => ! $subscription->cancelled_at && $subscription->starts_on->lte(now()));
        $plans = $this->availablePlans();

        return view('subscriptions.client', [
            'business' => $business,
            'subscriptions' => $subscriptions,
            'currentSubscription' => $currentSubscription,
            'planGroups' => $this->groupByPeriod($plans),
            'yearlySavings' => $this->yearlySavings($plans),
            'popularPlanIds' => $this->popularPlanIds($plans),
            'easypaisa' => PlatformSetting::easypaisa(),
            // Plans pricier than this one are shown as upgrades and ask for a reason.
            'currentPlan' => $business->currentSubscription()?->plan,
        ]);
    }

    public function requestPlan(Request $request, SubscriptionPlan $plan, SubscriptionPlanRequestService $requests)
    {
        abort_unless($plan->is_active && $plan->lifecycle_status === SubscriptionPlan::LIFECYCLE_ACTIVE, 404);

        // With Easypaisa set up, the shop pays first and sends the transaction ID; otherwise it is a plain request.
        $paying = PlatformSetting::easypaisa() !== null && (float) $plan->price > 0;
        $request->merge(['payment_reference' => is_string($request->input('payment_reference')) ? trim($request->input('payment_reference')) : $request->input('payment_reference')]);
        $business = Auth::user()->ownedBusiness()->firstOrFail();
        $upgrading = $plan->isUpgradeFrom($business->currentSubscription()?->plan);
        $validator = Validator::make($request->all(), [
            'upgrade_reason' => [$upgrading ? 'required' : 'nullable', 'string', 'min:5', 'max:1000'],
            // Easypaisa/JazzCash IDs are digits; SadaPay/Raast IDs look like RAAST-45372891.
            'payment_method' => [$paying ? 'required' : 'nullable', Rule::in(array_keys(SubscriptionPlanRequest::ONLINE_METHODS))],
            'payment_reference' => [$paying ? 'required' : 'nullable', 'string', 'min:6', 'max:40', 'regex:/^[A-Za-z0-9]+(-[A-Za-z0-9]+)*$/'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'upgrade_reason.required' => 'پلان اپ گریڈ کرنے کی وجہ لکھیں۔',
            'upgrade_reason.min' => 'اپ گریڈ کی وجہ تھوڑی تفصیل سے لکھیں۔',
            'upgrade_reason.max' => 'اپ گریڈ کی وجہ 1000 حروف سے زیادہ نہیں ہو سکتی۔',
            'payment_method.required' => 'ادائیگی کا طریقہ منتخب کریں۔',
            'payment_method.in' => 'ادائیگی کا درست طریقہ منتخب کریں۔',
            'payment_reference.required' => 'ادائیگی کی ٹرانزیکشن آئی ڈی درج کریں۔',
            'payment_reference.min' => 'ٹرانزیکشن آئی ڈی کم از کم 6 حروف کی ہوتی ہے۔',
            'payment_reference.max' => 'ٹرانزیکشن آئی ڈی 40 حروف سے زیادہ نہیں ہو سکتی۔',
            'payment_reference.regex' => 'درست ٹرانزیکشن آئی ڈی درج کریں: صرف حروف، ہندسے اور ڈیش (-)، جیسے 12345678901 یا RAAST-45372891۔',
            'payment_proof.mimes' => 'اسکرین شاٹ تصویر یا پی ڈی ایف فائل ہونا چاہیے۔',
            'payment_proof.uploaded' => 'اسکرین شاٹ اپ لوڈ نہیں ہو سکا۔ چھوٹی تصویر لگائیں۔',
            'payment_proof.file' => 'اسکرین شاٹ اپ لوڈ نہیں ہو سکا۔ چھوٹی تصویر لگائیں۔',
            'payment_proof.max' => 'اسکرین شاٹ کا سائز زیادہ سے زیادہ پانچ ایم بی ہو سکتا ہے۔',
        ]);
        // Always return to the plans page (not "back"), so the payment popup reopens with the errors.
        $toPlans = redirect()->to(route('admin.subscription.index').'#plans');
        // Browsers drop an attached file when the form comes back with an error, so ask for it again.
        $proofAttached = $request->files->has('payment_proof');
        if ($validator->fails()) {
            return $toPlans->withErrors($validator)->withInput($request->except('payment_proof'))->with('proof_reattach', $proofAttached);
        }

        $user = Auth::user();
        try {
            $requests->submit(
                $business,
                $user,
                $plan,
                $paying ? $request->input('payment_reference') : null,
                $paying ? $request->file('payment_proof') : null,
                $upgrading ? trim((string) $request->input('upgrade_reason')) : null,
                $paying ? $request->input('payment_method') : null,
            );
        } catch (ValidationException $exception) {
            return $toPlans->withErrors($exception->errors())->withInput($request->except('payment_proof'))->with('proof_reattach', $proofAttached);
        }

        return redirect()->to(route('admin.subscription.index').'#plans')->with('plan_requested', $paying
            ? 'آپ کی ادائیگی کی تفصیل سپر ایڈمن کو بھیج دی گئی ہے۔ تصدیق کے بعد آپ کا پلان فعال ہو جائے گا۔'
            : 'آپ کی درخواست سپر ایڈمن کو بھیج دی گئی ہے۔ وہ پلان فعال کرنے کے لیے آپ سے رابطہ کریں گے۔');
    }

    /** The owner stops the plan: it keeps working until its end date and is not renewed. */
    public function cancel(Request $request, ShopSubscriptionCancellationService $cancellations)
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:1000'],
            'cancel_confirm' => ['accepted'],
        ], [
            'cancel_reason.required' => 'منسوخی کی وجہ لکھیں۔',
            'cancel_reason.min' => 'منسوخی کی وجہ تھوڑی تفصیل سے لکھیں۔',
            'cancel_confirm.accepted' => 'منسوخی کی تصدیق کے لیے خانے پر نشان لگائیں۔',
        ]);
        $user = Auth::user();
        $subscription = $cancellations->cancelAtPeriodEnd($user->ownedBusiness()->firstOrFail(), $user, trim($validated['cancel_reason']));

        return redirect()->route('admin.subscription.index')->with('plan_requested',
            'آپ کی سبسکرپشن منسوخ کر دی گئی ہے۔ آپ کا پلان '.\App\Support\DisplayDate::long($subscription->ends_on).' تک چلتا رہے گا، اس کے بعد تجدید نہیں ہوگی۔');
    }

    public function undoCancel(ShopSubscriptionCancellationService $cancellations)
    {
        $cancellations->undo(Auth::user()->ownedBusiness()->firstOrFail());

        return redirect()->route('admin.subscription.index')->with('plan_requested', 'منسوخی واپس لے لی گئی۔ آپ کا پلان معمول کے مطابق جاری ہے۔');
    }

    public function required()
    {
        $user = Auth::user();
        $business = $user->business()->with('owner')->firstOrFail();
        $latestSubscription = $business->latestSubscription()->first();
        $nextSubscription = $business->subscriptions()
            ->whereNull('cancelled_at')
            ->whereDate('starts_on', '>', now()->toDateString())
            ->oldest('starts_on')
            ->first();

        return view('subscriptions.required', compact(
            'user',
            'business',
            'latestSubscription',
            'nextSubscription'
        ));
    }

    private function availablePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('lifecycle_status', SubscriptionPlan::LIFECYCLE_ACTIVE)
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();
    }

    /**
     * Plans keyed by billing period, shortest first, each with its tab label.
     *
     * @return Collection<int, array{label: string, plans: Collection}>
     */
    private function groupByPeriod(Collection $plans): Collection
    {
        return $plans->groupBy('billing_period_days')
            ->sortKeys()
            ->map(fn (Collection $group, int $days) => [
                'label' => self::PERIOD_LABELS[$days] ?? "{$days} دن",
                'plans' => $group->values(),
            ]);
    }

    /**
     * How much each yearly plan saves against twelve months of the matching monthly plan.
     * Plans pair up by code, e.g. "business-pro-annual" with "business-pro-monthly".
     *
     * @return array<int, float> yearly plan id => rupees saved
     */
    private function yearlySavings(Collection $plans): array
    {
        $tier = fn (SubscriptionPlan $plan) => $this->tier($plan);
        $monthly = $plans->where('billing_period_days', 30)->keyBy($tier);

        $savings = [];
        foreach ($plans->where('billing_period_days', 365) as $plan) {
            $match = $monthly->get($tier($plan));
            $saved = $match ? (float) $match->price * 12 - (float) $plan->price : 0;
            if ($saved > 0) {
                $savings[$plan->id] = $saved;
            }
        }

        return $savings;
    }

    /**
     * The recommended plan plus its other billing periods, so "Popular" shows on every tab.
     *
     * @return array<int, int>
     */
    private function popularPlanIds(Collection $plans): array
    {
        $tiers = $plans->where('is_recommended', true)->map(fn (SubscriptionPlan $plan) => $this->tier($plan))->all();

        return $plans->filter(fn (SubscriptionPlan $plan) => in_array($this->tier($plan), $tiers, true))
            ->pluck('id')->all();
    }

    /** Plans of one tier share a code apart from the period suffix, e.g. "business-pro-monthly". */
    private function tier(SubscriptionPlan $plan): string
    {
        return preg_replace('/-(monthly|annual|yearly)$/', '', $plan->code);
    }
}
