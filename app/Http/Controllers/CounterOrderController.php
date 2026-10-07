<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use App\Models\CounterSaleReceipt;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\SaleSession;
use App\Models\Setting;
use App\Models\User;
use App\Services\CounterOrderService;
use App\Services\MeasurementService;
use App\Services\PrintDocumentService;
use App\Support\PaymentMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CounterOrderController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorizeAnyOrderAccess($request);
        $canAddCloth = $this->canManageCloth($request);
        $canAddTailoring = $this->canManageTailoring($request);
        $customers = $this->customers($request);
        $selectedCustomer = $request->integer('customer')
            ? $customers->firstWhere('id', $request->integer('customer'))
            : null;
        $selectedProfileId = $selectedCustomer
            ? Customers::where('user_id', $request->user()->businessOwnerId())
                ->whereKey($request->integer('profile', $selectedCustomer->id))
                ->where(fn ($query) => $query->whereKey($selectedCustomer->id)->orWhere('parent_id', $selectedCustomer->id))
                ->value('id')
            : null;
        $openOrders = $selectedCustomer
            ? CounterOrder::where('user_id', $request->user()->businessOwnerId())
                ->where('customer_id', $selectedCustomer->id)
                ->where('status', '!=', CounterOrder::STATUS_CLOSED)
                ->withCount([
                    'items as draft_items_count' => fn ($query) => $query->where('status', CounterOrderItem::STATUS_DRAFT),
                    'items as confirmed_items_count' => fn ($query) => $query->where('status', CounterOrderItem::STATUS_CONFIRMED),
                ])->latest('updated_at')->get()
            : collect();

        return view('counter-orders.create', compact(
            'customers', 'selectedCustomer', 'selectedProfileId', 'openOrders',
            'canAddCloth', 'canAddTailoring'
        ));
    }

    public function store(Request $request, CounterOrderService $service): RedirectResponse
    {
        $this->authorizeAnyOrderAccess($request);
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'profile_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $customer = Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')->findOrFail($validated['customer_id']);
        $order = $service->create($request->user(), $customer, $validated['note'] ?? null);

        return redirect()->route('admin.counter-orders.edit', [
            'counterOrder' => $order,
            'profile' => $validated['profile_id'] ?? null,
        ])
            ->with('success', 'نیا آرڈر شروع ہو گیا ہے۔ اب کپڑا یا سلائی شامل کریں۔');
    }

    public function edit(Request $request, CounterOrder $counterOrder): View
    {
        $this->authorizeOrder($request, $counterOrder);
        $ownerId = $request->user()->businessOwnerId();
        $counterOrder->load([
            'customer', 'saleSession.agent', 'items.cloth.brand', 'items.cloth.type',
            'items.measurementProfile', 'items.measurementTemplate',
        ]);
        $canAddCloth = $this->canManageCloth($request);
        $canAddTailoring = $this->canManageTailoring($request);
        $canManageAllDrafts = $counterOrder->items->where('status', CounterOrderItem::STATUS_DRAFT)
            ->every(fn (CounterOrderItem $item) => $this->canManageItem($request, $item));
        $canManageAllItems = $counterOrder->items->where('status', '!=', CounterOrderItem::STATUS_CANCELLED)
            ->every(fn (CounterOrderItem $item) => $this->canManageItem($request, $item));
        $cloths = $canAddCloth ? Cloth::where('user_id', $ownerId)->with(['brand', 'type', 'colors'])->orderBy('id')->get() : collect();
        $profiles = Customers::where('user_id', $ownerId)
            ->where(fn ($query) => $query->whereKey($counterOrder->customer_id)->orWhere('parent_id', $counterOrder->customer_id))
            ->orderByRaw('parent_id is not null')->orderBy('name')->get();
        $selectedProfileId = $profiles->contains('id', $request->integer('profile'))
            ? $request->integer('profile')
            : $profiles->first()?->id;
        $templates = $canAddTailoring ? MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('name')->get() : collect();
        $paymentMethods = PaymentMethods::LABELS;
        $receiptLinks = CounterSaleReceipt::where('user_id', $ownerId)
            ->whereIn('id', $counterOrder->items->where('source_record_type', 'counter_sale_receipt')->pluck('source_record_id'))
            ->get()->keyBy('id');

        return view('counter-orders.edit', compact(
            'counterOrder', 'canAddCloth', 'canAddTailoring', 'cloths', 'profiles',
            'templates', 'paymentMethods', 'receiptLinks', 'selectedProfileId',
            'canManageAllDrafts', 'canManageAllItems'
        ));
    }

    public function print(
        Request $request,
        CounterOrder $counterOrder,
        PrintDocumentService $documents,
    ): View {
        $this->authorizeOrder($request, $counterOrder);
        $counterOrder->load([
            'customer', 'items.cloth.brand', 'items.cloth.type',
            'items.measurementProfile', 'items.measurementTemplate',
        ]);
        abort_unless(
            $counterOrder->items->contains('status', CounterOrderItem::STATUS_CONFIRMED),
            404
        );

        $setting = Setting::ensureDefaultFor($request->user());
        $printConfig = $documents->make(
            $setting,
            $request,
            'counter-order',
            $counterOrder->reference,
        );

        return view('counter-orders.print', compact('counterOrder', 'setting', 'printConfig'));
    }

    public function legacyTailoringCreate(Request $request, int $id): RedirectResponse
    {
        $request->session()->reflash();

        return redirect()->route('admin.counter-orders.create', [
            'customer' => $id,
            'profile' => $request->integer('profile', $id),
        ])->with('success', 'نیا آرڈر اب مشترکہ آرڈر ڈیسک سے شروع ہوتا ہے۔');
    }

    public function legacyClothSale(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        return redirect()->route('admin.counter-orders.create')
            ->with('success', 'کپڑے کی فروخت اب مشترکہ آرڈر ڈیسک سے شروع ہوتی ہے۔');
    }

    public function legacyGenericSale(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        return redirect()->route('admin.counter-orders.create')
            ->with('success', 'نئی فروخت اب مشترکہ آرڈر ڈیسک سے شروع ہوتی ہے۔');
    }

    public function importSaleSession(
        Request $request,
        SaleSession $saleSession,
        CounterOrderService $service,
    ): RedirectResponse {
        abort_unless($saleSession->user_id === $request->user()->businessOwnerId(), 404);
        abort_unless($this->canManageCloth($request), 403);
        $order = $service->fromSaleSession($saleSession, $request->user());

        return redirect()->route('admin.counter-orders.edit', $order);
    }

    public function addCloth(Request $request, CounterOrder $counterOrder): RedirectResponse|JsonResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageCloth($request), 403);
        abort_if($counterOrder->isClosed(), 409, 'This order is closed.');
        $validated = $request->validate([
            'cloth_id' => ['required', 'integer'],
            'color' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'length' => ['required', 'numeric', 'gt:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $cloth = Cloth::where('user_id', $request->user()->businessOwnerId())->with('colors')->findOrFail($validated['cloth_id']);
        if ($cloth->hasSelectableColors() && blank($validated['color'] ?? null)) {
            return back()->withErrors(['color' => 'اس کپڑے کے لیے رنگ منتخب کریں۔'])->withInput();
        }
        $lineTotal = round((float) $validated['total_price'], 2);
        $quantity = $cloth->sellsPerSuit() ? (int) ($validated['quantity'] ?? 1) : 1;
        $unitPrice = $cloth->sellsPerSuit()
            ? round($lineTotal / $quantity, 2)
            : round($lineTotal / (float) $validated['length'], 2);
        $item = $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_CLOTH,
            'status' => CounterOrderItem::STATUS_DRAFT,
            'cloth_id' => $cloth->id,
            'quantity' => $quantity,
            'length' => $validated['length'],
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'details' => [
                'color' => $validated['color'] ?? null,
                'sale_price_basis' => $cloth->sale_price_basis,
            ],
            'note' => $validated['note'] ?? null,
        ]);
        $counterOrder->recalculate();

        if ($request->expectsJson()) {
            return $this->rowResponse($request, $counterOrder, $item, 'کپڑا آرڈر میں شامل ہو گیا ہے۔');
        }

        return back()->with('success', 'کپڑا آرڈر میں شامل ہو گیا ہے۔');
    }

    public function addTailoring(
        Request $request,
        CounterOrder $counterOrder,
        MeasurementService $measurements,
    ): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageTailoring($request), 403);
        abort_if($counterOrder->isClosed(), 409, 'This order is closed.');
        $ownerId = $request->user()->businessOwnerId();
        $validated = $request->validate([
            'measurement_profile_id' => ['required', 'integer'],
            'measurement_template_id' => ['required', 'integer', Rule::exists('measurement_templates', 'id')->where('user_id', $ownerId)->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $profile = Customers::where('user_id', $ownerId)->findOrFail($validated['measurement_profile_id']);
        abort_unless((int) ($profile->parent_id ?: $profile->id) === (int) $counterOrder->customer_id, 404);
        $template = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->findOrFail($validated['measurement_template_id']);
        $missing = $measurements->missingRequiredMeasurements($profile, $ownerId, $template);
        if ($missing->isNotEmpty()) {
            return back()->withErrors([
                'measurement_profile_id' => 'پہلے یہ ضروری ناپ مکمل کریں: '.$missing->join('، '),
            ])->withInput();
        }
        $lineTotal = round((int) $validated['quantity'] * (float) $validated['unit_price'], 2);
        $counterOrder->items()->create([
            'type' => CounterOrderItem::TYPE_TAILORING,
            'status' => CounterOrderItem::STATUS_DRAFT,
            'measurement_profile_id' => $profile->id,
            'measurement_template_id' => $template->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'line_total' => $lineTotal,
            'due_date' => $validated['due_date'],
            'details' => [
                'measurement_snapshot' => $measurements->measurementRows($profile, $ownerId, $template)->values()->all(),
            ],
            'note' => $validated['note'] ?? null,
        ]);
        $counterOrder->recalculate();

        return back()->with('success', 'سلائی آرڈر میں شامل ہو گئی ہے۔');
    }

    public function updateItem(
        Request $request,
        CounterOrder $counterOrder,
        CounterOrderItem $item,
        MeasurementService $measurements,
    ): RedirectResponse|JsonResponse {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($item->counter_order_id === $counterOrder->id, 404);
        abort_unless($this->canManageItem($request, $item), 403);
        abort_unless($item->status === CounterOrderItem::STATUS_DRAFT, 409, 'Only unconfirmed items can be edited.');
        abort_if($counterOrder->isClosed(), 409, 'This order is closed.');

        if ($item->type === CounterOrderItem::TYPE_CLOTH) {
            $validated = $request->validate([
                'color' => ['nullable', 'string', 'max:100'],
                'quantity' => ['nullable', 'integer', 'min:1'],
                'length' => ['required', 'numeric', 'gt:0'],
                'total_price' => ['required', 'numeric', 'min:0'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);
            $cloth = Cloth::where('user_id', $request->user()->businessOwnerId())
                ->with('colors')->findOrFail($item->cloth_id);
            if ($cloth->hasSelectableColors() && blank($validated['color'] ?? null)) {
                return back()->withErrors(['color' => 'اس کپڑے کے لیے رنگ منتخب کریں۔'])->withInput();
            }
            if ($cloth->hasSelectableColors()
                && ! in_array($validated['color'] ?? null, $cloth->selectableColorNames(), true)) {
                return back()->withErrors(['color' => 'منتخب رنگ اس کپڑے کے دستیاب رنگوں میں شامل نہیں ہے۔'])->withInput();
            }
            $quantity = $cloth->sellsPerSuit() ? (int) ($validated['quantity'] ?? 1) : 1;
            $lineTotal = round((float) $validated['total_price'], 2);
            $item->update([
                'quantity' => $quantity,
                'length' => $validated['length'],
                'unit_price' => $cloth->sellsPerSuit()
                    ? round($lineTotal / $quantity, 2)
                    : round($lineTotal / (float) $validated['length'], 2),
                'line_total' => $lineTotal,
                'details' => array_merge($item->details ?? [], [
                    'color' => $validated['color'] ?? null,
                    'sale_price_basis' => $cloth->sale_price_basis,
                ]),
                'note' => $validated['note'] ?? null,
            ]);
        } else {
            $ownerId = $request->user()->businessOwnerId();
            $validated = $request->validate([
                'measurement_profile_id' => ['required', 'integer'],
                'measurement_template_id' => ['required', 'integer', Rule::exists('measurement_templates', 'id')->where('user_id', $ownerId)->where('is_active', true)],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'due_date' => ['required', 'date', 'after_or_equal:today'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);
            $profile = Customers::where('user_id', $ownerId)->findOrFail($validated['measurement_profile_id']);
            abort_unless((int) ($profile->parent_id ?: $profile->id) === (int) $counterOrder->customer_id, 404);
            $template = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
                ->findOrFail($validated['measurement_template_id']);
            $missing = $measurements->missingRequiredMeasurements($profile, $ownerId, $template);
            if ($missing->isNotEmpty()) {
                return back()->withErrors([
                    'measurement_profile_id' => 'پہلے یہ ضروری ناپ مکمل کریں: '.$missing->join('، '),
                ])->withInput();
            }
            $item->update([
                'measurement_profile_id' => $profile->id,
                'measurement_template_id' => $template->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'line_total' => round((int) $validated['quantity'] * (float) $validated['unit_price'], 2),
                'due_date' => $validated['due_date'],
                'details' => array_merge($item->details ?? [], [
                    'measurement_snapshot' => $measurements->measurementRows($profile, $ownerId, $template)->values()->all(),
                ]),
                'note' => $validated['note'] ?? null,
            ]);
        }

        $counterOrder->recalculate();

        if ($request->expectsJson()) {
            return $this->rowResponse($request, $counterOrder, $item, 'آئٹم کی تبدیلی محفوظ ہو گئی ہے۔');
        }

        return back()->with('success', 'آئٹم کی تبدیلی محفوظ ہو گئی ہے۔');
    }

    public function removeItem(Request $request, CounterOrder $counterOrder, CounterOrderItem $item): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($item->counter_order_id === $counterOrder->id, 404);
        abort_unless($this->canManageItem($request, $item), 403);
        abort_unless($item->status === CounterOrderItem::STATUS_DRAFT, 409, 'Only unconfirmed items can be removed.');
        $item->delete();
        $counterOrder->recalculate();

        return back()->with('success', 'آئٹم ہٹا دیا گیا ہے۔');
    }

    public function confirm(Request $request, CounterOrder $counterOrder, CounterOrderService $service): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        $draftItems = $counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->get();
        abort_unless($draftItems->every(fn (CounterOrderItem $item) => $this->canManageItem($request, $item)), 403);
        $draftTotal = (float) $counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->sum('line_total');
        $validated = $request->validate([
            'payment' => ['required', 'numeric', 'min:0', 'max:'.$draftTotal],
            'payment_method' => ['required', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);
        if (PaymentMethods::requiresReference($validated['payment_method']) && blank($validated['payment_reference'] ?? null)) {
            return back()->withErrors(['payment_reference' => 'اس ادائیگی کے طریقے کے لیے حوالہ نمبر درج کریں۔'])->withInput();
        }
        $service->confirm(
            $counterOrder,
            $request->user(),
            (float) $validated['payment'],
            $validated['payment_method'],
            $validated['payment_reference'] ?? null,
        );

        return back()->with('success', 'نئے آئٹمز تصدیق ہو گئے ہیں۔ کپڑا اسٹاک سے کم اور سلائی ورکشاپ میں شامل ہو گئی ہے۔');
    }

    public function close(Request $request, CounterOrder $counterOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        $items = $counterOrder->items()->where('status', '!=', CounterOrderItem::STATUS_CANCELLED)->get();
        abort_unless($items->every(fn (CounterOrderItem $item) => $this->canManageItem($request, $item)), 403);
        abort_if($counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->exists(), 409, 'Confirm or remove draft items first.');
        abort_if(! $counterOrder->items()->where('status', CounterOrderItem::STATUS_CONFIRMED)->exists(), 409, 'An empty order cannot be closed.');
        $counterOrder->update(['status' => CounterOrder::STATUS_CLOSED, 'closed_at' => now()]);

        return back()->with('success', 'آرڈر بند کر دیا گیا ہے۔ بعد کی فروخت کے لیے نیا آرڈر بنائیں۔');
    }

    private function customers(Request $request)
    {
        return Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')->selectableForSales()->orderBy('name')->get();
    }

    private function authorizeAnyOrderAccess(Request $request): void
    {
        abort_unless(
            $this->canManageCloth($request) || $this->canManageTailoring($request),
            403
        );
    }

    private function authorizeOrder(Request $request, CounterOrder $order): void
    {
        $this->authorizeAnyOrderAccess($request);
        abort_unless($order->user_id === $request->user()->businessOwnerId(), 404);
    }

    private function canManageItem(Request $request, CounterOrderItem $item): bool
    {
        return $item->type === CounterOrderItem::TYPE_CLOTH
            ? $this->canManageCloth($request)
            : $this->canManageTailoring($request);
    }

    private function canManageCloth(Request $request): bool
    {
        return $request->user()->hasModule(User::MODULE_CLOTHING)
            && $request->user()->hasBusinessPermission(BusinessRole::CLOTHING_SALES);
    }

    private function canManageTailoring(Request $request): bool
    {
        return $request->user()->hasModule(User::MODULE_TAILORING)
            && $request->user()->hasBusinessPermission(BusinessRole::TAILORING_ORDERS);
    }

    private function rowResponse(
        Request $request,
        CounterOrder $counterOrder,
        CounterOrderItem $item,
        string $message,
    ): JsonResponse {
        $counterOrder->refresh()->load([
            'items.cloth.brand', 'items.cloth.type', 'items.cloth.colors',
            'items.measurementProfile', 'items.measurementTemplate',
        ]);
        $item = $counterOrder->items->firstWhere('id', $item->id);
        $draftItems = $counterOrder->items->where('status', CounterOrderItem::STATUS_DRAFT);
        $confirmedItems = $counterOrder->items->where('status', CounterOrderItem::STATUS_CONFIRMED);

        return response()->json([
            'message' => $message,
            'item_id' => $item->id,
            'row_html' => view('counter-orders.partials.item-row', [
                'item' => $item,
                'counterOrder' => $counterOrder,
                'canAddCloth' => $this->canManageCloth($request),
                'canAddTailoring' => $this->canManageTailoring($request),
                'receiptLinks' => collect(),
            ])->render(),
            'summary' => [
                'subtotal' => (float) $counterOrder->subtotal,
                'paid' => (float) $counterOrder->paid_amount,
                'balance' => (float) $counterOrder->balance_amount,
                'draft_count' => $draftItems->count(),
                'confirmed_count' => $confirmedItems->count(),
                'draft_total' => (float) $draftItems->sum('line_total'),
            ],
        ]);
    }
}
