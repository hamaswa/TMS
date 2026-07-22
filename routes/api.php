<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReactNativeController\ReactNativeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/user',[CustomerController::class,'test']);
Route::post('/login',[ReactNativeController::class,'login']);

// api route to show orders and transactions
Route::get('/orders',[ReactNativeController::class,'AllOrders']);
Route::get('/transactions',[ReactNativeController::class,'AllTransactions']);
Route::get('/shops',[ReactNativeController::class,'AllShops']);

Route::get('/notifications/{id}',[ReactNativeController::class,'notifications']);
Route::get('/mark-read/{id}',[ReactNativeController::class,'markasRead']);

// for server stream (SSE) route
// Route::get('/notifications/sse', function () {
//     return response()->stream(function () {
//         while (true) {
//             // Check for any new notifications that haven't been sent
//             $notifications = DB::table('server_notifications')
//                 ->where('is_send', 0)
//                 ->get();

//             if ($notifications->isNotEmpty()) {
//                 foreach ($notifications as $notification) {
//                     echo "data: " . json_encode($notification) . "\n\n";

//                     // Update notification as sent
//                     DB::table('server_notifications')
//                         ->where('id', $notification->id)
//                         ->update(['is_send' => 1]);
//                 }
//             }

//             // Flush the output buffer and send the data
//             ob_flush();
//             flush();

//             // Delay to avoid overwhelming the server
//             sleep(5);
//         }
//     }, 200, [
//         'Content-Type' => 'text/event-stream',
//         'Cache-Control' => 'no-cache',
//         'Connection' => 'keep-alive',
//         'Access-Control-Allow-Origin' => 'http://localhost:5173',
//     ]);
// });
Route::get('/notification-stream', [ReactNativeController::class, 'testingSSE'])
    ->withoutMiddleware('throttle:api');
