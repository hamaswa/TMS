<?php

namespace App\Http\Controllers;

use App\Models\ClothColor;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\InventoryService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'received', 'cancelled'])],
            'supplier_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', Rule::in(['15', '25', '50', '100'])],
        ]);
        if (! empty($filters['supplier_id'])) {
            $this->ownedSupplier((int) $filters['supplier_id']);
        }
        $ownerId = Auth::user()->businessOwnerId();
        $summaryQuery = Purchase::where('user_id', $ownerId);
        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'total' => (float) (clone $summaryQuery)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'paid' => (float) (clone $summaryQuery)->where('status', '!=', 'cancelled')->sum('paid_amount'),
            'balance' => (float) (clone $summaryQuery)->where('status', '!=', 'cancelled')->sum('balance_amount'),
        ];
        $purchases = Purchase::where('user_id', $ownerId)->with('supplier')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['supplier_id'] ?? null, fn ($q, $supplier) => $q->where('supplier_id', $supplier))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(function ($nested) use ($search) {
                $nested->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            }))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('purchase_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('purchase_date', '<=', $date))
            ->latest('purchase_date')->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();
        $suppliers = Supplier::where('user_id', Auth::user()->businessOwnerId())->where('active', true)->orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'suppliers', 'filters', 'summary'));
    }

    public function create()
    {
        $suppliers = Supplier::where('user_id', Auth::user()->businessOwnerId())->where('active', true)->orderBy('name')->get();
        $colors = ClothColor::where('user_id', Auth::user()->businessOwnerId())
            ->with('cloth.brand', 'cloth.type')->orderBy('cloth_id')->orderBy('color')->get();

        return view('purchases.create', compact('suppliers', 'colors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'purchase_date' => ['required', 'date'],
            'reference' => [
                Rule::requiredIf(fn () => PaymentMethods::requiresReference($request->input('payment_method'))),
                'nullable',
                'string',
                'max:255',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'cloth_color_id' => ['required', 'array', 'min:1'],
            'cloth_color_id.*' => ['required', 'integer', 'distinct'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'array'],
            'unit_cost.*' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(count($validated['cloth_color_id']) === count($validated['quantity']) && count($validated['quantity']) === count($validated['unit_cost']), 422);
        $supplier = $this->ownedSupplier($validated['supplier_id']);

        $purchase = DB::transaction(function () use ($validated, $supplier) {
            $purchase = Purchase::create([
                'user_id' => Auth::user()->businessOwnerId(), 'supplier_id' => $supplier->id,
                'purchase_number' => 'TMP-'.Str::uuid(), 'purchase_date' => $validated['purchase_date'],
                'status' => 'draft', 'reference' => $validated['reference'] ?? null, 'note' => $validated['note'] ?? null,
            ]);
            $total = 0;
            foreach ($validated['cloth_color_id'] as $index => $colorId) {
                $color = ClothColor::where('user_id', Auth::user()->businessOwnerId())->with('cloth')->findOrFail($colorId);
                abort_unless((int) $color->cloth->user_id === (int) Auth::user()->businessOwnerId(), 404);
                $quantity = round((float) $validated['quantity'][$index], 2);
                $unitCost = round((float) $validated['unit_cost'][$index], 2);
                $lineTotal = round($quantity * $unitCost, 2);
                $purchase->items()->create([
                    'cloth_id' => $color->cloth_id, 'cloth_color_id' => $color->id, 'color' => $color->color,
                    'quantity' => $quantity, 'unit_cost' => $unitCost, 'line_total' => $lineTotal,
                ]);
                $total += $lineTotal;
            }
            $purchase->update([
                'purchase_number' => 'PO-'.now()->format('Ymd').'-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT),
                'total_amount' => $total, 'balance_amount' => $total,
            ]);

            return $purchase;
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'خریداری کا مسودہ بنا دیا گیا ہے۔');
    }

    public function show(int $purchase)
    {
        $purchase = $this->ownedPurchase($purchase)->load(['supplier', 'items.cloth.brand', 'items.cloth.type', 'payments', 'returns.items']);

        return view('purchases.show', compact('purchase'));
    }

    public function receive(int $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $inventory = app(InventoryService::class);
            $purchase = Purchase::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($purchase);
            abort_unless($purchase->status === 'draft', 422, 'Only draft purchases can be received.');
            foreach ($purchase->items()->lockForUpdate()->get() as $item) {
                $color = ClothColor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($item->cloth_color_id);
                $item->update(['received_quantity' => $item->quantity]);
                $inventory->receive($color, (float) $item->quantity, (float) $item->unit_cost, 'purchase_receipt', $purchase, $purchase->purchase_number);
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
        });

        return back()->with('success', 'مال وصول ہو گیا اور اسٹاک اپ ڈیٹ کر دیا گیا ہے۔');
    }

    public function cancel(int $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $purchase = Purchase::where('user_id', Auth::user()->businessOwnerId())
                ->lockForUpdate()
                ->findOrFail($purchase);
            abort_unless($purchase->status === 'draft', 422, 'Only draft purchases can be cancelled.');
            $purchase->update([
                'status' => 'cancelled',
                'balance_amount' => 0,
                'cancelled_at' => now(),
            ]);
        });

        return back()->with('success', 'خریداری منسوخ کر دی گئی ہے۔');
    }

    public function payment(Request $request, int $purchase)
    {
        $purchase = $this->ownedPurchase($purchase);
        abort_unless($purchase->status === 'received', 422, 'Payments can only be posted to received purchases.');
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.max(0, (float) $purchase->balance_amount)],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($purchase, $validated) {
            $purchase = Purchase::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($purchase->id);
            $amount = round((float) $validated['amount'], 2);
            if ($amount > (float) $purchase->balance_amount) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the current purchase balance.']);
            }
            SupplierPayment::create([
                'user_id' => Auth::user()->businessOwnerId(), 'supplier_id' => $purchase->supplier_id, 'purchase_id' => $purchase->id,
                'payment_date' => $validated['payment_date'], 'amount' => $amount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'reference' => $validated['reference'] ?? null, 'note' => $validated['note'] ?? null,
            ]);
            $purchase->update(['paid_amount' => (float) $purchase->paid_amount + $amount, 'balance_amount' => (float) $purchase->balance_amount - $amount]);
        });

        return back()->with('success', 'سپلائر کی ادائیگی درج کر دی گئی ہے۔');
    }

    public function returnItem(Request $request, int $purchase)
    {
        $purchase = $this->ownedPurchase($purchase);
        abort_unless($purchase->status === 'received', 422, 'Only received goods can be returned.');
        $validated = $request->validate([
            'purchase_item_id' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'],
            'return_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($purchase, $validated) {
            $inventory = app(InventoryService::class);
            $purchase = Purchase::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($purchase->id);
            $item = PurchaseItem::where('purchase_id', $purchase->id)->lockForUpdate()->findOrFail($validated['purchase_item_id']);
            $quantity = round((float) $validated['quantity'], 2);
            $availableToReturn = (float) $item->received_quantity - (float) $item->returned_quantity;
            if ($quantity > $availableToReturn) {
                throw ValidationException::withMessages(['quantity' => 'Return quantity exceeds the received quantity remaining.']);
            }
            $color = ClothColor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($item->cloth_color_id);
            if ($quantity > (float) $color->length) {
                throw ValidationException::withMessages(['quantity' => 'Current stock is lower than this return quantity.']);
            }
            $lineTotal = round($quantity * (float) $item->unit_cost, 2);
            $return = PurchaseReturn::create([
                'user_id' => Auth::user()->businessOwnerId(), 'supplier_id' => $purchase->supplier_id, 'purchase_id' => $purchase->id,
                'return_number' => 'TMP-'.Str::uuid(), 'return_date' => $validated['return_date'],
                'total_amount' => $lineTotal, 'note' => $validated['note'] ?? null,
            ]);
            $return->update(['return_number' => 'PR-'.now()->format('Ymd').'-'.str_pad((string) $return->id, 6, '0', STR_PAD_LEFT)]);
            $return->items()->create([
                'purchase_item_id' => $item->id, 'cloth_color_id' => $color->id,
                'quantity' => $quantity, 'unit_cost' => $item->unit_cost, 'line_total' => $lineTotal,
            ]);
            $inventory->issue($color, $quantity, 'purchase_return', $return, $return->return_number, (float) $item->unit_cost);
            $item->increment('returned_quantity', $quantity);
            $newTotal = round((float) $purchase->total_amount - $lineTotal, 2);
            $purchase->update(['total_amount' => $newTotal, 'balance_amount' => $newTotal - (float) $purchase->paid_amount]);
        });

        return back()->with('success', 'خریداری واپسی درج ہو گئی اور اسٹاک کم کر دیا گیا ہے۔');
    }

    private function ownedPurchase(int $id): Purchase
    {
        return Purchase::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function ownedSupplier(int $id): Supplier
    {
        return Supplier::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
