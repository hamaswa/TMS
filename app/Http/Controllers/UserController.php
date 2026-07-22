<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ServerNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('user.index', compact('user'));
    }

    public function edit($id)
    {
        $user = User::find($id);
        return view('user.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = [
            'name' => $request->input('userName'),
            'email' => $request->input('userEmail'),
        ];

        // Only update password if new one is provided
        if ($request->filled('newPassword')) {
            $data['password'] = Hash::make($request->input('newPassword'));
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', 'User updated successfully');
    }

    // public function notificationsStream()
    // {
    //     $response = new StreamedResponse(function () {
    //         $userId = Auth::id();

    //         while (true) {
    //             // Fetch the first unread notification for the current user
    //             $notifications = ServerNotifications::where('user_id', $userId)
    //                 ->where('is_send', 0)
    //                 ->first();

    //             if ($notifications) {
    //                 // Send the notification message
    //                 echo "data: " . json_encode(['message' => $notifications->message]) . "\n\n";
    //                 $notifications->is_send = 1;
    //                 $notifications->save();  // Mark as seen

    //                 // Flush the output to ensure it's sent to the client
    //                 ob_flush();
    //                 flush();
    //             } else {
    //                 // If no new notifications, send a dummy message to keep the connection alive
    //                 echo "data: " . json_encode(['message' => 'Waiting for new notifications...']) . "\n\n";
    //                 ob_flush();
    //                 flush();
    //             }

    //             // Sleep for a short period to avoid overwhelming the server
    //             sleep(2);
    //         }
    //     });

    //     // Set the necessary headers for SSE
    //     $response->headers->set('Content-Type', 'text/event-stream');
    //     $response->headers->set('Cache-Control', 'no-cache');
    //     $response->headers->set('Connection', 'keep-alive');

    //     return $response;
    // }
}
