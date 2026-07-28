<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::where('user_id', Auth::user()->businessOwnerId())
            ->withSum(['purchases as purchase_balance' => fn ($query) => $query->where('status', 'received')], 'balance_amount')
            ->withSum(['payments as unallocated_payments' => fn ($query) => $query->whereNull('purchase_id')], 'amount')
            ->orderBy('name')->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request) + ['user_id' => Auth::user()->businessOwnerId()]);

        return back()->with('success', 'سپلائر کامیابی سے شامل کر دیا گیا ہے۔');
    }

    public function edit(int $supplier)
    {
        $supplier = $this->owned($supplier)->loadSum(['purchases as purchase_balance' => fn ($query) => $query->where('status', 'received')], 'balance_amount')
            ->loadSum(['payments as unallocated_payments' => fn ($query) => $query->whereNull('purchase_id')], 'amount')
            ->load(['payments' => fn ($query) => $query->with('purchase')->latest('payment_date')->latest('id')]);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, int $supplier)
    {
        $this->owned($supplier)->update($this->validated($request, $supplier));

        return redirect()->route('admin.suppliers.index')->with('success', 'سپلائر کی معلومات تبدیل کر دی گئی ہیں۔');
    }

    public function destroy(int $supplier)
    {
        $supplier = $this->owned($supplier);
        abort_if($supplier->purchases()->exists(), 422, 'خریداری کی تاریخ رکھنے والے سپلائر کو حذف نہیں کیا جا سکتا۔');
        $supplier->delete();

        return back()->with('success', 'سپلائر حذف کر دیا گیا ہے۔');
    }

    public function payment(Request $request, int $supplier)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'reference' => [
                Rule::requiredIf(fn () => PaymentMethods::requiresReference($request->input('payment_method'))),
                'nullable',
                'string',
                'max:255',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $ownerId = Auth::user()->businessOwnerId();
        DB::transaction(function () use ($validated, $ownerId, $supplier) {
            $lockedSupplier = Supplier::where('user_id', $ownerId)->lockForUpdate()->findOrFail($supplier);
            $purchaseBalance = (float) $lockedSupplier->purchases()
                ->where('status', 'received')
                ->lockForUpdate()
                ->get(['id', 'balance_amount'])
                ->sum('balance_amount');
            $unallocatedPayments = (float) $lockedSupplier->payments()
                ->whereNull('purchase_id')
                ->lockForUpdate()
                ->get(['id', 'amount'])
                ->sum('amount');
            $outstanding = round((float) $lockedSupplier->opening_balance + $purchaseBalance - $unallocatedPayments, 2);

            if ((float) $validated['amount'] > max(0, $outstanding)) {
                throw ValidationException::withMessages([
                    'amount' => 'ادائیگی سپلائر کی موجودہ بقایا رقم سے زیادہ نہیں ہو سکتی۔',
                ]);
            }

            SupplierPayment::create([
                'user_id' => $ownerId, 'supplier_id' => $lockedSupplier->id, 'purchase_id' => null,
                'payment_date' => $validated['payment_date'], 'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'reference' => $validated['reference'] ?? null, 'note' => $validated['note'] ?? 'General supplier payment',
            ]);
        });

        return back()->with('success', 'سپلائر کی ادائیگی درج کر دی گئی ہے۔');
    }

    private function validated(Request $request, ?int $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers')->where('user_id', Auth::user()->businessOwnerId())->ignore($supplier)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function owned(int $id): Supplier
    {
        return Supplier::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
