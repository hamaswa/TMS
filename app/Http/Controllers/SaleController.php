<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Saledetail;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::where('user_id', Auth::user()->businessOwnerId())->latest()->get();

        return view("sale.list", compact('sales'));
    }

    public function create()
    {
        return view('sale.create');
    }

    public function show($id)
    {
        $sale = $this->ownedSale($id);
        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where('Order_type', 'Sale')->where('sale_id', $id)->get();
        // dd($transaction);
        return view('sale.show', compact('sale', 'transaction'));
    }

    public function store(Request $req)
    {
        $validated = $this->validateSale($req);

        $sale = DB::transaction(function () use ($validated) {
            $sale = Sale::create([
                'user_id' => Auth::user()->businessOwnerId(),
                'customer_name' => $validated['customer_name'],
            ]);

            foreach ($validated['name'] as $index => $productName) {
                $sale->detail()->create([
                    'product_name' => $productName,
                    'quantity' => $validated['quantity'][$index],
                    'price' => $validated['price'][$index],
                ]);
            }

            Transaction::create([
                'remainingBalance' => $validated['remaining_balance'],
                'recivedPayment' => $validated['received_payment'],
                'Order_type' => 'Sale',
                'sale_id' => $sale->id,
                'userId' => Auth::user()->businessOwnerId(),
            ]);

            return $sale;
        });

        $Id = $sale->id;

        // dd($transaction);
        return redirect(url('admin/sale/print', [$Id]));
    }

    public function edit($id)
    {
        $sales = $this->ownedSale($id);
        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where("sale_id", $id)->get();

        // Calculate the latest balance
        $latestBalance = $transaction->sum('remainingBalance');

        return view('sale.edit', compact("sales", "transaction", "latestBalance"));
    }




    public function update(Request $request, $id)
    {
        $validated = $this->validateSale($request);
        $sale = $this->ownedSale($id);

        DB::transaction(function () use ($validated, $sale) {
            $sale->update(['customer_name' => $validated['customer_name']]);
            $sale->detail()->delete();

            foreach ($validated['name'] as $index => $productName) {
                $sale->detail()->create([
                    'product_name' => $productName,
                    'quantity' => $validated['quantity'][$index],
                    'price' => $validated['price'][$index],
                ]);
            }

            Transaction::updateOrCreate(
                ['sale_id' => $sale->id, 'userId' => Auth::user()->businessOwnerId(), 'Order_type' => 'Sale'],
                [
                    'remainingBalance' => $validated['remaining_balance'],
                    'recivedPayment' => $validated['received_payment'],
                ]
            );
        });

        return redirect(url('admin/sale/print', [$id]));
    }



    public function destroy($id)
    {
        $sale = $this->ownedSale($id);

        DB::transaction(function () use ($sale) {
            Transaction::where('userId', Auth::user()->businessOwnerId())->where('sale_id', $sale->id)->delete();
            $sale->delete();
        });

        return back()->with("delete", "فروخت کو کامیابی کے ساتھ حذف کر دیا گیا ہے۔");
    }

    public function print($id)
    {
        $sale = $this->ownedSale($id);

        $saleid = $sale->id;


        $customerTransactions = Transaction::where('userId', Auth::user()->businessOwnerId())->where("sale_id", $saleid)->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where('sale_id', $id)->latest()->first();


        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $latestTransaction = $customerTransactions->last();

            // Calculate the sum of remaining balances excluding the latest transaction
            $previousBalance = $customerTransactions->where('id', '<', $latestTransaction->id)->sum('remainingBalance');
        }

        // $setting = Setting::where('user_id',auth()->user()->businessOwnerId())->where('status',1)->first();

        $setting = Setting::where('user_id', auth()->user()->businessOwnerId())->where('status', 1)->first();
        if (!$setting) {
            dd("Please Activate Your Setting");
        } else {

            $status = "default";
            return view('sale.print', compact('sale', 'setting', 'status', 'transaction', 'latestBalance', 'previousBalance'));
        }
    }

    private function ownedSale($id): Sale
    {
        return Sale::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function validateSale(Request $request): array
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
            'received_payment' => ['required', 'numeric', 'min:0'],
            'remaining_balance' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(
            count($validated['name']) === count($validated['quantity'])
                && count($validated['name']) === count($validated['price']),
            422,
            'Sale item fields are incomplete.'
        );

        return $validated;
    }
}
