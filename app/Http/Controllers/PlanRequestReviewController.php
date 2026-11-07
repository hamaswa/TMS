<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlanRequest;
use App\Services\SubscriptionPlanRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Super admin: approve or reject a shop's plan request after checking the Easypaisa payment. */
class PlanRequestReviewController extends Controller
{
    public function __construct(private readonly SubscriptionPlanRequestService $requests) {}

    /** Polled by the super admin pages: new plan requests since the last alert. */
    public function unannounced()
    {
        return response()->json([
            'requests' => SubscriptionPlanRequest::takeUnannouncedFor(Auth::user())
                ->map(fn (SubscriptionPlanRequest $request) => $request->alertData())->values(),
        ]);
    }

    public function approve(SubscriptionPlanRequest $planRequest)
    {
        $subscription = $this->requests->approve($planRequest, Auth::user());

        return redirect()->route('administrator.clients.show', $planRequest->business->owner_user_id)
            ->with('success', "Approved. {$subscription->plan_name} is active from {$subscription->starts_on->format('d M Y')} to {$subscription->ends_on->format('d M Y')}.");
    }

    public function reject(Request $request, SubscriptionPlanRequest $planRequest)
    {
        $validated = $request->validateWithBag('plan_request_'.$planRequest->id, [
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->requests->reject($planRequest, Auth::user(), $validated['reason']);

        return redirect()->route('administrator.clients.show', $planRequest->business->owner_user_id)
            ->with('success', 'Request rejected. The shop will see your reason and can try again.');
    }

    public function proof(SubscriptionPlanRequest $planRequest)
    {
        return $this->requests->proofResponse($planRequest);
    }
}
