<?php

namespace App\Http\Controllers;

use App\Models\ClothColor;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothType;
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
        $cloths = Cloth::where('user_id', Auth::user()->businessOwnerId())
            ->with(['brand', 'type', 'colors' => fn ($query) => $query->orderBy('color')])
            ->whereHas('colors')
            ->orderBy('name')
            ->get();
        $colors = $cloths->flatMap->colors;
        $brands = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->orderBy('name')->get();
        $types = ClothType::where('user_id', Auth::user()->businessOwnerId())->orderBy('name')->get();
        $catalog = $cloths->mapWithKeys(fn (Cloth $cloth) => [
            (string) $cloth->id => [
                'id' => $cloth->id,
                'name' => $cloth->name,
                'brand' => $cloth->brand?->name,
                'type' => $cloth->type?->name,
                'stock_code' => $cloth->stock_code,
                'brand_code' => $cloth->brand?->bundle_code,
                'tracks_colors' => $cloth->tracksColors(),
                'colors' => $cloth->colors->map(fn (ClothColor $color) => [
                    'id' => $color->id,
                    'name' => $color->color,
                    'stock' => (float) $color->length,
                    'cost' => (float) ($color->average_unit_cost ?: $cloth->price),
                ])->values(),
            ],
        ]);

        return view('purchases.create', compact('suppliers', 'cloths', 'colors', 'catalog', 'brands', 'types'));
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
            'submit_action' => ['nullable', Rule::in(['draft', 'receive'])],
            'cloth_color_id' => ['nullable', 'array'],
            'cloth_color_id.*' => ['required', 'integer', 'distinct'],
            'quantity' => ['nullable', 'array'],
            'quantity.*' => ['required', 'numeric', 'gt:0'],
            'line_total' => ['nullable', 'array'],
            'line_total.*' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'array'],
            'unit_cost.*' => ['required', 'numeric', 'gt:0'],
            'new_sets' => ['nullable', 'array'],
            'new_sets.*.name' => ['required', 'string', 'max:100'],
            'new_sets.*.cloth_brand_id' => ['required', 'integer'],
            'new_sets.*.cloth_type_id' => ['required', 'integer'],
            'new_sets.*.sale_price' => ['required', 'numeric', 'min:0'],
            'new_sets.*.default_sale_length' => ['nullable', 'numeric', 'gt:0'],
            'new_sets.*.tracking_mode' => ['required', Rule::in([Cloth::COLOR_TRACKING_NONE, Cloth::COLOR_TRACKING_PER_COLOR])],
            'new_sets.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'new_sets.*.total' => ['required', 'numeric', 'gt:0'],
            'new_sets.*.colors' => ['nullable', 'array'],
            'new_sets.*.colors.*.name' => ['required', 'string', 'max:100'],
            'new_sets.*.colors.*.hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'new_sets.*.colors.*.quantity' => ['required', 'numeric', 'gt:0'],
            'new_sets.*.colors.*.image' => ['nullable', 'image', 'max:4096'],
        ], [
            'line_total.*.gt' => 'ہر شامل شدہ کپڑے کی اصل کل خرید قیمت درج کریں۔',
            'unit_cost.*.gt' => 'ہر شامل شدہ کپڑے کی اصل فی میٹر لاگت درج کریں۔',
        ]);
        $existingColorIds = array_values($validated['cloth_color_id'] ?? []);
        $existingQuantities = array_values($validated['quantity'] ?? []);
        $submittedLineTotals = array_values($validated['line_total'] ?? []);
        $submittedUnitCosts = $validated['unit_cost'] ?? [];
        $newSets = array_values($validated['new_sets'] ?? []);
        if ($existingColorIds === [] && $newSets === []) {
            throw ValidationException::withMessages(['items' => 'کم از کم ایک نیا یا موجودہ کپڑے کا سیٹ شامل کریں۔']);
        }
        abort_unless(
            count($existingColorIds) === count($existingQuantities)
                && (
                    count($existingQuantities) === count($submittedLineTotals)
                    || count($existingQuantities) === count($submittedUnitCosts)
                ),
            422
        );
        $ownerId = Auth::user()->businessOwnerId();
        foreach ($newSets as $index => $set) {
            ClothBrand::where('user_id', $ownerId)->findOrFail($set['cloth_brand_id']);
            ClothType::where('user_id', $ownerId)->findOrFail($set['cloth_type_id']);
            $duplicate = Cloth::where('user_id', $ownerId)
                ->where('cloth_brand_id', $set['cloth_brand_id'])
                ->where('cloth_type_id', $set['cloth_type_id'])
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($set['name']))])
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    "new_sets.{$index}.name" => 'یہ سیٹ پہلے سے موجود ہے؛ اسے موجودہ سیٹ دوبارہ خریدیں سے منتخب کریں۔',
                ]);
            }
            if ($set['tracking_mode'] === Cloth::COLOR_TRACKING_PER_COLOR) {
                $setColors = array_values($set['colors'] ?? []);
                if ($setColors === []) {
                    throw ValidationException::withMessages(["new_sets.{$index}.colors" => 'کم از کم ایک رنگ شامل کریں۔']);
                }
                $names = collect($setColors)->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)));
                if ($names->unique()->count() !== $names->count()) {
                    throw ValidationException::withMessages(["new_sets.{$index}.colors" => 'ایک رنگ صرف ایک مرتبہ شامل کریں۔']);
                }
            } elseif (empty($set['quantity'])) {
                throw ValidationException::withMessages(["new_sets.{$index}.quantity" => 'خریدے گئے میٹر درج کریں۔']);
            }
        }
        $supplier = $this->ownedSupplier($validated['supplier_id']);

        $purchase = DB::transaction(function () use ($validated, $supplier, $existingColorIds, $existingQuantities, $submittedLineTotals, $submittedUnitCosts, $newSets, $ownerId) {
            $purchase = Purchase::create([
                'user_id' => Auth::user()->businessOwnerId(), 'supplier_id' => $supplier->id,
                'purchase_number' => 'TMP-'.Str::uuid(), 'purchase_date' => $validated['purchase_date'],
                'status' => 'draft', 'reference' => $validated['reference'] ?? null, 'note' => $validated['note'] ?? null,
            ]);
            $total = 0;
            foreach ($existingColorIds as $index => $colorId) {
                $color = ClothColor::where('user_id', $ownerId)->with('cloth')->findOrFail($colorId);
                abort_unless((int) $color->cloth->user_id === (int) $ownerId, 404);
                $quantity = round((float) $existingQuantities[$index], 2);
                $lineTotal = isset($submittedLineTotals[$index])
                    ? round((float) $submittedLineTotals[$index], 2)
                    : round($quantity * (float) $submittedUnitCosts[$index], 2);
                $unitCost = round($lineTotal / $quantity, 4);
                $purchase->items()->create([
                    'cloth_id' => $color->cloth_id, 'cloth_color_id' => $color->id, 'color' => $color->color,
                    'quantity' => $quantity, 'unit_cost' => $unitCost, 'line_total' => $lineTotal,
                ]);
                $total += $lineTotal;
            }
            foreach ($newSets as $set) {
                $quantities = $set['tracking_mode'] === Cloth::COLOR_TRACKING_PER_COLOR
                    ? collect($set['colors'])->map(fn ($color) => [
                        'name' => trim($color['name']),
                        'hex' => $color['hex'] ?? null,
                        'quantity' => round((float) $color['quantity'], 2),
                        'image' => $color['image'] ?? null,
                    ])->values()->all()
                    : [['name' => 'عام', 'hex' => null, 'quantity' => round((float) $set['quantity'], 2)]];
                $setQuantity = array_sum(array_column($quantities, 'quantity'));
                $setTotal = round((float) $set['total'], 2);
                $unitCost = round($setTotal / $setQuantity, 4);
                $cloth = Cloth::create([
                    'name' => trim($set['name']),
                    'cloth_type_id' => $set['cloth_type_id'],
                    'cloth_brand_id' => $set['cloth_brand_id'],
                    'price' => $unitCost,
                    'sale_price' => round((float) $set['sale_price'], 2),
                    'default_sale_length' => isset($set['default_sale_length']) ? round((float) $set['default_sale_length'], 2) : null,
                    'sale_price_basis' => Cloth::SALE_PRICE_PER_METER,
                    'color_tracking_mode' => $set['tracking_mode'],
                    'user_id' => $ownerId,
                ]);
                $allocated = 0;
                foreach ($quantities as $colorIndex => $colorData) {
                    $color = ClothColor::create([
                        'cloth_id' => $cloth->id,
                        'color' => $colorData['name'],
                        'color_hex' => $colorData['hex'],
                        'length' => 0,
                        'average_unit_cost' => $unitCost,
                        'user_id' => $ownerId,
                    ]);
                    if (! empty($colorData['image'])) {
                        $cloth->images()->create([
                            'images' => $colorData['image']->store('ClothImages', 'public'),
                            'image_color' => $color->color,
                            'user_id' => $ownerId,
                        ]);
                    }
                    $lineTotal = $colorIndex === array_key_last($quantities)
                        ? round($setTotal - $allocated, 2)
                        : round($setTotal * $colorData['quantity'] / $setQuantity, 2);
                    $allocated += $lineTotal;
                    $purchase->items()->create([
                        'cloth_id' => $cloth->id,
                        'cloth_color_id' => $color->id,
                        'color' => $color->color,
                        'quantity' => $colorData['quantity'],
                        'unit_cost' => round($lineTotal / $colorData['quantity'], 4),
                        'line_total' => $lineTotal,
                    ]);
                }
                $total += $setTotal;
            }
            $purchase->update([
                'purchase_number' => 'PO-'.now()->format('Ymd').'-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT),
                'total_amount' => $total, 'balance_amount' => $total,
            ]);

            return $purchase;
        });

        $receiveNow = ($validated['submit_action'] ?? 'draft') === 'receive';
        if ($receiveNow) {
            $this->receivePurchase($purchase->id);
        }

        return redirect()->route('admin.purchases.show', $purchase)->with(
            'success',
            $receiveNow
                ? 'خریداری محفوظ ہو گئی، مال انوینٹری میں شامل کر دیا گیا ہے۔'
                : 'خریداری کا مسودہ محفوظ ہو گیا ہے؛ مال وصول ہونے پر انوینٹری میں شامل کریں۔'
        );
    }

    public function show(int $purchase)
    {
        $purchase = $this->ownedPurchase($purchase)->load(['supplier', 'items.cloth.brand', 'items.cloth.type', 'payments', 'returns.items']);

        return view('purchases.show', compact('purchase'));
    }

    public function receive(int $purchase)
    {
        $this->receivePurchase($purchase);

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

    private function receivePurchase(int $purchaseId): void
    {
        DB::transaction(function () use ($purchaseId) {
            $inventory = app(InventoryService::class);
            $purchase = Purchase::where('user_id', Auth::user()->businessOwnerId())
                ->lockForUpdate()
                ->findOrFail($purchaseId);
            abort_unless($purchase->status === 'draft', 422, 'Only draft purchases can be received.');
            foreach ($purchase->items()->lockForUpdate()->get() as $item) {
                $color = ClothColor::where('user_id', Auth::user()->businessOwnerId())
                    ->lockForUpdate()
                    ->findOrFail($item->cloth_color_id);
                $item->update(['received_quantity' => $item->quantity]);
                $inventory->receive(
                    $color,
                    (float) $item->quantity,
                    (float) $item->unit_cost,
                    'purchase_receipt',
                    $purchase,
                    $purchase->purchase_number,
                );
            }
            $purchase->update(['status' => 'received', 'received_at' => now()]);
        });
    }
}
