<?php

namespace App\Http\Controllers\ReactNativeController;

use Exception;
use App\Models\Order;
use App\Models\Customers;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ServerNotifications;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReactNativeController extends Controller
{
    // api routes controller methods
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string'
        ]);

        try {
            $customer = Customers::where('name', $validatedData['name'])->where('phone_number1', $validatedData['phone'])->first();

            if (!$customer) {
                return response()->json([
                    'message' => 'Customer not found or invalid credentials.'
                ], 404); // Return a 404 status code for not found
            }
            $notifications = $customer->notifications;
            // Count unread notifications (where read_at is null)
            $unreadCount = $customer->notifications()->whereNull('read_at')->count();
            return response()->json([
                'customer' => $customer,
            ], 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function notifications($id)
    {
        try {
            $user = Customers::find($id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Fetch notifications and unread count
            $notifications = $user->notifications; // Or $user->unreadNotifications for unread ones
            $unreadCount = $user->notifications()->whereNull('read_at')->count();

            return response()->json([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function markasRead($id)
    {
        try {
            $user = Customers::find($id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $notifications = $user->notifications()->whereNull('read_at')->get();

            foreach ($notifications as $notification) {
                $notification->markAsRead();
            }


            return response()->json([
                'success' => 'updated',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function AllOrders()
    {
        $orders = Order::with(['customers', 'transactions'])->latest()->get();
        return response()->json(['orders' => $orders], 200);
    }
    
    public function AllShops()
    {
        try{
            $shops = Setting::all();
        return response()->json(['shops' => $shops], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }

    public function AllTransactions()
    {
        $transactions = Transaction::latest()->get();
        return response()->json(['transactions' => $transactions], 200);
    }
    // public function testingSSE()
    // {
    //     Log::info('SSE Stream started'); // Log that the stream has started

    //     $response = new StreamedResponse(function () {
    //         while (true) {
    //             // Log each time a message is sent
    //             Log::info('Sending SSE message: {"message": "This is a test notification from Laravel!"}');

    //             echo "data: {\"message\": \"This is a test notification from Laravel!\"}\n\n";
    //             ob_flush();
    //             flush();
    //             sleep(3); // Delay between messages
    //         }
    //     });

    //     // Set headers for SSE
    //     $response->headers->set('Content-Type', 'text/event-stream');
    //     $response->headers->set('Cache-Control', 'no-cache');
    //     $response->headers->set('Connection', 'keep-alive');
    //     $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173'); // Allow the React frontend

    //     Log::info('SSE Stream headers set'); // Log after setting headers

    //     return $response;
    // }
}
