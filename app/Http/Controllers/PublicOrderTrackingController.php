<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CustomerLedgerService;
use App\Support\OrderTrackingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicOrderTrackingController extends Controller
{
    /** Opens the tracking page from the short link in a receipt QR code. */
    public function short(int $order, string $code): RedirectResponse
    {
        abort_unless(OrderTrackingLink::isValid($order, $code), 403);

        return redirect()->to(OrderTrackingLink::signedUrl($order));
    }

    public function show(Order $order, CustomerLedgerService $customerLedger): View
    {
        $order->load('customers');

        [$previousBalance, $orderBalance] = $customerLedger->trackingSummary($order);
        $paymentReceived = $orderBalance <= 0;

        $setting = Setting::query()
            ->where('user_id', $order->userId)
            ->orderByDesc('status')
            ->first();

        $currentStatus = in_array($order->status, Order::STATUSES, true)
            ? $order->status
            : 'assigned';
        $simpleStatusLabels = [
            'unassigned' => 'آرڈر درج ہو گیا ہے',
            'assigned' => 'کارخانے میں ہے',
            'cutting' => 'کارخانے میں ہے',
            'stitching' => 'کارخانے میں ہے',
            'trial' => 'کارخانے میں ہے',
            'ready' => 'تیار ہے',
            'delivered' => 'حوالہ کر دیا گیا ہے',
        ];
        $detailedWorkflow = Business::tailoringStatusModeForOwner((int) $order->userId)
            === Business::TAILORING_STATUS_DETAILED;

        return view('order.track', [
            'order' => $order,
            'setting' => $setting,
            'paymentReceived' => $paymentReceived,
            'previousBalance' => $previousBalance,
            'currentStatusLabel' => $detailedWorkflow
                ? Order::STATUS_LABELS[$currentStatus]
                : $simpleStatusLabels[$currentStatus],
        ]);
    }
}
