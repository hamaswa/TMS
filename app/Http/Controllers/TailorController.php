<?php

namespace App\Http\Controllers;

use session;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Business;
use App\Models\Tailor;
use App\Models\Setting;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\TailorRecord;
use App\Models\Tailorsalary;
use App\Models\TailorSecurityDepositTransaction;
use App\Services\ProductionWorkforceService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


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
            ? Tailor::where('user_id', $business->owner_user_id)
                ->where('phone_number1', $credentials['contact'])
                ->limit(2)
                ->get()
            : collect();

        if ($matches->count() !== 1) {
            return redirect('tailor-login')
                ->withInput($req->only('shop_code', 'contact'))
                ->with('failed', 'دکان کا کوڈ، فون نمبر یا پاس ورڈ درست نہیں ہے۔');
        }

        $data = $matches->first();
        $storedPassword = (string) $data->password;
        $isHashed = password_get_info($storedPassword)['algoName'] !== 'unknown';
        $passwordMatches = $isHashed
            ? Hash::check($credentials['password'], $storedPassword)
            : hash_equals($storedPassword, $credentials['password']);

        if (! $passwordMatches) {
            return redirect('tailor-login')
                ->withInput($req->only('shop_code', 'contact'))
                ->with('failed', 'دکان کا کوڈ، فون نمبر یا پاس ورڈ درست نہیں ہے۔');
        }

        if (! $isHashed || Hash::needsRehash($storedPassword)) {
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
        $user = Auth::user()->loadMissing(['business', 'ownedBusiness']);
        $ownerId = $user->businessOwnerId();
        $weekStart = now()->startOfWeek()->startOfDay();
        $weekEnd = now()->endOfWeek()->endOfDay();

        $Tailors = Tailor::where('user_id', $ownerId)
            ->withCount('orders')
            ->with([
                'orders' => fn ($query) => $query
                    ->select('id', 'tailorId', 'tailor_price', 'suitQuantity', 'created_at')
                    ->whereBetween('created_at', [$weekStart, $weekEnd]),
                'tailorsalary:id,tailor_id,options_id,type,price',
            ])
            ->orderByDesc('id')
            ->get();

        $deletedTailors = Tailor::onlyTrashed()
            ->where('user_id', $ownerId)
            ->orderByDesc('deleted_at')
            ->get(['id', 'name', 'phone_number1', 'deleted_at']);

        $business = $user->business ?? $user->ownedBusiness;

        return view('tailor.list', compact('Tailors', 'deletedTailors', 'business', 'weekStart', 'weekEnd'));
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
                return redirect()->route('admin.Tailor.index')->withErrors($exception->errors());
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
            'initial_rate_label' => ['nullable', 'required_with:initial_rate_price', 'string', 'max:100'],
            'initial_rate_price' => ['nullable', 'required_with:initial_rate_label', 'numeric', 'min:0.01', 'max:9999999.99'],
            'security_deposit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'security_deposit_note' => ['nullable', 'string', 'max:500'],
        ], [
            'contact.unique' => 'اس دکان میں یہ فون نمبر پہلے سے کسی درزی کے نام پر موجود ہے۔',
            'initial_rate_label.required_with' => 'ابتدائی اجرت کے ساتھ سلائی کی قسم بھی لکھیں۔',
            'initial_rate_price.required_with' => 'سلائی کی قسم کے ساتھ فی سوٹ اجرت بھی لکھیں۔',
        ]);

        DB::transaction(function () use ($validated, $ownerId, $businessId, $entitlements) {
            if ($businessId) {
                $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($businessId);
                $entitlements->assertCanAddTailor($lockedBusiness);
            }

            $tailor = Tailor::create([
                'name' => $validated['name'],
                'user_id' => $ownerId,
                'phone_number1' => $validated['contact'],
                'password' => Hash::make($validated['password']),
                'security_deposit' => $validated['security_deposit'] ?? 0,
            ]);

            if ((float) ($validated['security_deposit'] ?? 0) > 0) {
                $tailor->securityDepositTransactions()->create([
                    'user_id' => $ownerId,
                    'transaction_type' => TailorSecurityDepositTransaction::TYPE_RECEIVED,
                    'amount' => $validated['security_deposit'],
                    'transaction_date' => now()->toDateString(),
                    'note' => $validated['security_deposit_note'] ?? 'درزی شامل کرتے وقت وصول شدہ سیکیورٹی رقم',
                ]);
            }

            if (! empty($validated['initial_rate_price'])) {
                $tailor->tailorsalary()->create([
                    'type' => $validated['initial_rate_label'],
                    'price' => $validated['initial_rate_price'],
                ]);
            }

            app(ProductionWorkforceService::class)->syncTailor($tailor->fresh());
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tailorData = Tailor::find($id);

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
        $obj = Tailor::find($id);
        $obj->name = $request->name;
        $obj->password = $request->password;
        $obj->phone_number1 = $request->contact;
        $obj->save();
        return redirect('admin/Tailor')->with('update', 'Tailor Data Update');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $obj = Tailor::where('user_id', $ownerId)->findOrFail($id);

        $obj->delete();

        return back()->with('delete', 'درزی کو حذف شدہ فہرست میں منتقل کر دیا گیا ہے۔ ضرورت پڑنے پر بحال کیا جا سکتا ہے۔');
    }

    /**
     * Restore a previously deleted tailor and all retained related records.
     */
    public function restore($id)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $tailor = Tailor::onlyTrashed()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $tailor->restore();

        return back()->with('restore', 'درزی اور اس کا محفوظ ریکارڈ کامیابی سے بحال کر دیا گیا ہے۔');
    }

    public function tailorRecord($id)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $detailedWorkflow = Business::tailoringStatusModeForOwner($ownerId)
            === Business::TAILORING_STATUS_DETAILED;
        $tailor = Tailor::where('user_id', $ownerId)->findOrFail($id);
        $data = [];
        $data['tailor-name'] = $tailor->name;
        $data['tailor-id'] = $tailor->id;
        $Tailor_records = Tailor::where('user_id', $ownerId)
            ->with(['orders' => fn ($query) => $query->with(['customers', 'rate.options'])->latest('created_at')])
            ->findOrFail($id);

        return view('tailor.tailor-record', compact('data', 'Tailor_records', 'detailedWorkflow'));
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
        $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $tailor->load(['securityDepositTransactions' => fn ($query) => $query
            ->latest('transaction_date')
            ->latest('id')]);

        $filterType = $request->input('filterType') === 'monthly' ? 'monthly' : 'weekly';
        $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endDate = Carbon::now()->endOfWeek(Carbon::THURSDAY)->endOfDay();

        if ($filterType === 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        }

        $result = Order::query()
            ->with('rate.options')
            ->where('tailorId', $tailor->id)
            ->where('userId', Auth::user()->businessOwnerId())
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->latest('created_at')
            ->get();

        $tailor_report = $result;
        $advanceCutQuery = Transaction::where('tailorId', $tailor->id)
            ->where('userId', Auth::user()->businessOwnerId())
            ->where('Order_type', 'Tailor_Advance_Cut')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $advanceCutAmount = (float) (clone $advanceCutQuery)->sum('remainingBalance');
        $transaction = $advanceCutQuery->latest('created_at')->first();

        $tailor_records = TailorRecord::where('tailor_id', $tailor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('created_at')
            ->get();

        $total_amount = $tailor_report->sum(fn ($order) => (float) $order->tailor_price * max(1, (int) $order->suitQuantity));

        return view('tailor.tailor-report', compact('tailor_report', 'result', 'total_amount', 'tailor', 'filterType', 'tailor_records', 'transaction', 'advanceCutAmount', 'startDate', 'endDate'));
    }



    public function addRecord(Request $request, $id)
    {
        $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $validated = $request->validate([
            'comment' => ['required', Rule::in(['advance', 'salary', 'chai'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($tailor, $validated) {
            TailorRecord::create([
                'tailor_id' => $tailor->id,
                'amount' => $validated['amount'],
                'comment' => $validated['comment'],
            ]);
        });

        return redirect()->route('admin.tailor-report', $tailor)->with('success', 'درزی کا لین دین محفوظ کر دیا گیا ہے۔');
    }

    public function addAdnvanceRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())
                ->lockForUpdate()
                ->findOrFail($id);
            $tailor->increment('advance', (float) $validated['amount']);
            TailorRecord::create([
                'tailor_id' => $tailor->id,
                'amount' => $validated['amount'],
                'comment' => 'main_advance',
            ]);
        });

        return redirect()->back()->with('insert', 'درزی کا مرکزی ایڈوانس محفوظ کر دیا گیا ہے۔');
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $id) {
            $ownerId = Auth::user()->businessOwnerId();
            $tailor = Tailor::where('user_id', $ownerId)->lockForUpdate()->findOrFail($id);
            $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
            $endDate = Carbon::now()->endOfWeek(Carbon::THURSDAY)->endOfDay();
            $weeklyAdvance = (float) TailorRecord::where('tailor_id', $tailor->id)
                ->where('comment', 'advance')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');
            $alreadyCovered = (float) Transaction::where('tailorId', $tailor->id)
                ->where('userId', $ownerId)
                ->where('Order_type', 'Tailor_Advance_Cut')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('remainingBalance');
            $maximumCut = min((float) $tailor->advance, max(0, $weeklyAdvance - $alreadyCovered));
            $amount = (float) $validated['amount'];

            if ($amount > $maximumCut) {
                throw ValidationException::withMessages([
                    'amount' => 'کٹوتی باقی ہفتہ وار یا مرکزی ایڈوانس سے زیادہ نہیں ہو سکتی۔',
                ]);
            }

            $tailor->decrement('advance', $amount);
            Transaction::create([
                'remainingBalance' => $amount,
                'recivedPayment' => max(0, (float) $validated['total'] - $amount),
                'Order_type' => 'Tailor_Advance_Cut',
                'tailorId' => $tailor->id,
                'userId' => $ownerId,
            ]);
        });

        return redirect()->back()->with('success', 'ہفتہ وار ایڈوانس مرکزی ایڈوانس سے کاٹ دیا گیا ہے۔');
    }


    public function tailorReportPrint($id, Request $request)
    {
        $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->first();
        $filterType = $request->input('filterType') === 'monthly' ? 'monthly' : 'weekly';
        $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endDate = Carbon::now()->endOfWeek(Carbon::THURSDAY)->endOfDay();

        if ($filterType === 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        }

        $tailor_records = TailorRecord::where('tailor_id', $tailor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('comment', ['advance', 'salary', 'chai'])
            ->latest('created_at')
            ->get();

        $tailor_report = $tailor->orders()
            ->with('rate.options')
            ->where('userId', Auth::user()->businessOwnerId())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('created_at')
            ->get();

        $advanceCutQuery = Transaction::where('tailorId', $tailor->id)
            ->where('userId', Auth::user()->businessOwnerId())
            ->where('Order_type', 'Tailor_Advance_Cut')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $advanceCutAmount = (float) (clone $advanceCutQuery)->sum('remainingBalance');
        $transaction = $advanceCutQuery->latest('created_at')->first();
        $total_amount = $tailor_report->sum(fn ($order) => $order->tailorAmountDue());

        return view('tailor.tailor-report-print', compact(
            'tailor_report', 'total_amount', 'tailor', 'setting', 'tailor_records',
            'transaction', 'advanceCutAmount', 'startDate', 'endDate', 'filterType'
        ));
    }


    public function tailorRates($id)
    {
        try {

            $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

            $tailor_rates = $tailor->tailorsalary;
            $types = Options::where('option_id', 1)
                ->where('user_id', Auth::user()->businessOwnerId())
                ->orderBy('Name')
                ->get();

            return view('tailor.tailor-rate', compact('tailor_rates', 'tailor', 'types'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function paymentReceived($id)
    {
        try {
            $user = Tailor::find($id);

            dd($user->transactions);
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
        if ($req->Date == "") {
            return back();
        }

        $tailor = Tailor::find($id);

        if (!$tailor) {
            abort(404); // Handle the case when the Tailor is not found
        }

        $user_id = $tailor->user_id;

        $dateRange = explode(" to ", $req->Date);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        // Fetch orders within the specified date range along with customer information
        $orders = DB::table('orders')
            ->select('orders.created_at as date', 'orders.totalPayment as t_payment', 'orders.advancePayment as advance_payment', 'orders.suitQuantity as suit', 'customers.name as c_name')
            ->leftJoin('customers', 'orders.customerId', '=', 'customers.id')
            ->where('orders.tailorId', $id)
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

            $rates = Tailorsalary::where("tailor_id", $tailor_id)->get();

            $html .= '<select class="form-control tailor-rate-select" name="tailor_price" required dir="rtl" aria-label="درزی کی فی سوٹ اجرت منتخب کریں">
            <option value="">درزی کی رقم منتخب کریں۔</option>';

            foreach ($rates as $index => $rate) {
                $rateLabel = $rate->options?->Name ?: $rate->type ?: 'عام سلائی';
                $value = e($rate->id . '-' . $rate->price);
                $label = e($rate->price . ' -- ' . $rateLabel);
                $selected = $index === 0 ? ' selected' : '';
                $html .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
            }

            $html .= '</select>';

            return $html;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function showSpecificRecord(Request $request, $id)
    {
        $ownerId = Auth::user()->businessOwnerId();
        Tailor::where('user_id', $ownerId)->findOrFail($id);

        $validated = $request->validate([
            'date_range' => ['required', 'string', 'max:50'],
        ]);
        $dateParts = preg_split('/\s*(?:to|تا)\s*/u', trim($validated['date_range']));

        if (count($dateParts) !== 2) {
            return response()->json(['message' => 'درست تاریخ کی حد منتخب کریں۔'], 422);
        }

        try {
            $startDate = Carbon::createFromFormat('Y-m-d', trim($dateParts[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', trim($dateParts[1]))->endOfDay();
        } catch (\Exception $e) {
            return response()->json(['message' => 'درست تاریخ منتخب کریں۔'], 422);
        }

        if ($startDate->greaterThan($endDate)) {
            return response()->json(['message' => 'ابتدائی تاریخ آخری تاریخ سے پہلے ہونی چاہیے۔'], 422);
        }

        $tailor_records = Order::with(['customers', 'rate.options'])
            ->where('tailorId', $id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('userId', $ownerId)
            ->latest('created_at')
            ->get();

        return response()->json([
            'tailors' => $tailor_records,
        ]);
    }
}
function pre_week()
{
    $previous_week = strtotime("-1 week +1 day");
    $start_week = strtotime("last saturday midnight", $previous_week);
    $end_week = strtotime("next friday", $start_week);
    $start = date("Y-m-d", $start_week);
    $end = date("Y-m-d", $end_week);
    $suit = count_suit($start, $end);
    $payment = sum_of_payment($start, $end);
    $arr = array();
    $arr[] = $suit;
    $arr[] = $payment;
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
        ->select('orders.suitQuantity as suit')
        ->where('orders.tailorId', $tailor_id)
        ->whereBetween('orders.created_at', [$start, $end])
        ->sum('totalPayment');
}
