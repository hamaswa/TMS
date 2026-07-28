<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\PrintDocumentService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer', 'detail'])
            ->where('user_id', Auth::user()->businessOwnerId())
            ->latest()
            ->get();

        return view('sale.list', compact('sales'));
    }

    public function create()
    {
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())->orderBy('name')->get();

        return view('sale.create', compact('customers'));
    }

    public function show($id)
    {
        $sale = $this->ownedSale($id)->load('detail');
        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where('Order_type', 'Sale')->where('sale_id', $id)->get();

        // dd($transaction);
        return view('sale.show', compact('sale', 'transaction'));
    }

    public function store(Request $req)
    {
        $validated = $this->validateSale($req);
        $customer = $this->ownedCustomer($validated['customer_id']);

        $sale = DB::transaction(function () use ($validated, $customer) {
            $sale = Sale::create([
                'user_id' => Auth::user()->businessOwnerId(),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
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
                'customerId' => $customer->id,
                'userId' => Auth::user()->businessOwnerId(),
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
            ]);

            return $sale;
        });

        $Id = $sale->id;

        // dd($transaction);
        return redirect(url('admin/sale/print', [$Id]));
    }

    public function edit($id)
    {
        $sales = $this->ownedSale($id)->load('detail');
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())->orderBy('name')->get();
        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where('sale_id', $id)->get();

        // Calculate the latest balance
        $latestBalance = $sales->customer_id
            ? Transaction::where('userId', Auth::user()->businessOwnerId())
                ->where('customerId', $sales->customer_id)
                ->where(fn ($query) => $query->whereNull('sale_id')->orWhere('sale_id', '!=', $sales->id))
                ->sum('remainingBalance')
            : $transaction->sum('remainingBalance');

        return view('sale.edit', compact('sales', 'transaction', 'latestBalance', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateSale($request);
        $sale = $this->ownedSale($id);
        $customer = $this->ownedCustomer($validated['customer_id']);

        DB::transaction(function () use ($validated, $sale, $customer) {
            $sale->update(['customer_id' => $customer->id, 'customer_name' => $customer->name]);
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
                    'customerId' => $customer->id,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
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

        return back()->with('delete', 'فروخت کو کامیابی کے ساتھ حذف کر دیا گیا ہے۔');
    }

    public function print($id)
    {
        $sale = $this->ownedSale($id);

        $saleid = $sale->id;

        $customerTransactions = Transaction::where('userId', Auth::user()->businessOwnerId())
            ->when($sale->customer_id,
                fn ($query) => $query->where('customerId', $sale->customer_id),
                fn ($query) => $query->where('sale_id', $saleid))
            ->orderBy('id')->get();

        // Calculate the latest balance
        $latestBalance = $customerTransactions->sum('remainingBalance');

        $transaction = Transaction::where('userId', Auth::user()->businessOwnerId())->where('sale_id', $id)->latest()->first();

        // Calculate the previous balance
        $previousBalance = 0; // Initialize it to zero
        if ($customerTransactions->isNotEmpty()) {
            $previousBalance = $transaction
                ? $customerTransactions->where('id', '<', $transaction->id)->sum('remainingBalance')
                : $customerTransactions->sum('remainingBalance');
        }

        // $setting = Setting::where('user_id',auth()->user()->businessOwnerId())->where('status',1)->first();

        $setting = Setting::ensureDefaultFor(Auth::user());
        $status = 'default';
        $printConfig = app(PrintDocumentService::class)
            ->make($setting, request(), 'sale-invoice', $sale->id);

        return view('sale.print', compact('sale', 'setting', 'status', 'transaction', 'latestBalance', 'previousBalance', 'printConfig'));
    }

    private function ownedSale($id): Sale
    {
        return Sale::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function validateSale(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'array'],
            'price.*' => ['required', 'numeric', 'min:0'],
            'received_payment' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => [
                Rule::requiredIf(fn () => PaymentMethods::requiresReference($request->input('payment_method'))),
                'nullable',
                'string',
                'max:255',
            ],
            'paid_on' => ['nullable', 'date'],
        ]);

        abort_unless(
            count($validated['name']) === count($validated['quantity'])
                && count($validated['name']) === count($validated['price']),
            422,
            'Sale item fields are incomplete.'
        );

        $saleTotal = collect($validated['price'])
            ->map(fn ($price, $index) => (float) $price * (int) $validated['quantity'][$index])
            ->sum();

        if ((float) $validated['received_payment'] > $saleTotal) {
            throw ValidationException::withMessages([
                'received_payment' => 'موصول شدہ رقم فروخت کی کل قیمت سے زیادہ نہیں ہو سکتی۔',
            ]);
        }

        $validated['remaining_balance'] = round($saleTotal - (float) $validated['received_payment'], 2);

        return $validated;
    }

    private function ownedCustomer(int|string $id): Customers
    {
        return Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
