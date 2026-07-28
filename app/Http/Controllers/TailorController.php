<?php

namespace App\Http\Controllers;

use session;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\Business;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\TailorRecord;
use App\Models\TailorSecurityDepositTransaction;
use App\Models\Tailorsalary;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\ProductionWorkforceService;
use App\Services\SubscriptionEntitlementService;


class TailorController extends Controller
{
    public function tailor_login()
    {
        return view('tailor-login');
    }

    public function login(Request $req)
    {
        $req->session()->forget(['tailor-login-success', 'tailor', 'tailor_id']);
        $credentials = $req->validate([
            'shop_code' => ['required', 'string', 'max:30'],
            'contact' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'shop_code.required' => 'دکان کا کوڈ درج کریں۔',
            'contact.required' => 'فون نمبر درج کریں۔',
            'password.required' => 'پاس ورڈ درج کریں۔',
        ]);

        $business = Business::where('shop_code', strtoupper(trim($credentials['shop_code'])))
            ->where('status', Business::STATUS_ACTIVE)
            ->where('tailoring_enabled', true)
            ->first();
        if ($business && (! $business->hasActiveSubscriptionAccess()
            || ! $business->subscriptionAllowsFeature('allow_tailoring'))) {
            return back()->with('failed', 'دکان کی سبسکرپشن فعال نہیں ہے۔ مالک سے رابطہ کریں۔');
        }
        $matches = $business
            ? Tailor::where('user_id', $business->owner_user_id)->where('phone_number1', $credentials['contact'])->limit(2)->get()
            : collect();
        if ($matches->count() !== 1) {
            return redirect('tailor-login')
                ->withInput($req->only('shop_code', 'contact'))
                ->with('failed', 'دکان کا کوڈ، فون نمبر یا پاس ورڈ درست نہیں ہے۔');
        }

        $data = $matches->first();
        $storedPassword = (string) ($data?->password ?? '');
        $isHashed = password_get_info($storedPassword)['algoName'] !== 'unknown';
        $passwordMatches = $data && ($isHashed
            ? Hash::check($credentials['password'], $storedPassword)
            : hash_equals($storedPassword, $credentials['password']));

        if (!$passwordMatches) {
            return redirect('tailor-login')
                ->withInput($req->only('shop_code', 'contact'))
                ->with('failed', 'دکان کا کوڈ، فون نمبر یا پاس ورڈ درست نہیں ہے۔');
        }

        if (!$isHashed || Hash::needsRehash($storedPassword)) {
            $data->forceFill(['password' => Hash::make($credentials['password'])])->save();
        }

        Auth::logout();
        $req->session()->regenerate();
        session()->put('tailor-login-success', $data->name);
        session()->put('tailor', 'tailor');
        session()->put('tailor_id', $data->id);

        return redirect('tailor/tailor-dashboard');
    }
    public function tailor_dashboard()
    {
        $tailorId = (int) session()->get('tailor_id');
        $monthOrders = Order::where('tailorId', $tailorId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();
        $suits = $monthOrders->sum(fn (Order $order) => max(1, (int) $order->suitQuantity));
        $earnings = $monthOrders->sum(fn (Order $order) => $order->tailorAmountDue());
        $paid = (float) $monthOrders->sum('tailor_paid_amount');
        $outstanding = max(0, $earnings - $paid);
        $activeJobs = Order::where('tailorId', $tailorId)
            ->where('status', '!=', 'delivered')
            ->count();

        return view('tailor-dashboard.tailor-card', compact(
            'suits',
            'earnings',
            'paid',
            'outstanding',
            'activeJobs',
        ));
    }

    public function tailor_order_list()
    {
        $t_id = session()->get('tailor_id');
        $data = [];
        $tailor = Tailor::find($t_id);
        $data['tailor-name'] = $tailor->name;
        $data['advance'] = $tailor->advance;
        $data['tailor-id'] = $tailor->id;
        $Tailor_records = Tailor::with('orders.customers')->find($t_id);
        // dd($Tailor_records);
        return view('tailor-dashboard.index', compact('Tailor_records', 'data'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Tailors = Tailor::where('user_id', Auth::user()->businessOwnerId())->orderBy('id', 'DESC')->get();
        $business = Auth::user()->business;

        return view('tailor.list', compact('Tailors', 'business'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(SubscriptionEntitlementService $entitlements)
    {
        $business = Auth::user()->business;
        if ($business) {
            try {
                $entitlements->assertCanAddTailor($business);
            } catch (ValidationException $exception) {
                return redirect()->route('admin.Tailor.index')
                    ->withErrors($exception->errors());
            }
        }

        return view('tailor.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SubscriptionEntitlementService $entitlements)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $businessId = Auth::user()->business_id;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:50', Rule::unique('tailors', 'phone_number1')->where('user_id', $ownerId)],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'tailor_rates' => ['nullable', 'string'],
            'initial_rate_label' => ['nullable', 'required_with:initial_rate_price', 'string', 'max:100'],
            'initial_rate_price' => ['nullable', 'required_with:initial_rate_label', 'numeric', 'min:0.01', 'max:9999999.99'],
            'security_deposit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'security_deposit_note' => ['nullable', 'string', 'max:500'],
        ], [
            'contact.unique' => 'اس دکان میں یہ فون نمبر پہلے سے کسی درزی کے نام پر موجود ہے۔',
        ]);

        DB::transaction(function () use ($validated, $ownerId, $businessId, $entitlements) {
            if ($businessId) {
                $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($businessId);
                $entitlements->assertCanAddTailor($lockedBusiness);
            }
            $obj = Tailor::create([
                'name' => $validated['name'],
                'user_id' => $ownerId,
                'phone_number1' => $validated['contact'],
                'password' => Hash::make($validated['password']),
                'security_deposit' => $validated['security_deposit'] ?? 0,
            ]);

            if ((float) ($validated['security_deposit'] ?? 0) > 0) {
                $obj->securityDepositTransactions()->create([
                    'user_id' => $ownerId,
                    'transaction_type' => TailorSecurityDepositTransaction::TYPE_RECEIVED,
                    'amount' => $validated['security_deposit'],
                    'transaction_date' => now()->toDateString(),
                    'note' => $validated['security_deposit_note'] ?? 'درزی شامل کرتے وقت وصول شدہ سیکیورٹی رقم',
                ]);
            }

            if (!empty($validated['tailor_rates'])) {
                foreach (explode(',', $validated['tailor_rates']) as $rate) {
                    $obj->tailorsalary()->create(['price' => trim($rate)]);
                }
            }

            if (! empty($validated['initial_rate_price'])) {
                $obj->tailorsalary()->create([
                    'type' => $validated['initial_rate_label'],
                    'price' => $validated['initial_rate_price'],
                ]);
            }

            app(ProductionWorkforceService::class)->syncTailor($obj->fresh());
        });

        return redirect('admin/Tailor')->with('insert', 'نیا درزی کامیابی سے شامل کر دیا گیا ہے۔');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->ownedTailor($id);

        return redirect()->route('admin.tailor-report', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tailorData = $this->ownedTailor($id);

        return view('tailor.edit', compact('tailorData'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $obj = $this->ownedTailor($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:50', Rule::unique('tailors', 'phone_number1')
                ->where('user_id', Auth::user()->businessOwnerId())->ignore($obj->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
        ], [
            'contact.unique' => 'اس دکان میں یہ فون نمبر پہلے سے کسی درزی کے نام پر موجود ہے۔',
        ]);

        $obj->name = $validated['name'];
        $obj->phone_number1 = $validated['contact'];
        if (!empty($validated['password'])) {
            $obj->password = Hash::make($validated['password']);
        }
        $obj->save();
        app(ProductionWorkforceService::class)->syncTailor($obj->fresh());
        return redirect('admin/Tailor')->with('update', 'درزی کی معلومات محفوظ کر دی گئی ہیں۔');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->ownedTailor($id)->delete();
        return back()->with('delete', 'درزی کا ریکارڈ حذف کر دیا گیا ہے۔');
    }

    public function tailorRecord($id)
    {
        $data = [];
        $tailor = $this->tailorForCurrentActor($id);
        $data['tailor-name'] = $tailor->name;
        $data['tailor-id'] = $tailor->id;
        $Tailor_records = Tailor::with(['orders' => function ($query) {
            $query->orderBy('created_at', 'desc');  // Order by 'created_at' in descending order
        }, 'orders.customers'])->where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        // dd($Tailor_records);
        return view('tailor.tailor-record', compact('data', 'Tailor_records'));
    }

    //     public function tailorReport($id)
    // {
    //     $tailor = Tailor::find($id);

    //     $details = [];

    //              $tailor_report = $tailor->orders()->whereBetween(
    //             'created_at',
    //             [Carbon::today()->subDays(7)->endOfDay(), Carbon::today()->endOfDay()]
    //         )->get();

    //         $tailor_records = TailorRecord::where('tailor_id', $id)->get();
    //         dd($tailor_records);
    //         $total_amount = $tailor->orders()->sum('tailor_price');

    //          return view('tailor.tailor-report', compact('tailor_report', 'total_amount', 'tailor', "tailor_records"));
    // }


    public function tailorReport($id, Request $request)
    {
        $tailor = $this->ownedTailor($id);
        $tailor->load(['securityDepositTransactions' => fn ($query) => $query
            ->latest('transaction_date')
            ->latest('id')]);

        $filterType = $request->validate([
            'filterType' => ['nullable', 'in:weekly,monthly'],
        ])['filterType'] ?? 'weekly';
        $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endDate = Carbon::now()->endOfWeek(Carbon::THURSDAY)->endOfDay();
        // $startDate = Carbon::now()->startOfWeek()->startOfDay();
        // $endDate = Carbon::now()->endOfDay();

        if ($filterType == 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
        }

        $totalReceivedExpression = $filterType === 'monthly'
            ? 'SUM(tailor_records.amount) as total_received'
            : 'MAX(tailor_records.amount) as total_received';

        $result = DB::table('orders')
            ->leftJoin('tailor_records', 'orders.tailorId', '=', 'tailor_records.tailor_id')
            ->select(
                DB::raw($totalReceivedExpression),
                'tailor_records.comment as comments',
                'orders.suitQuantity as quantity',
                'orders.suitNum as suitNum',
                'orders.design as design',
                'orders.designPrice as designPrice',
                DB::raw('SUM(orders.tailor_price) as totalPayment'),
                'orders.created_at'
            )
            ->where('orders.tailorId', $id)
            ->where('orders.userId', $tailor->user_id)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy(
                'orders.created_at',
                'tailor_records.amount',
                'tailor_records.comment',
                'orders.suitQuantity',
                'orders.suitNum',
                'orders.design',
                'orders.designPrice'
            )
            ->get();

        // dd($result);


        $tailor_report = $tailor->orders()->whereBetween('created_at', [$startDate, $endDate])->get();
        $transaction = Transaction::where('tailorId', $id)->whereBetween('created_at', [$startDate, $endDate])->first();

        $tailor_records = TailorRecord::where('tailor_id', $id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $total_amount = $tailor->orders()->sum('tailor_price');


        return view('tailor.tailor-report', compact('tailor_report', 'result', 'total_amount', 'tailor', 'filterType', 'tailor_records', 'transaction'));
    }



    public function addRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['required', Rule::in(['advance', 'salary', 'chai'])],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            if ($validated['comment'] === 'advance') {
                $tailor->increment('advance', (float) $validated['amount']);
            }
            TailorRecord::create([
                'tailor_id' => $tailor->id,
                'amount' => $validated['amount'],
                'comment' => $validated['comment'],
            ]);
        });

        return redirect(url('admin/tailor-report', $id))->with('success', 'درزی کا لین دین محفوظ کر دیا گیا ہے۔');
    }

    public function addAdnvanceRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            $tailor->increment('advance', (float) $validated['amount']);
            TailorRecord::create([
                'tailor_id' => $tailor->id,
                'amount' => $validated['amount'],
                'comment' => 'advance',
            ]);
        });

        return redirect()->back()->with('insert', 'درزی کا ایڈوانس محفوظ کر دیا گیا ہے۔');
    }

    public function updateSecurityDeposit(Request $request, $id)
    {
        $validated = $request->validate([
            'transaction_type' => ['required', Rule::in([
                TailorSecurityDepositTransaction::TYPE_RECEIVED,
                TailorSecurityDepositTransaction::TYPE_REFUNDED,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())
                ->lockForUpdate()
                ->findOrFail($id);
            $currentDeposit = (float) $tailor->security_deposit;
            $amount = (float) $validated['amount'];

            if ($validated['transaction_type'] === TailorSecurityDepositTransaction::TYPE_REFUNDED
                && $amount > $currentDeposit) {
                throw ValidationException::withMessages([
                    'amount' => 'واپس کی جانے والی رقم موجودہ سیکیورٹی ڈپازٹ سے زیادہ نہیں ہو سکتی۔',
                ]);
            }

            $newDeposit = $validated['transaction_type'] === TailorSecurityDepositTransaction::TYPE_RECEIVED
                ? $currentDeposit + $amount
                : $currentDeposit - $amount;

            $tailor->update(['security_deposit' => $newDeposit]);
            $tailor->securityDepositTransactions()->create([
                'user_id' => Auth::user()->businessOwnerId(),
                'transaction_type' => $validated['transaction_type'],
                'amount' => $amount,
                'transaction_date' => now()->toDateString(),
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()->back()->with('insert', 'درزی کی سیکیورٹی ڈپازٹ کا ریکارڈ محفوظ کر دیا گیا ہے۔');
    }

    public function cutAdvanceRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $tailorRecord = Tailor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            $advance = (float) $tailorRecord->advance;
            $amount = min((float) $validated['amount'], $advance);
            $tailorRecord->update([
                'advance' => $advance - $amount
            ]);

            $total = (float) $validated['total'];

            Transaction::create([
                'remainingBalance' => $amount,
                'recivedPayment' => max(0, $total - $amount),
                'Order_type' => 'Tailor_Advance_Cut',
                'tailorId' => $tailorRecord->id,
                'userId' => Auth::user()->businessOwnerId()
            ]);
        });

        return redirect()->back();
    }


    public function tailorReportPrint($id)
    {
        try {
            $tailor = $this->ownedTailor($id);

            $user_id = $tailor->user_id;

            $setting = Setting::where('user_id', $user_id)->firstOrFail();

            $details = [];

            $startDate = Carbon::today()->startOfWeek(Carbon::SATURDAY)->subDays(1)->endOfDay();
            $endDate = Carbon::today()->endOfDay();
            // dd($startDate,$endDate);
            $tailor_records = TailorRecord::where('tailor_id', $id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('comment', ['advance', 'salary', 'chai'])
                ->get();

            $tailor_report = $tailor->orders()->whereBetween(
                'created_at',
                [$startDate, $endDate]
            )->get();

            $transaction = Transaction::where('tailorId', $id)->whereBetween('created_at', [$startDate, $endDate])->first();

            $tailor_record = null;
            if ($tailor_report->isNotEmpty()) {
                $tailor_record = $tailor_records
                    ->where('order_id', $tailor_report->first()->id)
                    ->first();
            }

            // dd($tailor_report, $tailor_records, $tailor_record);

            // $tailor_record = $tailor_records
            //     ->where('order_id', $tailor_report->first()->id)
            //     ->first();
            //     dd($tailor_records);

            $total_amount = $tailor->orders()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('tailor_price');

            // dd($total_amount);
            $printConfig = app(\App\Services\PrintDocumentService::class)
                ->make($setting, request(), 'worker-weekly-report', $tailor->id);

            return view('tailor.tailor-report-print', compact('tailor_report', 'total_amount', 'tailor', 'setting', 'tailor_record', 'tailor_records', 'transaction', 'printConfig'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function tailorRates($id)
    {
        try {

            $tailor = $this->ownedTailor($id);

            $tailor_rates = $tailor->tailorsalary;

            return view('tailor.tailor-rate', compact('tailor_rates', 'tailor'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    // admin method

    // tailor method
    // public function tailor_weekly(Request $req, $id)
    // {
    //     if ($req->Date == "") {
    //         return back();
    //     }
    //     $tailor = Tailor::find($id);
    //     $user_id = $tailor->user_id;

    //     $dateRange = explode(" to ", $req->Date);
    //     $startDate = $dateRange[0];
    //     $endDate = $dateRange[1];

    //     // $orders= Tailor::with('orders.customers')->find($id);
    //     $orders = DB::table('orders')
    //         ->select('orders.created_at as date', 'orders.totalPayment as t_payment', 'orders.suitQuantity as suit', 'customers.name as c_name')
    //         ->leftJoin('customers', 'orders.customerId', '=', 'customers.id')
    //         ->where('orders.tailorId', $id)
    //         ->whereBetween('orders.created_at', [$startDate, $endDate])
    //         ->get();
    //     $setting  = DB::table('settings')->where('user_id', $user_id)->first();
    //     // dd($orders);
    //     return view('order.weakly_print', \compact('orders', 'setting', 'tailor'));
    // }

    public function tailor_weekly(Request $req, $id)
    {
        $validated = $req->validate([
            'Date' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2} to \d{4}-\d{2}-\d{2}$/'],
        ]);

        $tailor = $this->tailorForCurrentActor($id);

        $user_id = $tailor->user_id;

        $dateRange = explode(" to ", $validated['Date']);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        // Fetch orders within the specified date range along with customer information
        $orders = DB::table('orders')
            ->select('orders.created_at as date', 'orders.totalPayment as t_payment', 'orders.advancePayment as advance_payment', 'orders.suitQuantity as suit', 'customers.name as c_name')
            ->leftJoin('customers', 'orders.customerId', '=', 'customers.id')
            ->where('orders.tailorId', $id)
            ->where('orders.userId', $tailor->user_id)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->get();

        // Calculate total money earned by the tailor
        $totalEarnings = $orders->sum('t_payment');

        $totalAdvance = $orders->sum('advance_payment');

        // Insert a new record into the tailor_records table
        TailorRecord::create([
            'tailor_id' => $id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_earnings' => $totalEarnings,
            'total_advance' => $totalAdvance,
        ]);

        // Fetch settings based on user_id
        $setting = DB::table('settings')->where('user_id', $user_id)->first();

        // Return the view with the fetched data
        return view('order.weakly_print', compact('orders', 'setting', 'tailor', 'totalEarnings', 'totalAdvance'));
    }

    public function logout()
    {
        session()->forget('tailor-login-success');
        session()->forget('tailor');
        session()->forget('tailor_id');
        return redirect('tailor-login');
    }

    public function tailorSalary($tailor_id)
    {
        try {
            $html = '';

            $this->ownedTailor($tailor_id);
            $rates = Tailorsalary::where("tailor_id", $tailor_id)->get();
            $autoSelect = $rates->count() === 1;

            $html .= '<select class="form-control" name="tailor_price" required dir="rtl">
            <option value="">درزی کی رقم منتخب کریں۔</option>';

            foreach ($rates as $rate) {
                $label = $rate->options?->Name ?: $rate->type ?: 'سلائی';
                $selected = $autoSelect ? ' selected' : '';
                $html .= '<option value="' . $rate->id . '-' . $rate->price . '"' . $selected . '>' . $rate->price . ' -- ' . e($label) . '</option>';
            }

            $html .= '</select>';

            return $html;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function showSpecificRecord(Request $request, $id)
    {
        $this->ownedTailor($id);
        // Get date range from request
        $data_range = $request->input('date_range');

        // Check if date range is valid and contains 'to'
        if (strpos($data_range, 'to ') === false) {
            return response()->json(['error' => 'Invalid date range format.'], 400);
        }

        $date_parts = explode('to ', $data_range);

        // Check if the explosion resulted in exactly two parts
        if (count($date_parts) !== 2) {
            return response()->json(['error' => 'Invalid date range format.'], 400);
        }

        // Parse start and end dates using Carbon
        try {
            $start_date = Carbon::createFromFormat('Y-m-d', trim($date_parts[0]))->startOfDay(); // Start of the day
            $end_date = Carbon::createFromFormat('Y-m-d', trim($date_parts[1]))->endOfDay(); // End of the day
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format.'], 400);
        }

        // Query to fetch tailor records within the date range
        $tailor_records = Order::where('tailorId', $id) // Filter by Tailor id
            ->whereBetween('created_at', [$start_date, $end_date])
            ->where('userId', Auth::user()->businessOwnerId())
            ->get();

        // Return the data as JSON
        return response()->json([
            'tailors' => $tailor_records,
        ]);
    }

    private function ownedTailor($id): Tailor
    {
        return Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function tailorForCurrentActor($id): Tailor
    {
        if (Auth::check()) {
            return $this->ownedTailor($id);
        }

        abort_unless((int) session('tailor_id') === (int) $id, 403);

        return Tailor::findOrFail($id);
    }
}
function tailor_pre_week()
{
    $start = Carbon::now()
        ->startOfWeek(Carbon::SATURDAY)
        ->subWeek()
        ->startOfDay();
    $end = $start->copy()->addDays(6)->endOfDay();
    $suit = count_suit($start, $end);
    $payment = sum_of_payment($start, $end);
    $paid = sum_of_paid_amount($start, $end);
    $arr = array();
    $arr[] = $suit;
    $arr[] = $payment;
    $arr[] = $paid;
    return $arr;
};

function count_suit($start, $end)
{
    $tailor_id = session()->get('tailor_id');
    return DB::table('orders')
        ->select('orders.suitQuantity as suit')
        ->where('orders.tailorId', $tailor_id)
        ->whereBetween('orders.created_at', [$start, $end])
        ->sum('suitQuantity');
}

function sum_of_payment($start, $end)
{
    $tailor_id = session()->get('tailor_id');
    return DB::table('orders')
        ->where('orders.tailorId', $tailor_id)
        ->whereBetween('orders.created_at', [$start, $end])
        ->sum(DB::raw('COALESCE(orders.suitQuantity, 1) * COALESCE(orders.tailor_price, 0)'));
}

function sum_of_paid_amount($start, $end)
{
    $tailor_id = session()->get('tailor_id');
    return DB::table('orders')
        ->where('orders.tailorId', $tailor_id)
        ->whereBetween('orders.created_at', [$start, $end])
        ->sum('tailor_paid_amount');
}
