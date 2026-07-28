<?php

namespace App\Http\Controllers;

use App\Models\OnlineOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showNotifications()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(30);
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function AdminNotifications()
    {
        // Fetch notifications for the authenticated user
        $notifications = Auth::user()->notifications;

        // Mark all notifications as read
        Auth::user()->unreadNotifications->markAsRead();

        return view('notifications.admin', compact('notifications'));
    }
    public function UserNotifications()
    {
        // Fetch notifications for the authenticated user
        $notifications = Auth::user()->notifications;

        // Mark all notifications as read
        Auth::user()->unreadNotifications->markAsRead();

        return view('notifications.user', compact('notifications'));
    }

    public function showOnlineOrders()
    {
        $orders = OnlineOrder::where('admin_user_id', Auth::user()->businessOwnerId())
            ->with(['user:id,name', 'cloth.brand:id,name', 'cloth.type:id,name'])
            ->latest()
            ->get();

        return view('OnlineOrders.index', compact('orders'));
    }

    public function readNotifications(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function orderComplete(int $id)
    {
        $order = OnlineOrder::where('admin_user_id', Auth::user()->businessOwnerId())
            ->findOrFail($id);
        $order->update(['status' => 'complete']);

        return response()->json(['success' => true]);
    }
}
