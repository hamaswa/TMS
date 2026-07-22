<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showNotifications()
    {
        // Fetch notifications for the authenticated user
        $notifications = Auth::user()->notifications;

        // Mark all notifications as read
        Auth::user()->unreadNotifications->markAsRead();

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
}
