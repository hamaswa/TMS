<?php

namespace App\Http\Controllers;

use App\Models\CounterSaleReceipt;
use App\Services\CounterSaleReturnService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CounterSaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $search = trim((string) $request->query('search', ''));
        $receipts = CounterSaleReceipt::where('user_id', $ownerId)->where('status', '!=', 'cancelled')
            ->when($request->integer('receipt'), fn ($q, $id) => $q->whereKey($id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%$search%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%$search%")
                            ->orWhere('phone_number1', 'like', "%$search%"));
                });
            })
            ->with(['customer', 'transaction', 'items.type', 'items.brand', 'items.returnItems', 'returns' => fn ($q) => $q->latest()])
            ->latest()->limit(20)->get();
        return view('counter-sale-returns.index', compact('receipts', 'search'));
    }

    public function store(Request $request, CounterSaleReceipt $receipt, CounterSaleReturnService $service)
    {
        abort_unless((int) $receipt->user_id === $request->user()->businessOwnerId(), 404);
        $data = $request->validate([
            'items' => ['required', 'array'], 'items.*' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'], 'restock' => ['required', 'boolean'],
            'settlement_type' => ['nullable', Rule::in(['refund', 'credit'])],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);
        $return = $service->process($receipt, $request->user(), $data['items'], $data);
        return redirect()->route('admin.sale-returns.index', ['receipt' => $receipt->id])
            ->with('success', 'آئٹم واپسی '.$return->reference.' محفوظ، اسٹاک اور اسی فروخت کا کھاتہ درست ہو گیا ہے۔');
    }
}
