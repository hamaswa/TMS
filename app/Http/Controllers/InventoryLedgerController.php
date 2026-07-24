<?php

namespace App\Http\Controllers;

use App\Models\ClothColor;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryLedgerController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'cloth_color_id' => ['nullable', 'integer'],
            'movement_type' => ['nullable', Rule::in([
                'purchase_receipt', 'purchase_return', 'counter_sale', 'online_order',
                'online_cancellation', 'online_reorder', 'cart_reservation', 'cart_release',
                'storefront_order', 'storefront_cancellation', 'storefront_return', 'storefront_exchange_issue',
                'manual_adjustment_in', 'manual_adjustment_out',
            ])],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', Rule::in(['15', '25', '50', '100'])],
        ]);
        if (! empty($validated['cloth_color_id'])) {
            ClothColor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_color_id']);
        }
        $movements = InventoryMovement::where('user_id', Auth::user()->businessOwnerId())->with(['cloth.brand', 'cloth.type', 'clothColor'])
            ->when($validated['cloth_color_id'] ?? null, fn ($q, $id) => $q->where('cloth_color_id', $id))
            ->when($validated['movement_type'] ?? null, fn ($q, $type) => $q->where('movement_type', $type))
            ->when($validated['from_date'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))
            ->when($validated['to_date'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date))
            ->latest('occurred_at')->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 25))->withQueryString();
        $colors = ClothColor::where('user_id', Auth::user()->businessOwnerId())->with('cloth.brand', 'cloth.type')->orderBy('cloth_id')->get();

        return view('inventory-ledger.index', compact('movements', 'colors', 'validated'));
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'cloth_color_id' => ['required', 'integer'],
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'required_if:direction,increase', 'numeric', 'min:0'],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($validated) {
            $color = ClothColor::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($validated['cloth_color_id']);
            $inventory = app(InventoryService::class);
            if ($validated['direction'] === 'increase') {
                $inventory->receive($color, (float) $validated['quantity'], (float) $validated['unit_cost'], 'manual_adjustment_in', $color, $validated['note']);
            } else {
                $inventory->issue($color, (float) $validated['quantity'], 'manual_adjustment_out', $color, $validated['note']);
            }
        });

        return back()->with('success', 'اسٹاک کی تبدیلی کامیابی سے درج کر دی گئی ہے۔');
    }

    public function valuation(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', Rule::in(['15', '25', '50', '100'])],
        ]);
        $query = ClothColor::where('user_id', Auth::user()->businessOwnerId())
            ->when($validated['q'] ?? null, fn ($builder, $search) => $builder->where(function ($nested) use ($search) {
                $nested->where('color', 'like', "%{$search}%")
                    ->orWhereHas('cloth.brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('cloth.type', fn ($type) => $type->where('name', 'like', "%{$search}%"));
            }));
        $totals = [
            'meters' => (float) (clone $query)->sum('length'),
            'cost' => (float) (clone $query)->sum(DB::raw('length * average_unit_cost')),
            'retail' => (float) (clone $query)->sum(DB::raw('length * COALESCE((SELECT sale_price FROM cloths WHERE cloths.id = cloth_colors.cloth_id), 0)')),
        ];
        $totals['margin'] = $totals['retail'] - $totals['cost'];
        $colors = $query->with('cloth.brand', 'cloth.type')->orderBy('cloth_id')
            ->paginate((int) ($validated['per_page'] ?? 25))->withQueryString();

        return view('inventory-ledger.valuation', compact('colors', 'totals', 'validated'));
    }
}
