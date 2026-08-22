<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\View\View;

class PublicOrderTrackingController extends Controller
{
    public function show(Order $order): View
    {
        $order->load('customers');

        $remainingBalance = max(0, round((float) Transaction::query()
            ->where('userId', $order->userId)
            ->where('customerId', $order->customerId)
            ->sum('remainingBalance'), 2));

        $setting = Setting::query()
            ->where('user_id', $order->userId)
            ->orderByDesc('status')
            ->first();

        $currentStatus = in_array($order->status, Order::STATUSES, true)
            ? $order->status
            : 'assigned';

        return view('order.track', [
            'order' => $order,
            'setting' => $setting,
            'remainingBalance' => $remainingBalance,
            'currentStatus' => $currentStatus,
            'currentStatusLabel' => Order::STATUS_LABELS[$currentStatus],
            'statuses' => Order::STATUSES,
        ]);
    }
}
