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

        $data = $this->ownedOrder($id);
        $sub_customer = Customers::where('user_id', Auth::user()->businessOwnerId())->find($data->sub_customer);
        $customer = Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($data->customerId);
        $tailors = Tailor::where('user_id', Auth::user()->businessOwnerId())->get();
        $tailor = Tailorsalary::where('id', $data->rateId)->first();
        // dd($tailor);
        // Fetch the tailor rate and options
        $tailorRate = Tailorsalary::with('options')->where('id', $data->rateId)->first();
        $currentTailorRate = $tailorRate ? $tailorRate->price : null;
        $optionName = $tailorRate && $tailorRate->options ? $tailorRate->options->Name : '';
        $remainingBalance = Auth::user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES)
            ? Transaction::where('userId', Auth::user()->businessOwnerId())->where("customerId", $data->customerId)->sum('remainingBalance')
            : null;
        $recivedPayment = Transaction::where('userId', Auth::user()->businessOwnerId())->where("customerId", $data->customerId)->where("orderId", $data->id)->value('recivedPayment') ?? 0;
        $data['design'] = Options::where('option_id', 1)->get();
        // $currentTailorRate = 1220;
        return view('order.edit', compact('data', 'tailors', 'remainingBalance', 'recivedPayment', 'sub_customer', 'customer', 'currentTailorRate', 'optionName'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sub_id' => ['nullable', 'integer'],
            'customerId' => ['required', 'integer'],
            'suitQuantity' => ['required', 'integer', 'min:1'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            'recivedPayment' => ['required', 'numeric', 'min:0'],
            'tailorId' => ['required', 'integer'],
            'tailor_price' => ['nullable', 'string', 'max:255'],
            'returnDate' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $this->ownedOrder($id);
        $this->ownedCustomer($validated['customerId']);
        $this->ownedTailor($validated['tailorId']);
        if (!empty($validated['sub_id'])) {
            $this->ownedCustomer($validated['sub_id']);
        }

        //new change
        $parts = explode("-", $validated['tailor_price'] ?? '', 2);
        // Use old values if parts don't have new values
        $rateId = isset($parts[0]) && $parts[0] !== '' ? $parts[0] : $order->rateId;
        $tailorPrice = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : $order->tailor_price;
        Tailorsalary::where('tailor_id', $validated['tailorId'])->findOrFail($rateId);
        $remainingBalance = max(0, $validated['totalPayment'] - $validated['recivedPayment']);

        DB::transaction(function () use ($validated, $order, $rateId, $tailorPrice, $remainingBalance) {
            $order->update([
                "sub_customer" => $validated['sub_id'] ?? $validated['customerId'],
                "customerId" => $validated['customerId'],
                "suitQuantity" => $validated['suitQuantity'],
                "totalPayment" => $validated['totalPayment'],
                "tailorId" => $validated['tailorId'],
                "rateId" => $rateId,
                "tailor_price" => $tailorPrice,
                "userId" => Auth::user()->businessOwnerId(),
                "returnDate" => $validated['returnDate'],
                "remarks" => $validated['remarks'] ?? null,
            ]);

            Transaction::updateOrCreate(
                ['orderId' => $order->id, 'userId' => Auth::user()->businessOwnerId()],
                [
                    "recivedPayment" => $validated['recivedPayment'],
                    "remainingBalance" => $remainingBalance,
                    "Order_type" => 'Tailor',
                    "customerId" => $validated['customerId'],
                ]
            );
        });

        return redirect('admin/Customers')->with('insert', 'Order Updated');
    }

    public function createOrder($id)
    {
        // Retrieve all customers for the authenticated user
        $customers = Customers::where('user_id', auth()->user()->businessOwnerId())->get();
        // Find the customer based on the given ID
        $customer = $customers->where('id', $id)->firstOrFail();
        $data = [];
        $data['customer'] = $customer;
        $data['remainingBalance'] = Auth::user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES)
            ? Transaction::where('userId', Auth::user()->businessOwnerId())->where('customerId', $id)->sum('remainingBalance')
            : null;
        $data['tailors'] = Tailor::where('user_id', Auth::user()->businessOwnerId())->get();
        $data['childData'] = Customers::where('parent_id', $id)->get();
        $data['design'] = Options::where('option_id', 1)->where('user_id', auth()->user()->businessOwnerId())->get();

        // Get the serial number by searching through the collection
        $data['serialNumber'] = $customers->search(function ($item) use ($customer) {
            return $item->id === $customer->id;
        }) + 1; // Adding 1 to make it 1-based index
        return view('order.create', compact('data'));
    }

    public function insert(Request $req)
    {
        $validated = $req->validate([
            'customerId' => ['required', 'integer'],
            'sub_id' => ['nullable', 'integer'],
            'suitQuantity' => ['required', 'integer', 'min:1'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            'recivedPayment' => ['required', 'numeric', 'min:0'],
            'balance' => ['required', 'numeric', 'min:0'],
            'returnDate' => ['required', 'date'],
            'design' => ['nullable', 'string', 'max:255'],
            'designPrice' => ['nullable', 'numeric', 'min:0'],
            'tailorId' => ['required', 'integer'],
            'tailor_price' => ['required', 'regex:/^\d+-.+$/', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'serail' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ownedCustomer($validated['customerId']);
        $tailor = $this->ownedTailor($validated['tailorId']);
        if (!empty($validated['sub_id'])) {
            $this->ownedCustomer($validated['sub_id']);
        }

        [$rateId, $tailorPrice] = explode('-', $validated['tailor_price'], 2);
        Tailorsalary::where('tailor_id', $tailor->id)->findOrFail($rateId);
        $designParts = explode('-', $validated['design'] ?? '', 2);
        $subCustomerId = $validated['sub_id'] ?? $validated['customerId'];

        [$obj, $transaction] = DB::transaction(function () use ($validated, $rateId, $tailorPrice, $designParts, $subCustomerId) {
            $obj = Order::create([
                'customerId' => $validated['customerId'],
                'sub_customer' => $subCustomerId,
                'suitQuantity' => $validated['suitQuantity'],
                'totalPayment' => $validated['totalPayment'],
                'returnDate' => $validated['returnDate'],
                'design' => $designParts[0] ?: 0,
                'tailorId' => $validated['tailorId'],
                'rateId' => $rateId,
                'userId' => Auth::user()->businessOwnerId(),
                'remarks' => $validated['remarks'] ?? null,
                'tailor_price' => $tailorPrice,
                'suitNum' => $validated['serail'] ?? null,
                'designPrice' => $validated['designPrice'] ?? 0,
                'status' => 'assigned',
                'status_changed_at' => now(),
                'tailor_paid_amount' => 0,
                'tailor_payment_status' => 'unpaid',
            ]);

            $transaction = Transaction::create([
                'recivedPayment' => $validated['recivedPayment'],
                'remainingBalance' => $validated['balance'],
                'orderId' => $obj->id,
                'customerId' => $validated['customerId'],
                'userId' => Auth::user()->businessOwnerId(),
                'Order_type' => 'Tailor',
            ]);

            return [$obj, $transaction];
        });

        $Id = $obj->id;

        // find shop
        $setting = Setting::where('user_id', $obj->userId)->first();

        // Create the notification
        $notification = new NewOrderNotification($obj, $transaction, $setting);

        $customer = $this->ownedCustomer($validated['customerId']);
        // dd($customer);

        // Notify the user (adjust the 'user' to the correct user model)
        try {
            Notification::send($customer, $notification);
        } catch (\Throwable $exception) {
            Log::warning('Order created but customer notification failed.', [
                'order_id' => $obj->id,
                'error' => $exception->getMessage(),
            ]);
        }

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
                ->where('orders.userId', Auth::user()->businessOwnerId())
                ->get();
            $racks = rack::where('user_id', auth()->user()->businessOwnerId())->get();

            $data = [];
            $i = 1;
            foreach ($Orders as $order) {
                $button = ucfirst($order->status ?: 'assigned');
                $btn = match ($order->status) {
                    'assigned' => 'btn-primary',
                    'cutting', 'stitching', 'trial' => 'btn-warning',
                    'ready', 'delivered' => 'btn-success',
                    default => 'btn-secondary',
                };
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
        $order = $this->ownedOrder($id);
        $tailor_id = $order->tailorId;
        $tailor = $this->ownedTailor($tailor_id);


        $customerId = $order->customerId;

        // Find the latest order for the customer
        $orderDetail = Order::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customerId)->latest()->first();
        
        // dd($orderDetail);

        // Filter transactions for the current customer
        $customerTransactions = Transaction::where('userId', Auth::user()->businessOwnerId())->where("customerId", $customerId)->where('Order_type', 'Tailor')->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->where('status', 1)->first();

        if (!$setting) {
            return back()->with('error', 'پرنٹ کرنے سے پہلے دکان کی فعال ترتیب منتخب کریں۔');
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

    //     $setting = Setting::where('user_id',Auth::user()->businessOwnerId())->where('status',1)->first();
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
        $order = $this->ownedOrder($id);
        $tailor_id = $order->tailorId;
        $tailor = $this->ownedTailor($tailor_id);


        $customerId = $order->customerId;

        // Find the latest order for the customer
        $orderDetail = Order::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customerId)->latest()->first();
        
        // Filter transactions for the current customer
        $customerTransactions = Transaction::where('userId', Auth::user()->businessOwnerId())->where("customerId", $customerId)->where('Order_type', 'Tailor')->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->where('status', 1)->first();

        if (!$setting) {
            return back()->with('error', 'پرنٹ کرنے سے پہلے دکان کی فعال ترتیب منتخب کریں۔');
        } else {
            $status = "default";
            return view('order.print', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance','tailor'));
        }
    }

    public function search(Request $req)
    {
        $search = $req->validate(['sub_search' => ['nullable', 'string', 'max:255']])['sub_search'] ?? '';
        $data = Customers::where('user_id', Auth::user()->businessOwnerId())
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone_number1', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
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
        $validated = $req->validate([
            'order_id' => ['required', 'integer'],
            'order_status' => ['required', 'in:new,start,complete'],
        ]);
        $obj = $this->ownedOrder($validated['order_id']);
        $obj->status = $validated['order_status'];
        $obj->save();
        return back();
    }

    public function totalOrder()
    {
        $monthly_orders = [];

        for ($month = 1; $month <= 12; $month++) {
            $orders = Order::where('userId', auth()->user()->businessOwnerId())
                ->whereMonth('created_at', $month)
                ->get();

            $ordersCount = $orders->count();
            $totalSuitQuantity = $orders->sum('suitQuantity');
            $totalpayment = $orders->sum('totalPayment');

            $newOrdersCount = $orders->where('status', 'assigned')->count();
            $processingOrdersCount = $orders->whereIn('status', ['cutting', 'stitching', 'trial'])->count();
            $completedOrdersCount = $orders->whereIn('status', ['ready', 'delivered'])->count();

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
        $validated = $request->validate(['rack_no' => ['nullable', 'string', 'max:255']]);
        $order = $this->ownedOrder($orderId);

        $order->rack_no = $validated['rack_no'] ?? null;
        $order->save();

        return response()->json(['message' => 'Rack number updated successfully'], 200);
    }

    public function orderCompleteNotify(Request $request)
    {
        Log::info('Order ID:', ['id' => $request->order_id]);

        $validated = $request->validate(['order_id' => ['required', 'integer']]);
        $order = Order::with('transactions')->where('userId', Auth::user()->businessOwnerId())->where('id', $validated['order_id'])->first();
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

    private function ownedOrder($id): Order
    {
        if (Auth::check()) {
            return Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($id);
        }

        abort_unless(session('tailor') === 'tailor' && session()->has('tailor_id'), 403);

        return Order::where('tailorId', session('tailor_id'))->findOrFail($id);
    }

    private function ownedCustomer($id): Customers
    {
        return Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function ownedTailor($id): Tailor
    {
        return Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
