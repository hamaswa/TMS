<?php

namespace App\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use App\Models\Cloth;
use App\Models\Stock;
use App\Models\Setting;
use App\Models\Workers;
use App\Models\Expenses;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\SaleStock;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\DaliyExpenses;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClothStockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //     public function index(Request $request)
    // {
    //     try {
    //         $startDate = $request->input('start_date');
    //         $endDate = $request->input('end_date');


    //         if ($startDate && $endDate) {

    //             $startDate = date('Y-m-d', strtotime($startDate));
    //             $endDate = date('Y-m-d', strtotime($endDate));


    //             if ($startDate <= $endDate) {

    //                 $stocks = Stock::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->latest()->get();
    //             } else {

    //                 $stocks = Stock::whereBetween('created_at', [$endDate, $startDate . ' 23:59:59'])->latest()->get();
    //             }
    //         } else {

    //             $stocks = Stock::latest()->get();
    //         }
    //         $stock = Stock::latest()->get();

    //         return view('stock.index', compact('stocks', 'stock'));
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }


    public function index(Request $request)
    {
        try {

            // New Code
            $cloths = Cloth::where('user_id', auth()->user()->id)->with(['colors', 'images'])->latest()->get();


            return view('stock.index', compact('cloths'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }







    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {

            $cloths = ClothBrand::all();
            $cloth_types = ClothType::latest()->get();
            $cloth_brands = ClothBrand::latest()->get();
            return view('stock.create', compact('cloths', 'cloth_types', 'cloth_brands'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            // New Code
            $formData = $request->validate([
                'cloth_type_id' => 'required|string',
                'cloth_brand_id' => 'required',
                'color' => 'required|string',
                'length' => 'required|numeric',
                'price' => 'required|numeric', // No change here
                'image' => 'required|mimes:png,jpg,jpeg', //new change
            ]);
            $file = $formData['image'];
            $fileName = $file->getClientOriginalName();
            $path = $request->image->move('public/images/setting/', $fileName);

            Cloth::create([
                'cloth_type_id' => $formData['cloth_type_id'],
                'cloth_brand_id' => $formData['cloth_brand_id'],
                'color' => $formData['color'],
                'length' => $formData['length'],
                'price' => $formData['price'],
                'image' => $fileName,
            ]);

            return redirect()->route('admin.stock.index')->with('insert', 'اسٹاک کامیابی کے ساتھ شامل ہو گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function show($id)
    {
    }
    public function sellCloth($id = null)
    {
        try {
            // if ($id) {
            //     $stock = Stock::findOrFail($id);
            // } else {
            //     $stock = Stock::first();
            // }

            // Get all the cloth brands,types and colths table table
            $cloths = Cloth::where('user_id', auth()->user()->id)->with('colors')->get();
            $id = auth()->user()->id;
            // dd($id);
            $customers = Customers::where('user_id', $id)->get();
            // dd($customers);
            return view('stock.sell', compact('customers', 'cloths'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getNmbr(Request $request)
    {
        try {
            $id = $request->input('id');
            $data = Customers::select('id', 'phone_number1')->where('id', $id)->where('user_id', auth()->user()->id)->first();

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function getId(Request $request)
    {
        try {
            $name = $request->input('name');
            $data = ClothBrand::select('id')->where('name', $name)->first();

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }




    public function sellStock(Request $request)
    {
        // Retrieve the stock based on the selected brand name
        $brandNames = $request->input('brand_name');
        $clothTypes = $request->input('cloth_type');
        $color = $request->input('color');
        $perMeters = $request->input('per_meter');
        $clothesRacks = $request->input('clothes_rack');
        $lengths = $request->input('length');
        $c_name = $request->input('c_name');
        $name = explode('|', $c_name);
        $cust_name = $name[0];
        $cust_id = $name[1];
        $phone = $request->input('phone');
        $total = $request->input('total');
        $payment = $request->input('payment');
        $remain = $request->input('remain');
        // dd($request->all());

        // dd($brandNames, $clothTypes);

        // Array to store unique timestamps for each sale
        $timestamps = [];
        // // Array to store sale IDs for the customer
        $saleIds = [];

        // Loop through each cloth to store sale stocks
        for ($i = 0; $i < count($brandNames); $i++) {
            $saleStock = new SaleStock();

            $saleStock->cloth_type_id = $clothTypes[$i];
            $saleStock->cloth_brand_id = $brandNames[$i];
            $saleStock->color = $color[$i];
            $saleStock->c_name = $cust_name;
            $saleStock->c_id = $cust_id;
            $saleStock->phone = $phone;
            $saleStock->length = $lengths[$i];
            $saleStock->sellDate = now();
            $saleStock->clothes_rack = $clothesRacks[$i];
            $saleStock->selling_price = $perMeters[$i];
            $saleStock->user_id = Auth::id();

            // Fetch the price from the cloths table
            $cloth = Cloth::where('cloth_type_id', $clothTypes[$i])
                ->where('cloth_brand_id', $brandNames[$i])
                ->first();

            // Fetch the corresponding color length from the ClothColor table
            $clothColor = ClothColor::where('cloth_id', $cloth->id)
                ->where('color', $color[$i]) // Assuming you have an array of colors
                ->first();

                // dd($clothColor);

            // Calculate profit and loss
            if ($cloth) {
                // Check if there's enough available length
                if ($clothColor->length >= $lengths[$i]) {
                    // Update the available length in the cloths table
                    $clothColor->length -= $lengths[$i];
                    $clothColor->save();

                    // Calculate profit and loss
                    $costPrice = $cloth->price;
                    $salePrice = $perMeters[$i];

                    if ($salePrice > $costPrice) {
                        // Calculate profit if sale price is greater than cost price
                        $profit = $salePrice - $costPrice;
                        $loss = 0; // Set loss to 0 if there's profit
                    } else {
                        // Calculate loss if sale price is less than or equal to cost price
                        $profit = 0; // Set profit to 0 if there's loss
                        $loss = $costPrice - $salePrice;
                    }

                    $saleStock->profit = $profit;
                    $saleStock->loss = $loss;
                } else {
                    // Handle the case where there's not enough available length
                    return redirect()->route('admin.stock.index')->with('update', 
                        $cloth->brand->name . ' ' . $cloth->type->name . ' کا اسٹاک کم ہے۔ موجودہ اسٹاک میٹر	: ' . 
                        $clothColor->length . '، مطلوبہ اسٹاک میٹر : ' . $lengths[$i]
                    );
                }
            } else {
                // Handle the case where cloth details are not found
                // You may choose to set profit and loss to 0 or handle it differently
                $saleStock->profit = 0;
                $saleStock->loss = 0;
            }

            $saleStock->save();

            // Store the timestamp for the current sale
            $timestamps[] = $saleStock->created_at;
            // Store the sale ID for the customer
            $saleIds[] = $saleStock->id;
            // Retrieve sale records based on the unique timestamps
            $sellStock = SaleStock::whereIn('created_at', $timestamps)->get();
        }
        // Create transaction for each sale ID
        $transaction = Transaction::create([
            'remainingBalance' => $remain,
            'recivedPayment' => $payment,
            'customerId' => $cust_id,
            'Order_type' => 'Sale',
            'sale_id' => $sellStock->first()->id,
            'userId' => auth()->user()->id
        ]);


        return redirect()->route('admin.printStock', ['id' => $sellStock->first()->id, 'customerId' => $cust_id]);
        // // Array to store unique timestamps for each sale
        // $timestamps = [];
        // // Array to store sale IDs for the customer
        // $saleIds = [];

        // // Loop through the arrays and process each set of values
        // foreach ($brandNames as $key => $brandName) {
        //     // Retrieve the stock based on the selected brand name
        //     $stock = Stock::where('brand_name', $brandName)->first();

        //     $costPrice = $stock->per_meter;
        //     $sellingPrice = $perMeters[$key];

        //     $length = $lengths[$key];
        //     $remainingLength = $stock->length;

        //     // Check if the entered length is greater than the remaining stock length
        //     if ($length > $remainingLength) {
        //         $brandName = $stock->brand_name;
        //         return redirect()->route('admin.stock.index')->with('update', $brandName . ' کے لیے بقیہ لمبائی ' . $remainingLength . ' میٹر ہے ');
        //     }


        //     $profitPerUnit = max(0, $sellingPrice - $costPrice);

        //     $profit = $profitPerUnit * $length;
        //     $loss = max(0, $costPrice - $sellingPrice);

        //     $stock->length -= $length;
        //     $stock->save();

        //     $saleStock = new SaleStock([
        //         'c_name' => $cust_name,
        //         'c_id' => $cust_id,
        //         'phone' => $phone,
        //         'user_id' => auth()->user()->id,
        //         'cloth_brand_id' => $brandName,
        //         'cloth_type_id ' => $clothTypes,
        //         'cloth_id' => $clothIds,
        //         'color' => $color,
        //         'profit' => $profit,
        //         'loss' => $loss,
        //         'length' => $length,
        //         'sellDate' => now(),
        //         'clothes_rack' => $clothesRacks[$key],
        //         'selling_price' => $sellingPrice,
        //     ]);

        //     $stock->saleStocks()->save($saleStock);

        //     // Store the timestamp for the current sale
        //     $timestamps[] = $saleStock->created_at;
        //     // Store the sale ID for the customer
        //     $saleIds[] = $saleStock->id;
        // }

        // Retrieve sale records based on the unique timestamps
        // $sellStock = SaleStock::whereIn('created_at', $timestamps)->get();
        // Create transaction for each sale ID
        // foreach ($saleIds as $saleId) {
        //     $transaction = Transaction::create([
        //         'remainingBalance' => $remain,
        //         'recivedPayment' => $payment,
        //         'customerId' => $id,
        //         'Order_type' => 'Sale',
        //         'sale_id' => $saleId,
        //         'userId' => auth()->user()->id
        //     ]);
        // }

        // return redirect()->route('admin.printStock', ['id' => $sellStock->first()->id, 'customerId' => $id]);
    }




    public function printStock($id, $customerId)
    {
        $sale_id = $id;
        $setting = Setting::where('user_id', Auth::user()->id)->where('status', 1)->first();
        $latestSaleStock = SaleStock::latest('created_at')->first();

        $customers = Customers::where('user_id', auth()->user()->id)
            ->findOrFail($customerId);

        $id = $customers->id;
        // dd($id);

        $gettransactions = Transaction::where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->where('userId', auth()->user()->id)
            ->where('sale_id', $sale_id)
            ->first();

        $remaining = $gettransactions->remainingBalance;
        $payment = $gettransactions->recivedPayment;

        $transactions = Transaction::where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->where('userId', auth()->user()->id)
            ->get();
        // dd($transactions);

        // Calculate the latest balance
        $latestBalance = $transactions->sum('remainingBalance');

        $previousBalance = 0; // Initialize it to zero
        if ($transactions->isNotEmpty()) {
            $latestTransaction = $transactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $transactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }
        // dd($previousBalance);
        // dd($latestBalance);

        $tailortransactions = Transaction::select(
            DB::raw('SUM(remainingBalance) as Balance')
        )
            ->where('customerId', $id)
            ->where('Order_type', 'Tailor')
            ->groupBy('customerId')
            ->first();

        // dd($transactions);


        if ($latestSaleStock) {
            $customerName = $latestSaleStock->c_name;
            $phone = $latestSaleStock->phone;
            $sellStock = SaleStock::where('created_at', $latestSaleStock->created_at)->get();

            return view('stock.stockPrint', compact('sellStock', 'setting', 'customerName', 'phone', 'transactions', 'tailortransactions', 'previousBalance', 'latestBalance', 'remaining', 'payment', 'id'));
        } else {
            return view('stock.stockPrint')->with('error', 'No SaleStock records found.');
        }
    }


    public function printStocks($id, $customerId)
    {
        $sale_id = $id;
        // dd($id);
        $setting = Setting::where('user_id', Auth::user()->id)->where('status', 1)->first();

        $customers = Customers::where('user_id', auth()->user()->id)
            ->findOrFail($customerId);

        $id = $customers->id;

        $transactions = Transaction::where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->where('sale_id', $sale_id)
            ->where('userId', auth()->user()->id)
            ->get();
        // dd($transactions);

        $remaining = $transactions->sum('remainingBalance');
        $payment = $transactions->sum('recivedPayment');

        $latestTransaction = Transaction::where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->where('userId', auth()->user()->id)
            ->latest() // Order by creation date in descending order
            ->first(); // Retrieve the first (latest) transaction

        if ($latestTransaction) {
            $latestReceivedPayment = $latestTransaction->recivedPayment;
        } else {
            $latestReceivedPayment = 0; // Default value if no transaction found
        }
        // dd($latestReceivedPayment);
        $previousTransactions = Transaction::where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->where('userId', auth()->user()->id)
            ->where('id', '<>', $latestTransaction->id) // Exclude the latest transaction
            ->get();

        $previousBalance = $previousTransactions->sum('remainingBalance');

        if ($customers) {
            $customerName = $customers->name;
            $phone = $customers->phone_number1;
            $sellStock = SaleStock::where('id', $sale_id)->where('c_id', $id)->first();
            // dd($sellStock);
            $saleStocks = SaleStock::where('created_at', $sellStock->created_at)
                ->where('c_id', $id)
                ->get();
            // dd($saleStock);

            return view('stock.stockPrints', compact('sellStock', 'setting', 'customerName', 'phone', 'transactions', 'id', 'remaining', 'payment', 'latestReceivedPayment', 'previousBalance', 'saleStocks'));
        } else {
            return view('stock.stockPrints')->with('error', 'No SaleStock records found.');
        }
    }






    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Stock  $stock
     * @return \Illuminate\Http\Response
     */
    public function destroy(Stock $stock)
    {
        try {

            $stock->delete();
            return back()->with('delete', "اسٹاک کامیابی سے حذف ہو گیا۔");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function showSpecificSales(Request $request)
    {
        try {
            $data_range = $request->input('date_range');
            $date_parts = explode('to ', $data_range);

            $start_date = $date_parts[0];
            $end_date = $date_parts[1];

            $stocks = SaleStock::with('type', 'brand')
                ->select(
                    'cloth_brand_id',
                    'cloth_type_id',
                    'selling_price',
                    'sellDate',
                    'c_name',
                    DB::raw('SUM(length) as total_length'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('SUM(loss) as total_loss')
                )
                ->whereBetween('sellDate', [date($start_date), date($end_date)])
                ->where('user_id', auth()->user()->id)
                ->groupBy('sellDate', 'c_name', 'cloth_brand_id', 'cloth_type_id', 'selling_price')->get();

            return response()->json($stocks);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function showList()
    {
        $customerIdsWithTransactions = Transaction::distinct('customerId')->pluck('customerId')->toArray();

        $customers = Customers::where('user_id', auth()->user()->id)
            ->get();

        $customerTransactions = [];

        foreach ($customers as $customer) {
            $transactions = Transaction::where('customerId', $customer->id)->get();
            $totalTransactions = $transactions->sum(function ($transaction) {
                return $transaction->remainingBalance;
            });
            $customerTransactions[$customer->id] = $totalTransactions;
        }

        return view('stock.customer_list', ['customers' => $customers, 'customerTransactions' => $customerTransactions]);
    }


    public function customersDetail($id)
    {
        //sale
        $customer = Customers::findOrFail($id);
        $name = $customer->name;
        $id = $customer->id;
        $stocks = SaleStock::select(
            'sellDate',
            'c_name',
            'cloth_brand_id',
            'cloth_type_id',
            'selling_price',
            DB::raw('SUM(length) as total_length')
        )
            ->where('c_name', $name)
            ->where('c_id', $id)
            ->groupBy('sellDate', 'c_name', 'cloth_brand_id', 'cloth_type_id', 'selling_price')->get();
        // dd($stocks);
        $transactions = Transaction::select(
            'created_at',
            DB::raw('SUM(recivedPayment) as Payment'),
            DB::raw('SUM(remainingBalance) as Balance')
        )
            ->where('customerId', $id)
            ->where('Order_type', 'Sale')
            ->groupBy('customerId')
            ->get();

        // $extransactions = Transaction::where('customerId', $id)
        //     ->where('Order_type', 'Sale')
        //     ->get();

        // dd($extransactions);

        //tailor
        $tailorcustomer = Customers::findOrFail($id);
        $tailortransactions = Transaction::select(
            'created_at',
            DB::raw('SUM(recivedPayment) as Payment'),
            DB::raw('SUM(remainingBalance) as Balance')
        )
            ->where('customerId', $id)
            ->where('Order_type', 'Tailor')
            ->groupBy('customerId')
            ->get();

        return view('stock.show', [
            'customer' => $customer,
            'transactions' => $transactions,
            'tailorcustomer' => $tailorcustomer,
            'tailortransactions' => $tailortransactions,
            'stocks' => $stocks,
            // 'extran' => $extransactions
        ]);
    }

    public function dlt($id)
    {
        $customer = Customers::findOrFail($id);
        if ($customer) {
            $customer->delete();
            return redirect()->back();
        }
    }

    public function totalSales()
    {
        $currentMonth = Carbon::now()->month;

        // $stock = SaleStock::where('user_id', auth()->user()->id)
        //     ->whereMonth('sellDate', $currentMonth)
        //     ->get();

        $stocks = SaleStock::select(
            'cloth_type_id',
            'cloth_brand_id',
            'selling_price',
            DB::raw('SUM(length) as total_length'),
            DB::raw('SUM(profit) as total_profit'),
            DB::raw('SUM(loss) as total_loss')
        )
            ->where('user_id', auth()->user()->id)
            ->groupBy('cloth_type_id', 'cloth_brand_id', 'selling_price')->get();

        // dd($stocks);
        return view('All_Total.index', compact('stocks'));
    }

    public function totalEarning()
    {
        // Array to store earnings for each month
        $monthly_earnings = [];

        // Loop through each month (assuming 1 for January, 2 for February, etc.)
        for ($month = 1; $month <= 12; $month++) {
            $sales = SaleStock::where('user_id', auth()->user()->id)->whereMonth('created_at', $month)->sum(DB::raw('selling_price * length'));

            $extra_expense = DaliyExpenses::where('user_id', auth()->user()->id)->whereMonth('created_at', $month)->sum('Expense_payment');

            $monthly_bills = Expenses::where('user_id', auth()->user()->id)->whereMonth('created_at', $month)->sum('Monthly_Bill');

            $monthly_rent = Expenses::where('user_id', auth()->user()->id)->whereMonth('created_at', $month)->sum('Monthly_Rent');

            $monthly_salary = Workers::where('user_id', auth()->user()->id)->whereMonth('created_at', $month)->sum('Worker_salary');

            // Convert numeric month to month name
            $monthName = DateTime::createFromFormat('!m', $month)->format('F');

            // Store earnings for the current month in the array
            $monthly_earnings[$monthName] = [
                'sales' => $sales,
                'extra_expense' => $extra_expense,
                'monthly_bills' => $monthly_bills,
                'monthly_rent' => $monthly_rent,
                'monthly_salary' => $monthly_salary,
            ];
        }

        return view('All_Total.earning', compact('monthly_earnings'));
    }

    //to show sale data
    public function getSale(Request $req)
    {
        try {
            $id = $req->id;
            $sales = SaleStock::where('c_id', $id)
                ->get();

            $data = [];
            $i = 1;
            foreach ($sales as $sale) {
                $data[] = [
                    'number' => $i++,
                    'totalPayment' => $sale->length * $sale->selling_price,
                    'created_at' => $sale->sellDate,
                    'brand' => $sale->brand->name,
                    'type' => $sale->type->name,
                    'color' => $sale->color,
                    'length' => $sale->length,
                    'rate' => $sale->selling_price,
                    'salesId' => $sale->id,
                    'customerId' => $id,
                ];
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getType(Request $request)
    {
        try {
            $id = $request->input('id');
            $data = Cloth::with('type')->select('cloth_type_id')->where('cloth_brand_id', $id)->where('user_id', auth()->user()->id)->get();

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
