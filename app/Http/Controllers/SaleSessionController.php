<?php

namespace App\Http\Controllers;

use App\Models\SaleSession;
use App\Services\SaleSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = $this->tenantQuery($request)->with(['agent', 'claimedBy', 'receipt'])
            ->latest('updated_at')->paginate(30);

        return view('sales-sessions.index', compact('sessions'));
    }

    public function show(Request $request, SaleSession $saleSession): RedirectResponse
    {
        $this->authorizeTenant($request, $saleSession);

        return redirect()->route('admin.sellCloth', ['sale_session' => $saleSession->uuid]);
    }

    public function feed(Request $request, SaleSessionService $service): JsonResponse
    {
        $sessions = $this->tenantQuery($request)->with(['agent', 'claimedBy', 'completedBy', 'receipt'])
            ->where(function ($query) {
                $query->whereIn('status', [SaleSession::STATUS_ACTIVE, SaleSession::STATUS_NEEDS_ATTENTION, SaleSession::STATUS_CLAIMED])
                    ->orWhere(fn ($recent) => $recent->where('status', SaleSession::STATUS_COMPLETED)->where('completed_at', '>=', now()->subDay()));
            })
            ->latest('updated_at')->limit(50)->get()->map(fn ($session) => $service->serialize($session));

        return response()->json([
            'attentionCount' => $sessions->where('status', SaleSession::STATUS_NEEDS_ATTENTION)->count(),
            'openCount' => $sessions->whereIn('status', [
                SaleSession::STATUS_ACTIVE,
                SaleSession::STATUS_NEEDS_ATTENTION,
                SaleSession::STATUS_CLAIMED,
            ])->count(),
            'data' => $sessions,
        ]);
    }

    public function claim(Request $request, SaleSession $saleSession, SaleSessionService $service): RedirectResponse
    {
        $this->authorizeTenant($request, $saleSession);
        $service->claim($saleSession, $request->user());

        return back()->with('success', 'Sale session claimed.');
    }

    public function complete(Request $request, SaleSession $saleSession, SaleSessionService $service): RedirectResponse
    {
        $this->authorizeTenant($request, $saleSession);
        $completed = $service->complete($saleSession, $request->user());

        return redirect()->route('admin.printStock', [
            'id' => $completed->receipt->first_sale_stock_id,
            'customerId' => $completed->receipt->customer_id,
        ]);
    }

    private function tenantQuery(Request $request)
    {
        return SaleSession::where('user_id', $request->user()->businessOwnerId());
    }

    private function authorizeTenant(Request $request, SaleSession $session): void
    {
        abort_unless($session->user_id === $request->user()->businessOwnerId(), 404);
    }
}
