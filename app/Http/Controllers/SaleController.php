<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Saledetail;
use App\Models\Transaction;
use App\Models\Setting;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::all();

        return view("sale.list", compact('sales'));
    }

    public function create()
    {
        return view('sale.create');
    }

    public function show($id)
    {
        $sale = Sale::findOrFail($id);
        $transaction = Transaction::where('Order_type', 'Sale')->where('sale_id', $id)->get();
        // dd($transaction);
        return view('sale.show', compact('sale', 'transaction'));
    }

    public function store(Request $req)
    {

        $sale = Sale::create([
            "customer_name" => $req->customer_name
        ]);

        $input = $req->all();

        for ($i = 0; $i < count($_POST['name']); $i++) {
            $sale->detail()->create([
                "product_name" => $input['name'][$i],
                "quantity" => $input['quantity'][$i],
                "price" => $input['price'][$i],
            ]);
        }

        $Id = $sale->id;

        //Insert into transactions table
        $transaction = Transaction::create([
            'remainingBalance' => $req->input('remaining_balance'),
            'recivedPayment' => $req->input('received_payment'),
            'Order_type' => 'Sale',
            'sale_id' => $Id,
            'userId' => auth()->user()->id,
        ]);

        // dd($transaction);
        return redirect(url('admin/sale/print', [$Id]));
    }

    public function edit($id)
    {
        $sales = Sale::findOrFail($id);
        $transaction = Transaction::where("sale_id", $id)->first();
        $transaction = Transaction::where("sale_id", $id)->get();

        // Calculate the latest balance
        $latestBalance = $transaction->sum('remainingBalance');

        return view('sale.edit', compact("sales", "transaction", "latestBalance"));
    }




    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $sale->update([
            "customer_name" => $request->customer_name,
        ]);

        $input = $request->all();
        $sale->detail()->delete();

        $totalRemainingBalance = 0;

        for ($i = 0; $i < count($input['name']); $i++) {
            $detail = $sale->detail()->create([
                "product_name" => $input['name'][$i],
                "quantity" => $input['quantity'][$i],
                "price" => $input['price'][$i],
            ]);

            $totalRemainingBalance += $detail->price;
        }
        $receivedPayment = $request->input('received_payment');

        $remainingBalance = $totalRemainingBalance - $receivedPayment;


        $transaction = new Transaction([
            'remainingBalance' => $remainingBalance,
            'recivedPayment' => $receivedPayment,
        ]);

        $sale->transaction()->save($transaction);

        return redirect(url('admin/sale/print', [$id]));
    }



    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        $sale->delete();

        return back()->with("delete", "فروخت کو کامیابی کے ساتھ حذف کر دیا گیا ہے۔");
    }

    public function print($id)
    {
        $sale = Sale::find($id);

        $saleid = $sale->id;


        $customerTransactions = Transaction::where("sale_id", $saleid)->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        $transaction = Transaction::where('sale_id', $id)->latest()->first();


        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        // $setting = Setting::where('user_id',auth()->user()->id)->where('status',1)->first();

        $setting = Setting::where('user_id', auth()->user()->id)->where('status', 1)->first();
        if (!$setting) {
            dd("Please Activate Your Setting");
        } else {

            $status = "default";
            return view('sale.print', compact('sale', 'setting', 'status', 'transaction', 'latestBalance', 'previousBalance'));
        }
    }
}
