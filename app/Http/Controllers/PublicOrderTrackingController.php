<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CustomerLedgerService;
use Illuminate\View\View;

class PublicOrderTrackingController extends Controller
{
    public function show(Order $order, CustomerLedgerService $customerLedger): View
    {
        $order->load('customers');

        [, $previousBalance, $orderBalance] = $customerLedger->receiptSummary($order);
        $paymentReceived = $orderBalance <= 0;

        $setting = Setting::query()
            ->where('user_id', $order->userId)
            ->orderByDesc('status')
            ->first();

        $currentStatus = in_array($order->status, Order::STATUSES, true)
            ? $order->status
            : 'assigned';
        $simpleStatusLabels = [
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
