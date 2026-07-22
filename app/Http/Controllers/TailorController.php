<?php

namespace App\Http\Controllers;

use session;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\TailorRecord;
use App\Models\Tailorsalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class TailorController extends Controller
{
    public function tailor_login()
    {
        return view('tailor-login');
    }

    public function login(Request $req)
    {
        $password = $req->password;
        $contact = $req->contact;
        $data = Tailor::where('password', $password)->where('phone_number1', $contact)->first();
        if (!$data) {
            return redirect('tailor-login')->with('failed', 'Credentials Not Match!');
        } else {
            Auth::logout();
            session()->put('tailor-login-success', $data->name);
            session()->put('tailor', 'tailor');
            session()->put('tailor_id', $data->id);
            return redirect('tailor/tailor-dashboard');
        }
    }
    public function tailor_dashboard()
    {

        $val = pre_week();
        $suits = $val[0];
        $payments = $val[1];
        return view('tailor-dashboard.tailor-card', compact('suits', 'payments'));
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
        $Tailors = Tailor::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
        return view('tailor.list', compact('Tailors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tailor.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $obj = new Tailor;
        $obj->name = $request->name;
        $obj->user_id = Auth::user()->id;
        $obj->phone_number1 = $request->contact;
        $obj->password = $request->password;
        $obj->save();

        if ($request->tailor_rates) {
            $rates = explode(',', $request->tailor_rates);

            foreach ($rates as $rate) {
                $obj->tailorsalary()->create([
                    "price" => $rate
                ]);
            }
        }

        return redirect('admin/Tailor')->with('insert', 'Tailor Add');
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
        $obj = Tailor::find($id);
        $obj->delete();
        return back()->with('delete', 'Tailor Data Delete');
    }

    public function tailorRecord($id)
    {
        $data = [];
        $tailor = Tailor::find($id);
        $data['tailor-name'] = $tailor->name;
        $data['tailor-id'] = $tailor->id;
        $Tailor_records = Tailor::with(['orders' => function ($query) {
            $query->orderBy('created_at', 'desc');  // Order by 'created_at' in descending order
        }, 'orders.customers'])->find($id);
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
        $tailor = Tailor::find($id);

        $filterType = $request->input('filterType', 'weekly');
        $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endDate = Carbon::now()->endOfWeek(Carbon::THURSDAY)->endOfDay();
        // $startDate = Carbon::now()->startOfWeek()->startOfDay();
        // $endDate = Carbon::now()->endOfDay();

        if ($filterType == 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
        }

        $result = DB::table('orders')
            ->leftJoin('tailor_records', 'orders.tailorId', '=', 'tailor_records.tailor_id')
            ->select(
                DB::raw("IF('$filterType' = 'monthly', SUM(tailor_records.amount), tailor_records.amount) as total_received"),
                'tailor_records.comment as comments',
                'orders.suitQuantity as quantity',
                'orders.suitNum as suitNum',
                'orders.design as design',
                'orders.designPrice as designPrice',
                DB::raw('SUM(orders.tailor_price) as totalPayment'),
                'orders.created_at'
            )
            ->where('orders.tailorId', $id)
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
        $tailorRecord = new TailorRecord();
        $tailorRecord->tailor_id = $id;
        $tailorRecord->amount = $request->input('amount');
        $tailorRecord->comment = $request->input('comment');
        $tailorRecord->save();

        return redirect(url('admin/tailor-report', $id))->with('success', 'Record added successfully!');
    }

    public function addAdnvanceRecord(Request $request, $id)
    {
        try {
            $tailorRecord = Tailor::findOrFail($id);
            $tailorRecord->update([
                'advance' => $request->input('amount')
            ]);
        } catch (\Exception $e) {
            // Handle the case where the tailor record is not found
            return response()->json($e->getMessage());
        }

        return redirect()->back();
    }

    public function cutAdvanceRecord(Request $request, $id)
    {
        try {
            $tailorRecord = Tailor::findOrFail($id);
            $advance = $tailorRecord->advance;
            $tailorRecord->update([
                'advance' => $advance - $request->input('amount')
            ]);

            $total = $request->input('total');

            Transaction::create([
                'remainingBalance' => $request->input('amount'),
                'recivedPayment' => $total - $request->input('amount'),
                'Order_type' => 'Tailor_Advance_Cut',
                'tailorId' => $request->input('tailor_id'), //hidden field in modal form
                'userId' => auth()->user()->id
            ]);
        } catch (\Exception $e) {
            // Handle the case where the tailor record is not found
            return response()->json($e->getMessage());
        }

        return redirect()->back();
    }


    public function tailorReportPrint($id)
    {
        try {
            $tailor = Tailor::find($id);

            $user_id = $tailor->user_id;

            $setting = DB::table('settings')->where('user_id', $user_id)->first();

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

            return view('tailor.tailor-report-print', compact('tailor_report', 'total_amount', 'tailor', 'setting', 'tailor_record', 'tailor_records', 'transaction'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function tailorRates($id)
    {
        try {

            $tailor = Tailor::find($id);

            $tailor_rates = $tailor->tailorsalary;

            return view('tailor.tailor-rate', compact('tailor_rates', 'tailor'));
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

            $html .= '<select class="form-control" name="tailor_price" required dir="rtl">
            <option value="">درزی کی رقم منتخب کریں۔</option>';

            foreach ($rates as $rate)
                $html .= '<option value="' . $rate->id . '-' . $rate->price . '">' . $rate->price . ' -- ' . $rate->options->Name . '</option>';

            $html .= '</select>';

            return $html;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function showSpecificRecord(Request $request, $id)
    {
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
            ->where('userId', Auth::user()->id)
            ->get();

        // Return the data as JSON
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
