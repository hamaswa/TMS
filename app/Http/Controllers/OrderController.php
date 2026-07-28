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
use App\Models\MeasurementTemplate;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
use App\Events\CompleteOrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderCompleteNotification;
use App\Services\MeasurementService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\ProductionWorkforceService;
use App\Support\PaymentMethods;


class OrderController extends Controller
{
    public function __construct(private MeasurementService $measurements)
    {
    }

    public function edit($id)
    {

        $data = $this->ownedOrder($id)->load('measurementValues');
        $sub_customer = Customers::where('user_id', Auth::user()->businessOwnerId())->find($data->sub_customer);
        $customer = Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($data->customerId);
        $tailors = Tailor::where('user_id', Auth::user()->businessOwnerId())->get();
        $customerBalance = Auth::user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES)
            ? Transaction::where('userId', Auth::user()->businessOwnerId())->where("customerId", $data->customerId)->sum('remainingBalance')
            : null;
        $orderTransaction = Transaction::where('userId', Auth::user()->businessOwnerId())
            ->where('customerId', $data->customerId)->where('orderId', $data->id)->first();
        $recivedPayment = $orderTransaction?->recivedPayment ?? 0;
        $orderBalance = $orderTransaction?->remainingBalance ?? max(0, (float) $data->totalPayment - (float) $recivedPayment);
        $tailorRates = Tailorsalary::with('options')->where('tailor_id', $data->tailorId)->get();
        $data['design'] = Options::where('option_id', 1)->get();
        // $currentTailorRate = 1220;
        return view('order.edit', compact('data', 'tailors', 'tailorRates', 'customerBalance', 'orderBalance', 'recivedPayment', 'sub_customer', 'customer'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sub_id' => ['nullable', 'integer'],
            'customerId' => ['required', 'integer'],
            'suitQuantity' => ['required', 'integer', 'min:1'],
            'totalPayment' => ['required', 'numeric', 'min:0'],
            'recivedPayment' => ['required', 'numeric', 'min:0', 'lte:totalPayment'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => [
                Rule::requiredIf(fn () => PaymentMethods::requiresReference($request->input('payment_method'))),
                'nullable',
                'string',
                'max:255',
            ],
            'paid_on' => ['nullable', 'date'],
            'tailorId' => ['required', 'integer'],
            'tailor_price' => ['required', 'regex:/^\d+-.+$/', 'max:255'],
            'returnDate' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'recivedPayment.lte' => 'وصول رقم کل قیمت سے زیادہ نہیں ہو سکتی۔',
        ]);

        $order = $this->ownedOrder($id);
        $this->ownedCustomer($validated['customerId']);
        $this->ownedTailor($validated['tailorId']);
        if (!empty($validated['sub_id'])) {
            $this->ownedCustomer($validated['sub_id']);
        }

        //new change
        [$rateId, $tailorPrice] = explode('-', $validated['tailor_price'], 2);
        Tailorsalary::where('tailor_id', $validated['tailorId'])->findOrFail($rateId);
        $remainingBalance = max(0, $validated['totalPayment'] - $validated['recivedPayment']);

        $measurementCustomerId = $validated['sub_id'] ?? $validated['customerId'];
        $measurementChanged = (int) $order->sub_customer !== (int) $measurementCustomerId;
        $measurementCustomer = $this->ownedCustomer($measurementCustomerId);
        if ($measurementChanged || ! $order->measurementValues()->exists()) {
            $this->ensureRequiredMeasurements($measurementCustomer, $order->measurementTemplate);
        }

        DB::transaction(function () use ($validated, $order, $rateId, $tailorPrice, $remainingBalance, $measurementChanged, $measurementCustomer) {
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

            if ($measurementChanged || ! $order->measurementValues()->exists()) {
                $this->measurements->snapshotOrder($order, $measurementCustomer);
            }
            app(ProductionWorkforceService::class)->syncOrder($order->fresh());
        });

        return redirect('admin/Customers')->with('insert', 'آرڈر کامیابی سے اپ ڈیٹ کر دیا گیا ہے۔');
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
        $data['tailors'] = Tailor::with('tailorsalary')
            ->where('user_id', Auth::user()->businessOwnerId())->get();
        $data['hasReadyTailor'] = $data['tailors']->contains(
            fn (Tailor $tailor) => $tailor->tailorsalary->isNotEmpty()
        );
        $data['childData'] = Customers::where('parent_id', $id)->get();
        $data['design'] = Options::where('option_id', 1)->where('user_id', auth()->user()->businessOwnerId())->get();
        $data['measurementTemplates'] = MeasurementTemplate::where('user_id', auth()->user()->businessOwnerId())
            ->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $data['measurementTemplateId'] = $customer->measurement_template_id
            ?: $data['measurementTemplates']->firstWhere('is_default', true)?->id;

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
            'recivedPayment' => ['required', 'numeric', 'min:0', 'lte:totalPayment'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'returnDate' => ['required', 'date'],
            'design' => ['nullable', 'string', 'max:255'],
            'designPrice' => ['nullable', 'numeric', 'min:0'],
            'tailorId' => ['required', 'integer'],
            'tailor_price' => ['required', 'regex:/^\d+-.+$/', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'serail' => ['nullable', 'string', 'max:255'],
            'measurement_template_id' => ['nullable', 'integer', Rule::exists('measurement_templates', 'id')
                ->where('user_id', Auth::user()->businessOwnerId())->where('is_active', true)],
        ], [
            'recivedPayment.lte' => 'وصول رقم کل قیمت سے زیادہ نہیں ہو سکتی۔',
        ]);

        $this->ownedCustomer($validated['customerId']);
        $tailor = $this->ownedTailor($validated['tailorId']);
        if (!empty($validated['sub_id'])) {
            $this->ownedCustomer($validated['sub_id']);
        }

        [$rateId, $tailorPrice] = explode('-', $validated['tailor_price'], 2);
        Tailorsalary::where('tailor_id', $tailor->id)->findOrFail($rateId);
        $remainingBalance = round((float) $validated['totalPayment'] - (float) $validated['recivedPayment'], 2);
        $designParts = explode('-', $validated['design'] ?? '', 2);
        $subCustomerId = $validated['sub_id'] ?? $validated['customerId'];

        $measurementCustomer = $this->ownedCustomer($subCustomerId);
        $measurementTemplate = ! empty($validated['measurement_template_id'])
            ? MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['measurement_template_id'])
            : ($measurementCustomer->measurementTemplate
                ?: MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->first());
        $this->ensureRequiredMeasurements($measurementCustomer, $measurementTemplate);
        [$obj, $transaction] = DB::transaction(function () use ($validated, $rateId, $tailorPrice, $remainingBalance, $designParts, $subCustomerId, $measurementCustomer, $measurementTemplate) {
            $obj = Order::create([
                'customerId' => $validated['customerId'],
                'sub_customer' => $subCustomerId,
                'measurement_template_id' => $measurementTemplate?->id,
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
                'remainingBalance' => $remainingBalance,
                'orderId' => $obj->id,
                'customerId' => $validated['customerId'],
                'userId' => Auth::user()->businessOwnerId(),
                'Order_type' => 'Tailor',
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
            ]);

            $this->measurements->snapshotOrder($obj, $measurementCustomer, $measurementTemplate);
            app(ProductionWorkforceService::class)->syncOrder($obj);

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
                    'racks' => $racks,
                    'nextStatuses' => Order::nextStatusOptionsFor((string) $order->status),
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
        $orderDetail = $order->load(['customers', 'measurementValues']);
        
        // dd($orderDetail);

        [$latestBalance, $previousBalance, $orderBalance] = $this->printBalanceSummary($order);

        $setting = Setting::ensureDefaultFor(Auth::user());
        $status = "default";
        $printConfig = app(\App\Services\PrintDocumentService::class)
            ->make($setting, request(), 'tailor-order', $order->id);

        return view('order.print', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance', 'orderBalance', 'tailor', 'printConfig'));
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
        $orderDetail = $order->load(['customers', 'measurementValues']);
        
        [$latestBalance, $previousBalance, $orderBalance] = $this->printBalanceSummary($order);

        $setting = Setting::ensureDefaultFor(Auth::user());
        $status = "default";
        $printConfig = app(\App\Services\PrintDocumentService::class)
            ->make($setting, request(), 'tailor-order-copy', $order->id);

        return view('order.prints', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance', 'orderBalance', 'tailor', 'printConfig'));
    }

    private function printBalanceSummary(Order $order): array
    {
        $ownerId = Auth::user()->businessOwnerId();
        $orderTransaction = Transaction::where('userId', $ownerId)
            ->where('customerId', $order->customerId)
            ->where('orderId', $order->id)
            ->where('Order_type', 'Tailor')
            ->orderBy('id')
            ->first();

        $orderBalance = $orderTransaction
            ? (float) $orderTransaction->remainingBalance
            : max(0, (float) $order->totalPayment - (float) ($order->transactions()->sum('recivedPayment')));
        $previousBalance = $orderTransaction
            ? (float) Transaction::where('userId', $ownerId)
                ->where('customerId', $order->customerId)
                ->where('id', '<', $orderTransaction->id)
                ->sum('remainingBalance')
            : 0.0;

        return [
            max(0, round($previousBalance + $orderBalance, 2)),
            round($previousBalance, 2),
            max(0, round($orderBalance, 2)),
        ];
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

    private function ensureRequiredMeasurements(
        Customers $customer,
        ?MeasurementTemplate $template,
    ): void {
        $missing = $this->measurements->missingRequiredMeasurements(
            $customer,
            Auth::user()->businessOwnerId(),
            $template,
        );
        if ($missing->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'measurement_template_id' => 'آرڈر بنانے سے پہلے یہ ضروری پیمائش مکمل کریں: '.$missing->implode('، '),
        ]);
    }
}
