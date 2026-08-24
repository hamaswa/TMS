<?php

namespace App\Http\Controllers;

use App\Models\rack;
use App\Models\Business;
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
use App\Services\CustomerLedgerService;
use App\Support\PaymentMethods;
use Illuminate\Support\Carbon;


class OrderController extends Controller
{
    public function __construct(
        private MeasurementService $measurements,
        private CustomerLedgerService $customerLedger
    )
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
        $measurementCustomer = $sub_customer ?: $customer;
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $savedMeasurementValues = $data->measurementValues->keyBy('source_key');
        $customerCustomValues = $measurementCustomer->measurementValues()
            ->whereIn('measurement_field_id', $measurementFields->pluck('id'))
            ->pluck('value', 'measurement_field_id');
        $data['design'] = Options::where('option_id', 1)->get();
        // $currentTailorRate = 1220;
        return view('order.edit', compact(
            'data', 'tailors', 'tailorRates', 'customerBalance', 'orderBalance',
            'recivedPayment', 'sub_customer', 'customer', 'measurementCustomer',
            'measurementFields', 'savedMeasurementValues', 'customerCustomValues'
        ));
    }

    public function update(Request $request, $id)
    {
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $measurementRules = [
            'system_measurements' => ['nullable', 'array'],
            'custom_measurements' => ['nullable', 'array'],
        ];
        foreach (MeasurementService::SYSTEM_FIELDS as $key => $meta) {
            $measurementRules['system_measurements.'.$key] = $meta['unit'] === 'inch'
                ? ['nullable', 'numeric', 'min:0']
                : ['nullable', 'string', 'max:500'];
        }
        foreach ($measurementFields as $field) {
            $fieldRules = ['nullable'];
            if ($field->field_type === 'number') {
                array_push($fieldRules, 'numeric', 'min:0');
            } elseif ($field->field_type === 'select') {
                $fieldRules[] = Rule::in($field->options ?? []);
            } else {
                array_push($fieldRules, 'string', 'max:500');
            }
            $measurementRules['custom_measurements.'.$field->id] = $fieldRules;
        }

        $validated = $request->validate(array_merge([
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
        ], $measurementRules), [
            'recivedPayment.lte' => 'وصول رقم کل قیمت سے زیادہ نہیں ہو سکتی۔',
        ], $this->measurements->attributes($measurementFields));

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
        if ($measurementChanged) {
            $this->ensureRequiredMeasurements($measurementCustomer, $order->measurementTemplate);
        }

        DB::transaction(function () use ($validated, $order, $rateId, $tailorPrice, $remainingBalance, $measurementChanged, $measurementCustomer, $measurementFields) {
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

            if ($measurementChanged) {
                $this->measurements->snapshotOrder($order, $measurementCustomer);
            } else {
                foreach (MeasurementService::SYSTEM_FIELDS as $key => $meta) {
                    $value = $validated['system_measurements'][$key] ?? null;
                    $sourceKey = 'system.'.$key;
                    if ($value === null || $value === '') {
                        $order->measurementValues()->where('source_key', $sourceKey)->delete();
                        continue;
                    }
                    $order->measurementValues()->updateOrCreate(
                        ['source_key' => $sourceKey],
                        [
                            'measurement_field_id' => null,
                            'label' => $meta['label'],
                            'value' => (string) $value,
                            'unit' => $meta['unit'],
                            'sort_order' => array_search($key, array_keys(MeasurementService::SYSTEM_FIELDS), true),
                        ]
                    );
                }
                foreach ($measurementFields as $field) {
                    $value = $validated['custom_measurements'][$field->id] ?? null;
                    $sourceKey = 'custom.'.$field->id;
                    if ($value === null || $value === '') {
                        $order->measurementValues()->where('source_key', $sourceKey)->delete();
                        continue;
                    }
                    $order->measurementValues()->updateOrCreate(
                        ['source_key' => $sourceKey],
                        [
                            'measurement_field_id' => $field->id,
                            'label' => $field->label,
                            'value' => (string) $value,
                            'unit' => $field->unit,
                            'sort_order' => 1000 + (int) $field->sort_order,
                        ]
                    );
                }
            }
            app(ProductionWorkforceService::class)->syncOrder($order->fresh());
        });

        return redirect('admin/Customers')->with('insert', 'آرڈر کامیابی سے اپ ڈیٹ کر دیا گیا ہے۔');
    }

    public function createOrder($id)
    {
        $customer = Customers::where('user_id', auth()->user()->businessOwnerId())
            ->findOrFail($id);
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

        // Keep the customer's serial stable even when other customers are added.
        $data['serialNumber'] = $customer->id;
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
            $detailedWorkflow = Business::tailoringStatusModeForOwner(Auth::user()->businessOwnerId())
                === Business::TAILORING_STATUS_DETAILED;
            $canViewBalances = Auth::user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES);
            $orderBalances = $canViewBalances
                ? $this->customerLedger->orderBalances(Auth::user()->businessOwnerId(), (int) $id)
                : collect();

            $data = [];
            $i = 1;
            foreach ($Orders as $order) {
                $remaining = $canViewBalances
                    ? max(0, (float) $orderBalances->get((int) $order->id, (float) $order->totalPayment))
                    : null;
                $paid = $canViewBalances ? max(0, (float) $order->totalPayment - $remaining) : null;
                $paymentStatus = ! $canViewBalances ? null : match (true) {
                    $remaining <= 0 => ['key' => 'paid', 'label' => 'ادا شدہ'],
                    $paid > 0 => ['key' => 'partial', 'label' => 'جزوی ادا شدہ'],
                    default => ['key' => 'unpaid', 'label' => 'غیر ادا شدہ'],
                };
                $currentStatus = (string) ($order->status ?: 'assigned');
                $button = $detailedWorkflow
                    ? (Order::STATUS_LABELS[$currentStatus] ?? ucfirst($currentStatus))
                    : match ($currentStatus) {
                        'ready' => 'تیار ہے',
                        'delivered' => 'تیار ہے',
                        default => 'کارخانے میں ہے',
                    };
                $btn = match ($currentStatus) {
                    'assigned', 'cutting', 'stitching', 'trial' => 'order-stage-workshop',
                    'ready' => 'order-stage-ready',
                    'delivered' => $detailedWorkflow ? 'order-stage-delivered' : 'order-stage-ready',
                    default => 'order-stage-workshop',
                };
                $data[] = [
                    'number' => $i++,
                    'totalPayment' => $order->totalPayment,
                    'paidAmount' => $paid,
                    'remainingAmount' => $remaining,
                    'paymentStatus' => $paymentStatus,
                    'canReceivePayment' => $canViewBalances && $remaining > 0,
                    'customerId' => (int) $id,
                    'created_at' => date('d-m-Y', strtotime($order->created_at)),
                    'returnDate' => date('d-m-Y', strtotime($order->returnDate)),
                    'suitQuantity' => $order->suitQuantity,
                    'tailorName' => $order->tailor_name,
                    'button' => $button,
                    'btnClass' => $btn,
                    'orderId' => $order->id,
                    'rack_no' => $order->rack_no,
                    'racks' => $racks,
                    'canMarkDelivered' => ! $detailedWorkflow && $currentStatus === 'ready',
                    'isDelivered' => ! $detailedWorkflow && $currentStatus === 'delivered',
                    'nextStatuses' => $detailedWorkflow
                        ? Order::nextStatusOptionsFor($currentStatus)
                        : match ($currentStatus) {
                            'delivered' => [],
                            default => [
                            ['value' => 'start', 'label' => 'کارخانے میں ہے'],
                            ['value' => 'complete', 'label' => 'تیار ہے'],
                            ],
                        },
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
        $printDocumentService = app(\App\Services\PrintDocumentService::class);
        $printConfig = $printDocumentService->make($setting, request(), 'tailor-order', $order->id);
        $trackingUrl = \Illuminate\Support\Facades\URL::signedRoute('orders.track', ['order' => $order->id]);
        $trackingQrSvg = $printDocumentService->qrSvg($trackingUrl, 180);

        return view('order.print', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance', 'orderBalance', 'tailor', 'printConfig', 'trackingUrl', 'trackingQrSvg'));
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
        $printDocumentService = app(\App\Services\PrintDocumentService::class);
        $printConfig = $printDocumentService->make($setting, request(), 'tailor-order-copy', $order->id);
        $trackingUrl = \Illuminate\Support\Facades\URL::signedRoute('orders.track', ['order' => $order->id]);
        $trackingQrSvg = $printDocumentService->qrSvg($trackingUrl, 180);

        return view('order.prints', compact('order', 'orderDetail', 'setting', 'status', 'latestBalance', 'previousBalance', 'orderBalance', 'tailor', 'printConfig', 'trackingUrl', 'trackingQrSvg'));
    }

    private function printBalanceSummary(Order $order): array
    {
        return $this->customerLedger->receiptSummary($order);
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

    public function totalOrder(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $detailedWorkflow = Business::tailoringStatusModeForOwner($ownerId) === Business::TAILORING_STATUS_DETAILED;
        $validated = $request->validate(['week' => ['nullable', 'date']]);
        $weekStart = Carbon::parse($validated['week'] ?? now())
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $orders = Order::where('userId', $ownerId)
            ->whereBetween('returnDate', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['customers:id,name,phone_number1', 'tailor:id,name'])
            ->orderBy('returnDate')
            ->orderBy('id')
            ->get();

        $ordersByDate = $orders->groupBy(
            fn (Order $order) => Carbon::parse($order->returnDate)->toDateString()
        );
        $weekDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $ordersByDate) {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'orders' => $ordersByDate->get($date->toDateString(), collect()),
            ];
        });
        $summary = [
            'orders' => $orders->count(),
            'suits' => (int) $orders->sum('suitQuantity'),
            'in_workshop' => $orders->whereNotIn('status', ['ready', 'delivered'])->count(),
            'ready' => $orders->whereIn('status', ['ready', 'delivered'])->count(),
        ];

        return view('All_Total.order', compact('weekStart', 'weekEnd', 'weekDays', 'summary', 'detailedWorkflow'));
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
