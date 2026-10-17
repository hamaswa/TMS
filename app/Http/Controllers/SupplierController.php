<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
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
            ->withSum(['purchases as purchase_total' => fn ($query) => $query->where('status', 'received')], 'total_amount')
            ->withSum('payments as total_paid', 'amount')
            ->orderBy('name')->get();

        $purchaseOptions = Purchase::where('user_id', Auth::user()->businessOwnerId())->where('status', 'received')->orderByDesc('purchase_date')->get(['id','supplier_id','purchase_number']);
        return view('suppliers.index', compact('suppliers', 'purchaseOptions'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request) + ['user_id' => Auth::user()->businessOwnerId()]);

        return redirect()->route('admin.suppliers.index')->with('success', 'سپلائر کامیابی سے شامل کر دیا گیا ہے۔');
    }

    public function edit(int $supplier)
    {
        $supplier = $this->owned($supplier)->loadSum(['purchases as purchase_total' => fn ($query) => $query->where('status', 'received')], 'total_amount')
            ->loadSum('payments as total_paid', 'amount')
            ->load(['payments' => fn ($query) => $query->with('purchase')->latest('payment_date')->latest('id')]);

        $purchaseOptions = $supplier->purchases()->where('status', 'received')->orderByDesc('purchase_date')->get(['id','purchase_number']);
        return view('suppliers.edit', compact('supplier', 'purchaseOptions'));
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
            'purchase_id' => ['nullable', 'integer'],
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
            $purchaseId = $this->validatedPurchaseId($validated['purchase_id'] ?? null, $lockedSupplier->id, $ownerId);
            // Supplier payments are account-level; purchase references are informational only.
            // Advance payments are permitted when no balance is due.
            SupplierPayment::create([
                'user_id' => $ownerId, 'supplier_id' => $lockedSupplier->id, 'purchase_id' => $purchaseId,
                'payment_date' => $validated['payment_date'], 'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'reference' => $validated['reference'] ?? null, 'note' => $validated['note'] ?? 'General supplier payment',
            ]);
        });

        return back()->with('success', 'سپلائر کی ادائیگی درج کر دی گئی ہے۔');
    }

    public function linkPayment(Request $request, int $supplier, int $payment)
    {
        $validated = $request->validate(['purchase_id' => ['nullable', 'integer']]);
        $ownerId = Auth::user()->businessOwnerId();
        $this->owned($supplier);
        $record = SupplierPayment::where('user_id', $ownerId)->where('supplier_id', $supplier)->findOrFail($payment);
        $record->update(['purchase_id' => $this->validatedPurchaseId($validated['purchase_id'] ?? null, $supplier, $ownerId)]);

        return back()->with('success', 'ادائیگی کا خریداری حوالہ محفوظ کر دیا گیا ہے۔');
    }

    private function validatedPurchaseId(?int $purchaseId, int $supplierId, int $ownerId): ?int
    {
        if ($purchaseId === null) return null;
        return Purchase::where('user_id', $ownerId)->where('supplier_id', $supplierId)
            ->where('status', 'received')->findOrFail($purchaseId)->id;
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
