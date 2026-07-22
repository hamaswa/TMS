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
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReactNativeController extends Controller
{
    // api routes controller methods
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'shop_id' => ['required', 'integer', 'exists:users,id'],
            'pin' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $customer = Customers::where('user_id', $validatedData['shop_id'])
                ->where('phone_number1', $validatedData['phone'])
                ->first();

            if ($customer?->pin_locked_until?->isFuture()) {
                return response()->json([
                    'message' => 'زیادہ غلط کوششوں کی وجہ سے لاگ اِن عارضی طور پر بند ہے۔ 15 منٹ بعد دوبارہ کوشش کریں۔',
                ], 423);
            }

            if (! $customer || ! $customer->mobile_pin || ! Hash::check($validatedData['pin'], $customer->mobile_pin)) {
                if ($customer) {
                    $customer->increment('pin_failed_attempts');
                    $customer->refresh();

                    if ($customer->pin_failed_attempts >= 5) {
                        $customer->forceFill(['pin_locked_until' => now()->addMinutes(15)])->save();
                    }
                }

                return response()->json([
                    'message' => 'فون نمبر، پن یا دکان درست نہیں ہے۔',
                ], 401);
            }

            $customer->forceFill([
                'pin_failed_attempts' => 0,
                'pin_locked_until' => null,
            ])->save();

            return response()->json([
                'customer' => $customer,
                'token' => $customer->createToken($validatedData['device_name'] ?? 'mobile')->plainTextToken,
            ], 200);
        } catch (Exception $e) {
            Log::error('Mobile customer login failed unexpectedly.', [
                'shop_id' => $validatedData['shop_id'],
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'لاگ اِن مکمل نہیں ہو سکا۔ براہ کرم دوبارہ کوشش کریں۔',
            ], 500);
        }
    }

    public function changePin(Request $request)
    {
        $validated = $request->validate([
            'current_pin' => ['required', 'digits:6'],
            'new_pin' => ['required', 'digits:6', 'different:current_pin'],
        ]);
        /** @var Customers $customer */
        $customer = $request->user();

        if (! $customer->mobile_pin || ! Hash::check($validated['current_pin'], $customer->mobile_pin)) {
            return response()->json(['message' => 'موجودہ پن درست نہیں ہے۔'], 422);
        }

        $customer->forceFill([
            'mobile_pin' => Hash::make($validated['new_pin']),
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
            'pin_changed_at' => now(),
        ])->save();

        return response()->json(['message' => 'پن کامیابی سے تبدیل ہو گیا ہے۔']);
    }

    public function notifications(Request $request)
    {
        try {
            $user = $request->user();

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

    public function markasRead(Request $request)
    {
        try {
            $user = $request->user();

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

    public function AllOrders(Request $request)
    {
        $customer = $request->user();
        $orders = Order::with(['customers', 'transactions'])
            ->where(function ($query) use ($customer) {
                $query->where('customerId', $customer->id)
                    ->orWhere('sub_customer', $customer->id);
            })
            ->latest()
            ->get();
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

    public function AllTransactions(Request $request)
    {
        $transactions = Transaction::where('customerId', $request->user()->id)->latest()->get();
        return response()->json(['transactions' => $transactions], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
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
