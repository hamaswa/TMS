<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function saveSubscription(Request $request)
    {
        $items = new PushNotification();
        $items->subscriptions = json_decode($request->sub);
        $items->save();

        return response()->json(['message' => 'added successfully'],200);
    }
}
