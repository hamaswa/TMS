<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\rack;
use App\Models\Order;
use PhpOption\Option;
use App\Models\Design;
use App\Models\Tailor;
use App\Models\Options;
use App\Models\Setting;
use App\Models\Customers;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\Tailorsalary;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
use App\Events\CompleteOrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderCompleteNotification;


class OrderController extends Controller
{

    public function edit($id)
    {

        $data = Order::findOrFail($id);
        $sub_customer = Customers::find($data->sub_customer);
        $customer = Customers::find($data->customerId);
        $tailors = Tailor::where('user_id', Auth::user()->id)->get();
        $tailor = Tailorsalary::where('id', $data->rateId)->first();
        // dd($tailor);
        // Fetch the tailor rate and options
        $tailorRate = Tailorsalary::with('options')->where('id', $data->rateId)->first();
        $currentTailorRate = $tailorRate ? $tailorRate->price : null;
        $optionName = $tailorRate && $tailorRate->options ? $tailorRate->options->Name : '';
        $remainingBalance = Transaction::where("customerId", $data->customerId)->sum('remainingBalance');
        $recivedPayment = Transaction::where("customerId", $data->customerId)->where("orderId", $data->id)->first()->recivedPayment;
        $data['design'] = Options::where('option_id', 1)->get();
        // $currentTailorRate = 1220;
        return view('order.edit', compact('data', 'tailors', 'remainingBalance', 'recivedPayment', 'sub_customer', 'customer', 'currentTailorRate', 'optionName'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        //new change
        $parts = explode("-", $request->tailor_price);
        // Use old values if parts don't have new values
        $rateId = isset($parts[0]) && $parts[0] !== '' ? $parts[0] : $order->rateId;
        $tailorPrice = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : $order->tailor_price;
        $remainingBalance = $request->totalPayment - $request->recivedPayment;

        $order->update([
            "sub_customer" => $request->sub_id,
            "customerId" => $request->customerId,
            "suitQuantity" => $request->suitQuantity,
            "totalPayment" => $request->totalPayment,
            "tailorId" => $request->tailorId,
            "rateId" => $rateId, //new change
            "tailor_price" => $tailorPrice, //new change
            "userId" => auth()->user()->id,
            "returnDate" => $request->returnDate,
            "remarks" => $request->remarks,
            "tailor_price" => $tailorPrice
        ]);



        $transaction = Transaction::where("orderId", $order->id)->first();
        $transaction->update([
            "recivedPayment" => $request->recivedPayment,
            "remainingBalance" => $remainingBalance,
            "Order_type" => 'Tailor',
            "customerId" => $request->customerId,
            "userId" => auth()->user()->id,
        ]);

        return redirect('admin/Customers')->with('insert', 'Order Updated');
    }

    public function createOrder($id)
    {
        // Retrieve all customers for the authenticated user
        $customers = Customers::where('user_id', auth()->user()->id)->get();
        // Find the customer based on the given ID
        $customer = $customers->where('id', $id)->first();
        $data = [];
        $data['customer'] = $customer;
        $data['remainingBalance'] = Transaction::where('customerId', $id)->where('Order_type', 'Tailor')->sum('remainingBalance');
        $data['tailors'] = Tailor::where('user_id', Auth::user()->id)->get();
        $data['childData'] = Customers::where('parent_id', $id)->get();
        $data['design'] = Options::where('option_id', 1)->where('user_id', auth()->user()->id)->get();

        // Get the serial number by searching through the collection
        $data['serialNumber'] = $customers->search(function ($item) use ($customer) {
            return $item->id === $customer->id;
        }) + 1; // Adding 1 to make it 1-based index
        return view('order.create', compact('data'));
    }

    public function insert(Request $req)
    {
        //new change
        $parts = explode("-", $req->tailor_price);
        $designParts = explode("-", $req->design);

        $req['rateId'] =      $parts[0];
        $req['tailor_price'] = $parts[1]; //new change

        $req['design_name'] = $designParts[0] ?? 0;
        $req['desId'] = $designParts[1] ?? 0;
        // dd($req['design_name']);
        $sub_customer_id = "";
        if ($req->sub_id) {
            $sub_customer_id = $req->sub_id;
        } else {
            $sub_customer_id = $req->customerId;
        }

        $sub_customer_id = $req->sub_id ? $req->sub_id : $req->customerId; //new change

        $obj = new Order;
        $obj->customerId = $req['customerId'];
        $obj->sub_customer = $sub_customer_id;
        $obj->suitQuantity = $req['suitQuantity'];
        $obj->totalPayment = $req['totalPayment'];
        $obj->returnDate = $req['returnDate'];
        // $suitNumbers = $req->input('suitNum');
        $obj->design = $req['design_name'];
        $obj->tailorId = $req['tailorId'];
        $obj->rateId = $req['rateId'];
        $obj->userId = Auth::user()->id;
        $obj->remarks = $req['remarks'];
        $obj->tailor_price = $req['tailor_price']; //new change
        $obj->suitNum = $req['serail'];
        $obj->designPrice = $req['designPrice'] ?? 0;
        $obj->save();

        $Id = $obj->id;

        // transaction
        $transaction = new Transaction;
        $transaction->recivedPayment = $req['recivedPayment'];
        $transaction->remainingBalance = $req['balance'];
        $transaction->orderId = $Id;
        $transaction->customerId = $req['customerId'];
        $transaction->userId = Auth::user()->id;
        $transaction->Order_type = 'Tailor';
        $transaction->save();

        // find shop
        $setting = Setting::where('user_id', $obj->userId)->first();

        // Create the notification
        $notification = new NewOrderNotification($obj, $transaction, $setting);

        $customer = Customers::find($req['customerId']);
        // dd($customer);

        // Notify the user (adjust the 'user' to the correct user model)
        Notification::send($customer, $notification);

        // event(new NotificationEvent("You have notification from {$setting->name}"));

        return redirect(url('/admin/order/print/' . $Id));
    }

    public function getCustomer(Request $req)
    {
        try {
            $id = $req->id;
            $Orders = DB::table('orders')
                ->join('tailors', 'orders.tailorId', '=', 'tailors.id')
                ->select('orders.*', 'tailors.name as tailor_name')
                ->where('orders.customerId', $id)
                ->get();
            $racks = rack::where('user_id', auth()->user()->id)->get();

            $data = [];
            $i = 1;
            foreach ($Orders as $order) {
                $button = '';
                if ($order->status == 'new') {
                    $button = 'New';
                    $btn = 'btn-primary';
                } elseif ($order->status == 'start') {
                    $button = 'Start';
                    $btn = 'btn-warning';
                } else {
                    $button = 'Complete';
                    $btn = 'btn-success';
                }
                $data[] = [
                    'number' => $i++,
                    'totalPayment' => $order->totalPayment,
                    'created_at' => date('d-m-Y', strtotime($order->created_at)),
                    'returnDate' => date('d-m-Y', strtotime($order->returnDate)),
                    'suitQuantity' => $order->suitQuantity,
                    'tailorName' => $order->tailor_name,
                    'button' => $button,
                    'btnClass' => $btn,
                    'orderId' => $order->id,
                    'rack_no' => $order->rack_no,
                    'racks' => $racks
                ];
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function print($id)
    {
        $order = Order::find($id);
        $tailor_id = $order->tailorId;
        $tailor = Tailor::find($tailor_id);


        $customerId = $order->customerId;

        // Find the latest order for the customer
        $orderDetail = Order::where('customerId', $customerId)->latest()->first();
        
        // dd($orderDetail);

        // Filter transactions for the current customer
        $customerTransactions = Transaction::where("customerId", $customerId)->where('Order_type', 'Tailor')->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        $setting = Setting::where('user_id', Auth::user()->id)->where('status', 1)->first();

        if (!$setting) {
            dd("Please Activate Your Setting");
        } else {
            $status = "default";
            return view('order.print', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance','tailor'));
        }
    }




    // public function print($id)
    // {
    //     $balance=0;

    //     $orderDetail = Order::with('transactions','tailor','customers')->find($id);

    //     $customerId=$orderDetail->customerId;

    //     $balance=Transaction::where("customerId",$customerId)->sum('remainingBalance');

    //     $setting = Setting::where('user_id',Auth::user()->id)->where('status',1)->first();
    //     if(!$setting)
    //     {
    //         dd("Please Activate Your Setting");
    //     }else{
    //         $status= "default";
    //         return view('order.print',compact('orderDetail','setting','status','balance'));
    //     }

    // }
    public function two_prints($id)
    {
        $order = Order::find($id);
        $tailor_id = $order->tailorId;
        $tailor = Tailor::find($tailor_id);


        $customerId = $order->customerId;

        // Find the latest order for the customer
        $orderDetail = Order::where('customerId', $customerId)->latest()->first();
        
        // Filter transactions for the current customer
        $customerTransactions = Transaction::where("customerId", $customerId)->where('Order_type', 'Tailor')->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        $setting = Setting::where('user_id', Auth::user()->id)->where('status', 1)->first();

        if (!$setting) {
            dd("Please Activate Your Setting");
        } else {
            $status = "default";
            return view('order.print', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance','tailor'));
        }
    }

    public function search(Request $req)
    {
        $data = Customers::where('name', 'like', '%' . $req->sub_search . '%')
            ->orWhere('phone_number1', 'like', '%' . $req->sub_search . '%')
            ->orWhere('id', 'like', '%' . $req->sub_search . '%')
            ->get();
        $html = "";
        $html .= "<select class='form-control' style='height:50px' name='sub_id'>";
        foreach ($data as $val) {
            $html .= "<option value='" . $val->id . "'>" . $val->id . " - " . $val->name . " - " . $val->phone_number1 . "</option>";
        }
        $html .= "</select>";
        return $html;
    }

    public function order_status(Request $req)
    {
        $req->order_status;
        $req->order_id;
        $obj = Order::find($req->order_id);
        $obj->status = $req->order_status;
        $obj->save();
        return back();
    }

    public function totalOrder()
    {
        $monthly_orders = [];

        for ($month = 1; $month <= 12; $month++) {
            $orders = Order::where('userId', auth()->user()->id)
                ->whereMonth('created_at', $month)
                ->get();

            $ordersCount = $orders->count();
            $totalSuitQuantity = $orders->sum('suitQuantity');
            $totalpayment = $orders->sum('totalPayment');

            $newOrdersCount = $orders->where('status', 'new')->count();
            $processingOrdersCount = $orders->where('status', 'start')->count();
            $completedOrdersCount = $orders->where('status', 'complete')->count();

            $monthname = DateTime::createFromFormat('!m', $month)->format('F');

            $monthly_orders[$monthname] = [
                'orders' => $ordersCount,
                'suits' => $totalSuitQuantity,
                'payment' => $totalpayment,
                'neworders' => $newOrdersCount,
                'inprocessorders' => $processingOrdersCount,
                'completed' => $completedOrdersCount,
            ];
        }
        // dd($orders);
        return view('All_Total.order', compact('monthly_orders'));
    }

    public function updateRackNo(Request $request, $orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->rack_no = $request->input('rack_no');
        $order->save();

        return response()->json(['message' => 'Rack number updated successfully'], 200);
    }

    public function orderCompleteNotify(Request $request)
    {
        Log::info('Order ID:', ['id' => $request->order_id]);

        $order = Order::with('transactions')->where('id', $request->order_id)->first();
        if ($order) {
            Log::info('Order found:', ['order' => $order]);
            $setting = Setting::where('user_id', $order->userId)->first();

            $customer = $order->customers; // Ensure this returns a valid notifiable entity
            if ($customer) {
                Log::info('Customer found:', ['customer' => $customer]);

                $notification = new OrderCompleteNotification($order,$setting);
                Notification::send($customer, $notification);
                Log::info('Notification sent.');

                // Add entry to server_notifications table
                DB::table('server_notifications')->insert([
                    'user_id' => $order->userId,
                    'customer_id' => $customer->id,
                    'message' => 'Your order has been completed.',
                    'is_send' => 0,  // Mark as unsent for SSE
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // find shop
                $setting = Setting::where('user_id', $order->userId)->first();

                // event(new CompleteOrderEvent("You have notification from {$setting->name}"));

                return response()->json(['message' => 'Notification sent successfully.']);
            } else {
                return response()->json(['message' => 'Customer not found.'], 404);
            }
        } else {
            return response()->json(['message' => 'Order not found.'], 404);
        }
    }
}
