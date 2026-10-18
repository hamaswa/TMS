<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use App\Models\CounterOrderComplaint;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\CounterSaleReceipt;
use App\Models\CustomerMeasurementHistory;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\SaleSession;
use App\Models\Setting;
use App\Models\Tailor;
use App\Models\Tailorsalary;
use App\Models\User;
use App\Services\CounterOrderService;
use App\Services\CounterOrderNumberService;
use App\Services\MeasurementService;
use App\Services\PrintDocumentService;
use App\Support\PaymentMethods;
use App\Rules\PakistanMobileNumber;
use App\Rules\UniqueCustomerPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CounterOrderController extends Controller
{
    public function create(Request $request): View
    {
        $tailors = Tailor::where('user_id', $request->user()->id)->get();
        // dd($tailors);
        $this->authorizeAnyOrderAccess($request);
        $canAddCloth = $this->canManageCloth($request);
        $canAddTailoring = $this->canManageTailoring($request);
        $selectedCustomer = $request->integer('customer')
            ? Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')->selectableForSales()->find($request->integer('customer'))
            : null;
        $customers = $this->customers($request);
        if ($selectedCustomer && ! $customers->contains('id', $selectedCustomer->id)) {
            $customers->prepend($selectedCustomer);
        }
        $selectedProfileId = $selectedCustomer
            ? Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereKey($request->integer('profile', $selectedCustomer->id))
            ->where(fn($query) => $query->whereKey($selectedCustomer->id)->orWhere('parent_id', $selectedCustomer->id))
            ->value('id')
            : null;
        $openOrders = $selectedCustomer
            ? CounterOrder::where('user_id', $request->user()->businessOwnerId())
            ->where('customer_id', $selectedCustomer->id)
            ->where('status', '!=', CounterOrder::STATUS_CLOSED)
            ->withCount([
                'items as draft_items_count' => fn($query) => $query->where('status', CounterOrderItem::STATUS_DRAFT),
                'items as confirmed_items_count' => fn($query) => $query->where('status', CounterOrderItem::STATUS_CONFIRMED),
            ])->latest('updated_at')->get()
            : collect();

        return view('counter-orders.create', compact(
            'customers',
            'selectedCustomer',
            'selectedProfileId',
            'openOrders',
            'canAddCloth',
            'canAddTailoring',
            'tailors'
        ));
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $this->authorizeAnyOrderAccess($request);
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim((string) ($validated['q'] ?? ''));

        $customers = Customers::query()
            ->where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')
            ->selectableForSales()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($match) use ($search, $like) {
                    $match->where('name', 'like', $like)
                        ->orWhere('phone_number1', 'like', $like);
                    if (ctype_digit($search)) {
                        $match->orWhere('serial_number', (int) $search);
                    }
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'name', 'phone_number1', 'serial_number']);

        return response()->json(['customers' => $customers]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $this->authorizeAnyOrderAccess($request);
        $ownerId = $request->user()->businessOwnerId();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', new PakistanMobileNumber, new UniqueCustomerPhone($ownerId)],
        ]);
        $customer = Customers::create([
            'name' => trim($validated['name']),
            'phone_number1' => trim($validated['phone']),
            'user_id' => $ownerId,
            'acquisition_source' => 'counter_order',
        ]);

        return response()->json(['customer' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone_number1' => $customer->phone_number1,
            'serial_number' => $customer->serial_number,
        ]], 201);
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
        $tailors = Tailor::where('user_id', $request->user()->id)->get();
        // dd($tailors);
        $this->authorizeOrder($request, $counterOrder);
        $ownerId = $request->user()->businessOwnerId();
        $counterOrder->load([
            'customer',
            'saleSession.agent',
            'items.cloth.brand',
            'items.cloth.type',
            'items.measurementProfile',
            'items.measurementTemplate',
        ]);
        $canAddCloth = $this->canManageCloth($request);
        $canAddTailoring = $this->canManageTailoring($request);
        $canManageAllDrafts = $counterOrder->items->where('status', CounterOrderItem::STATUS_DRAFT)
            ->every(fn(CounterOrderItem $item) => $this->canManageItem($request, $item));
        $canManageAllItems = $counterOrder->items->where('status', '!=', CounterOrderItem::STATUS_CANCELLED)
            ->every(fn(CounterOrderItem $item) => $this->canManageItem($request, $item));
        $cloths = $canAddCloth ? Cloth::where('user_id', $ownerId)->with(['brand', 'type', 'colors'])->orderBy('id')->get() : collect();
        $profiles = Customers::where('user_id', $ownerId)
            ->where(fn($query) => $query->whereKey($counterOrder->customer_id)->orWhere('parent_id', $counterOrder->customer_id))
            ->orderByRaw('parent_id is not null')->orderBy('name')->get();
        $selectedProfileId = $profiles->contains('id', $request->integer('profile'))
            ? $request->integer('profile')
            : $profiles->first()?->id;
        $templates = $canAddTailoring ? MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('name')->get() : collect();

        $allTemplateIds = $templates->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $profileTemplateIds = $profiles->mapWithKeys(function (Customers $profile) use ($allTemplateIds) {
            return [$profile->id => $allTemplateIds];
        });

        $paymentMethods = PaymentMethods::LABELS;
        $previousDue = app(CounterOrderService::class)->previousDue($counterOrder);
        $receiptLinks = CounterSaleReceipt::where('user_id', $ownerId)
            ->whereIn('id', $counterOrder->items->where('source_record_type', 'counter_sale_receipt')->pluck('source_record_id'))
            ->get()->keyBy('id');

        return view('counter-orders.edit', compact(
            'counterOrder',
            'canAddCloth',
            'canAddTailoring',
            'cloths',
            'profiles',
            'templates',
            'paymentMethods',
            'receiptLinks',
            'selectedProfileId',
            'canManageAllDrafts',
            'canManageAllItems',
            'profileTemplateIds',
            'tailors',
            'previousDue'
        ));
    }

    public function print(
        Request $request,
        CounterOrder $counterOrder,
        PrintDocumentService $documents,
    ): View {
        $this->authorizeOrder($request, $counterOrder);
        $counterOrder->load([
            'customer',
            'items.cloth.brand',
            'items.cloth.type',
            'items.measurementProfile',
            'items.measurementTemplate',
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

        $printConfig = $documents->make($setting, request(), 'tailor-order-copy', $counterOrder->id);
        [$trackingUrl, $trackingQrSvg] = $this->trackingQr($counterOrder, $documents);

        return view('counter-orders.print', compact('counterOrder', 'setting', 'printConfig', 'trackingUrl', 'trackingQrSvg'));
    }

    public function getTailorRates(Request $request, int $tailorId)
    {
        $ownerId = $request->user()->businessOwnerId();

        $tailor = Tailor::where('user_id', $ownerId)
            ->findOrFail($tailorId);

        $rates = $tailor->tailorsalary()
            ->with('options')
            ->get();

        return response()->json(
            $rates->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'rateName' => $rate->options->Name
                        ?? $rate->type
                        ?? 'سلائی',
                    'price' => (float) $rate->price,
                    'rateValue' => $rate->id . '-' . $rate->price,
                ];
            })->values()
        );
    }

    public function printItem(
        Request $request,
        CounterOrder $counterOrder,
        CounterOrderItem $item,
        PrintDocumentService $documents,
    ): View|RedirectResponse {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless((int) $item->counter_order_id === (int) $counterOrder->id, 404);
        abort_unless($item->status === CounterOrderItem::STATUS_CONFIRMED, 404);

        if ($item->type === CounterOrderItem::TYPE_TAILORING) {
            abort_unless($this->canManageTailoring($request), 403);
            abort_unless($item->source_record_type === 'tailoring_order' && $item->source_record_id, 404);

            return redirect()->route('admin.order-prints', ['id' => $item->source_record_id]);
        }

        $counterOrder->load(['customer']);
        $item->load(['cloth.brand', 'cloth.type', 'measurementProfile', 'measurementTemplate']);
        $setting = Setting::ensureDefaultFor($request->user());
        $printConfig = $documents->make(
            $setting,
            $request,
            'counter-order-item',
            $item->item_serial ?: $counterOrder->reference . '-' . $item->id,
        );
        $singleItem = $item;
        [$trackingUrl, $trackingQrSvg] = $this->trackingQr($counterOrder, $documents);

        return view('counter-orders.print', compact(
            'counterOrder',
            'setting',
            'printConfig',
            'singleItem',
            'trackingUrl',
            'trackingQrSvg'
        ));
    }

    /**
     * Signed order-tracking link and its QR code, shown on printed receipts.
     */
    private function trackingQr(CounterOrder $counterOrder, PrintDocumentService $documents): array
    {
        $trackingUrl = \Illuminate\Support\Facades\URL::signedRoute('orders.track', ['order' => $counterOrder->id]);

        return [$trackingUrl, $documents->qrSvg($trackingUrl, 180)];
    }

    public function editMeasurements(
        Request $request,
        CounterOrder $counterOrder,
        Customers $profile,
        MeasurementService $measurements,
    ): View {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageTailoring($request), 403);
        abort_if($counterOrder->isClosed(), 409, 'This order is closed.');

        $ownerId = $request->user()->businessOwnerId();
        $this->authorizeMeasurementProfile($profile, $counterOrder, $ownerId);
        $template = MeasurementTemplate::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with('templateOptions.optionType')
            ->findOrFail($request->integer('measurement_template_id'));
        // abort_unless($this->profileHasSavedTemplate($profile, $template, $ownerId), 404);
        $fields = $measurements->fieldsForTemplate($measurements->activeFields($ownerId), $template);
        $savedRows = $measurements->savedMeasurementRows($profile, $ownerId, $template);
        $savedValues = $savedRows->pluck('value', 'source_key');
        $systemValues = collect($template->system_fields ?? [])
            ->mapWithKeys(fn($key) => [$key => $savedValues->get('system.' . $key)]);
        $customValues = $fields->mapWithKeys(fn($field) => [
            $field->id => $savedValues->get('custom.' . $field->id),
        ]);
        $preferenceChoices = $template->templateOptions
            ->groupBy(fn($option) => $option->optionType?->type === 'daaman' ? 'Daaman' : $option->optionType?->type)
            ->map(fn($options) => $options->pluck('Name')->values())
            ->filter(fn($options, $key) => filled($key));
        $layout = collect($template->field_layout ?? [])->keyBy('source');

        return view('counter-orders.partials.measurement-form', compact(
            'counterOrder',
            'profile',
            'template',
            'fields',
            'customValues',
            'preferenceChoices',
            'layout',
            'systemValues'
        ));
    }

    public function updateMeasurements(
        Request $request,
        CounterOrder $counterOrder,
        Customers $profile,
        MeasurementService $measurements,
    ): JsonResponse {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageTailoring($request), 403);
        abort_if($counterOrder->isClosed(), 409, 'This order is closed.');

        $ownerId = $request->user()->businessOwnerId();

        $this->authorizeMeasurementProfile($profile, $counterOrder, $ownerId);

        $template = MeasurementTemplate::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with('templateOptions.optionType')
            ->findOrFail($request->integer('measurement_template_id'));

        // Do not require this template to be the customer's permanent template.
        $fields = $measurements->fieldsForTemplate(
            $measurements->activeFields($ownerId),
            $template,
        );

        $preferenceChoices = $template->templateOptions
            ->groupBy(
                fn($option) => $option->optionType?->type === 'daaman'
                    ? 'Daaman'
                    : $option->optionType?->type
            )
            ->map(fn($options) => $options->pluck('Name')->values())
            ->filter(fn($options, $key) => filled($key));

        $rules = [
            'measurement_template_id' => [
                'required',
                'integer',
                Rule::in([$template->id]),
            ],
            'system_measurements' => ['nullable', 'array'],
        ];

        foreach ($template->system_fields ?? [] as $key) {
            if (! isset(MeasurementService::SYSTEM_FIELDS[$key])) {
                continue;
            }

            $meta = MeasurementService::SYSTEM_FIELDS[$key];

            $rules['system_measurements.' . $key] = $meta['unit'] === 'inch'
                ? ['required', 'numeric', 'min:0']
                : array_values(array_filter([
                    'required',
                    'string',
                    'max:255',
                    $preferenceChoices->has($key)
                        ? Rule::in($preferenceChoices->get($key)->all())
                        : null,
                ]));
        }

        $validated = $request->validate(
            array_merge($rules, $measurements->rules($fields)),
            [],
            $measurements->attributes($fields),
        );

        $systemValues = $validated['system_measurements'] ?? [];
        $customValues = $validated['custom_measurements'] ?? [];

        // Build rows specifically for the selected template.
        // Do not write these values to the customer's shared measurement columns.
        $rows = collect();
        $sortOrder = 0;

        foreach (MeasurementService::SYSTEM_FIELDS as $key => $meta) {
            if (
                ! in_array($key, $template->system_fields ?? [], true)
                || ! array_key_exists($key, $systemValues)
            ) {
                continue;
            }

            $value = $systemValues[$key];

            if ($value === null || $value === '') {
                continue;
            }

            $rows->push([
                'measurement_field_id' => null,
                'source_key' => 'system.' . $key,
                'label' => $meta['label'],
                'value' => (string) $value,
                'unit' => $meta['unit'],
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($fields as $field) {
            $value = $customValues[$field->id] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $rows->push([
                'measurement_field_id' => $field->id,
                'source_key' => 'custom.' . $field->id,
                'label' => $field->label,
                'value' => (string) $value,
                'unit' => $field->unit,
                'sort_order' => 1000 + (int) $field->sort_order,
            ]);
        }

        $previousRows = $measurements->savedMeasurementRows(
            $profile,
            $ownerId,
            $template,
        );

        $previousFingerprint = $measurements->measurementFingerprint(
            $previousRows,
            $template,
        );

        $currentFingerprint = $measurements->measurementFingerprint(
            $rows,
            $template,
        );

        $changed = ! hash_equals($previousFingerprint, $currentFingerprint);

        if ($changed) {
            $measurements->recordHistoryRows(
                $profile,
                $rows,
                $template,
                $request->user()->id,
                'order_update',
            );
        }

        // Update only draft items for this person and this template.
        // Existing orders and other templates remain untouched.
        $snapshot = $rows->values()->all();

        $counterOrder->items()
            ->where('type', CounterOrderItem::TYPE_TAILORING)
            ->where('status', CounterOrderItem::STATUS_DRAFT)
            ->where('measurement_profile_id', $profile->id)
            ->where('measurement_template_id', $template->id)
            ->get()
            ->each(function (CounterOrderItem $item) use ($snapshot): void {
                $details = $item->details ?? [];
                $details['measurement_snapshot'] = $snapshot;

                $item->update(['details' => $details]);
            });

        return response()->json([
            'message' => $changed
                ? 'پیمائش محفوظ ہو گئی ہے۔'
                : 'پیمائش میں کوئی تبدیلی نہیں تھی۔',
            'profile_name' => $profile->name,
            'template_name' => $template->name,
        ]);
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

    public function addCloth(
        Request $request,
        CounterOrder $counterOrder,
        CounterOrderNumberService $numbers,
    ): RedirectResponse|JsonResponse {
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
        $this->ensureClothStock($cloth, $validated['color'] ?? null, (float) $validated['length'], $quantity);
        $unitPrice = $cloth->sellsPerSuit()
            ? round($lineTotal / $quantity, 2)
            : round($lineTotal / (float) $validated['length'], 2);
        $item = $numbers->createItem($counterOrder, [
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
        CounterOrderNumberService $numbers,
    ): RedirectResponse {
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
            'tailor_id' => ['nullable', 'integer'],
            'tailor_price' => ['nullable', 'regex:/^\d+-.+$/', 'max:255'],
        ]);

        $profile = Customers::where('user_id', $ownerId)->findOrFail($validated['measurement_profile_id']);

        abort_unless((int) ($profile->parent_id ?: $profile->id) === (int) $counterOrder->customer_id, 404);

        $template = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->findOrFail($validated['measurement_template_id']);
        // abort_unless($this->profileHasSavedTemplate($profile, $template, $ownerId), 422, 'Selected measurements do not belong to this person.');

        $missing = $measurements->missingRequiredMeasurements($profile, $ownerId, $template);
        if ($missing->isNotEmpty()) {
            return back()->withErrors([
                'measurement_profile_id' => 'پہلے یہ ضروری ناپ مکمل کریں: ' . $missing->join('، '),
            ])->withInput();
        }
        $lineTotal = round((int) $validated['quantity'] * (float) $validated['unit_price'], 2);


        $tailor = null;
        $rate = null;

        if (!empty($validated['tailor_id']) && !empty($validated['tailor_price'])) {
            $tailor = Tailor::where('user_id', $ownerId)
                ->findOrFail($validated['tailor_id']);

            [$rateId] = explode('-', $validated['tailor_price'], 2);

            $rate = Tailorsalary::where('tailor_id', $tailor->id)
                ->findOrFail((int) $rateId);
        }

        $numbers->createItem($counterOrder, [
            'type' => CounterOrderItem::TYPE_TAILORING,
            'status' => CounterOrderItem::STATUS_DRAFT,
            'measurement_profile_id' => $profile->id,
            'measurement_template_id' => $template->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'line_total' => $lineTotal,
            'due_date' => $validated['due_date'],
            'details' => [
                'measurement_snapshot' => $measurements->savedMeasurementRows($profile, $ownerId, $template)->values()->all(),
            ],
            'tailor_id' => $tailor->id ??  null,
            'rate_id' => $rate->id ?? null,
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
            if (
                $cloth->hasSelectableColors()
                && ! in_array($validated['color'] ?? null, $cloth->selectableColorNames(), true)
            ) {
                return back()->withErrors(['color' => 'منتخب رنگ اس کپڑے کے دستیاب رنگوں میں شامل نہیں ہے۔'])->withInput();
            }
            $quantity = $cloth->sellsPerSuit() ? (int) ($validated['quantity'] ?? 1) : 1;
            $this->ensureClothStock($cloth, $validated['color'] ?? null, (float) $validated['length'], $quantity);
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
            $hasExistingPair = (int) $item->measurement_profile_id === (int) $profile->id
                && (int) $item->measurement_template_id === (int) $template->id;
            abort_unless($hasExistingPair || $this->profileHasSavedTemplate($profile, $template, $ownerId), 422, 'Selected measurements do not belong to this person.');
            $measurementSnapshot = $measurements->savedMeasurementRows($profile, $ownerId, $template);
            if ($hasExistingPair && $measurementSnapshot->isEmpty()) {
                $measurementSnapshot = collect(data_get($item->details, 'measurement_snapshot', []));
                if ($measurementSnapshot->isEmpty()) {
                    $measurementSnapshot = $measurements->measurementRows($profile, $ownerId, $template);
                }
            }
            $missing = $measurements->missingRequiredMeasurementsFromRows($measurementSnapshot, $ownerId, $template);
            if ($missing->isNotEmpty()) {
                return back()->withErrors([
                    'measurement_profile_id' => 'پہلے یہ ضروری ناپ مکمل کریں: ' . $missing->join('، '),
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
                    'measurement_snapshot' => $measurementSnapshot->values()->all(),
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
        abort_unless($draftItems->every(fn(CounterOrderItem $item) => $this->canManageItem($request, $item)), 403);
        $draftTotal = (float) $counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->sum('line_total');
        // The customer may also clear the previous unpaid balance of this order.
        $maxPayment = round($draftTotal + $service->previousDue($counterOrder), 2);
        $validated = $request->validate([
            'payment' => ['required', 'numeric', 'min:0', 'max:' . $maxPayment],
            'payment_method' => ['required', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ], [
            'payment.max' => 'وصول رقم Rs. '.number_format($maxPayment, 2).' (نئے آئٹمز اور پچھلا بقایا) سے زیادہ نہیں ہو سکتی۔',
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


    public function storeComplaint(Request $request, CounterOrder $counterOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageTailoring($request), 403);
        $validated = $request->validate([
            'counter_order_item_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'requested_tailoring_refund' => ['required', 'numeric', 'min:0'],
            'requested_cloth_compensation' => ['required', 'numeric', 'min:0'],
        ]);
        $item = $counterOrder->items()->whereKey($validated['counter_order_item_id'])
            ->where('type', CounterOrderItem::TYPE_TAILORING)
            ->where('status', CounterOrderItem::STATUS_CONFIRMED)->firstOrFail();
        if ((float) $validated['requested_tailoring_refund'] > (float) $item->line_total) {
            throw ValidationException::withMessages(['requested_tailoring_refund' => 'سلائی کی واپسی اصل سلائی قیمت سے زیادہ نہیں ہو سکتی۔']);
        }
        if ((float) $validated['requested_tailoring_refund'] + (float) $validated['requested_cloth_compensation'] <= 0) {
            throw ValidationException::withMessages(['requested_tailoring_refund' => 'واپسی یا کپڑے کے نقصان کی رقم درج کریں۔']);
        }
        CounterOrderComplaint::create([
            'counter_order_id' => $counterOrder->id,
            'counter_order_item_id' => $item->id,
            'user_id' => $request->user()->businessOwnerId(),
            'created_by_user_id' => $request->user()->id,
            'reason' => $validated['reason'],
            'requested_tailoring_refund' => $validated['requested_tailoring_refund'],
            'requested_cloth_compensation' => $validated['requested_cloth_compensation'],
            'status' => 'pending',
        ]);
        return back()->with('success', 'شکایت اور مجوزہ رقم محفوظ ہو گئی ہے۔ ابھی کوئی ادائیگی یا کھاتے میں تبدیلی نہیں ہوئی۔');
    }


    public function settleComplaint(Request $request, CounterOrder $counterOrder, CounterOrderComplaint $complaint): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        abort_unless($this->canManageTailoring($request), 403);
        $validated = $request->validate([
            'approved_tailoring_refund' => ['required', 'numeric', 'min:0'],
            'approved_cloth_compensation' => ['required', 'numeric', 'min:0'],
            'actual_cash_payout' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($request, $counterOrder, $complaint, $validated) {
            $locked = CounterOrderComplaint::where('user_id', $request->user()->businessOwnerId())
                ->where('counter_order_id', $counterOrder->id)
                ->lockForUpdate()->findOrFail($complaint->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['complaint' => 'یہ شکایت پہلے ہی نمٹائی جا چکی ہے۔']);
            }
            $item = $counterOrder->items()->whereKey($locked->counter_order_item_id)
                ->where('type', CounterOrderItem::TYPE_TAILORING)
                ->where('status', CounterOrderItem::STATUS_CONFIRMED)->firstOrFail();
            $refund = round((float) $validated['approved_tailoring_refund'], 2);
            $compensation = round((float) $validated['approved_cloth_compensation'], 2);
            $payout = round((float) $validated['actual_cash_payout'], 2);
            $alreadyRefunded = (float) CounterOrderComplaint::where('user_id', $locked->user_id)
                ->where('counter_order_item_id', $item->id)
                ->where('status', 'settled')->sum('approved_tailoring_refund');
            if ($refund > round((float) $item->line_total - $alreadyRefunded, 2)) {
                throw ValidationException::withMessages(['approved_tailoring_refund' => 'اس آئٹم کی اصل سلائی قیمت سے زیادہ رقم واپس نہیں کی جا سکتی۔']);
            }
            $total = round($refund + $compensation, 2);
            if ($total <= 0 || $payout > $total) {
                throw ValidationException::withMessages(['actual_cash_payout' => 'ادا کی گئی رقم منظور شدہ رقم سے زیادہ نہیں ہو سکتی۔']);
            }
            if ($payout > 0 && blank($validated['payment_method'] ?? null)) {
                throw ValidationException::withMessages(['payment_method' => 'رقم واپسی کا طریقہ منتخب کریں۔']);
            }
            if ($payout > 0 && PaymentMethods::requiresReference($validated['payment_method']) && blank($validated['payment_reference'] ?? null)) {
                throw ValidationException::withMessages(['payment_reference' => 'ادائیگی کا حوالہ درج کریں۔']);
            }
            Transaction::create([
                'userId' => $locked->user_id,
                'customerId' => $counterOrder->customer_id,
                'orderId' => $item->source_record_type === 'tailoring_order' ? $item->source_record_id : null,
                'Order_type' => 'Tailor Complaint Settlement',
                'remainingBalance' => round(-$total + $payout, 2),
                'recivedPayment' => -$payout,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_on' => now()->toDateString(),
                'comment' => 'سلائی شکایت #'.$locked->id.' · سلائی واپسی '.$refund.' · کپڑے کا ہرجانہ '.$compensation.' · ادا '.$payout.' · باقی واجب الادا '.round($total - $payout, 2),
            ]);
            $locked->update([
                'status' => 'settled',
                'approved_tailoring_refund' => $refund,
                'approved_cloth_compensation' => $compensation,
                'actual_cash_payout' => $payout,
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'settled_by_user_id' => $request->user()->id,
                'settled_at' => now(),
            ]);
        });
        return back()->with('success', 'شکایت کا تصفیہ اور کھاتے کی انٹری محفوظ ہو گئی ہے۔');
    }

    public function close(Request $request, CounterOrder $counterOrder): RedirectResponse
    {
        $this->authorizeOrder($request, $counterOrder);
        $items = $counterOrder->items()->where('status', '!=', CounterOrderItem::STATUS_CANCELLED)->get();
        abort_unless($items->every(fn(CounterOrderItem $item) => $this->canManageItem($request, $item)), 403);
        abort_if($counterOrder->items()->where('status', CounterOrderItem::STATUS_DRAFT)->exists(), 409, 'Confirm or remove draft items first.');
        abort_if(! $counterOrder->items()->where('status', CounterOrderItem::STATUS_CONFIRMED)->exists(), 409, 'An empty order cannot be closed.');
        $counterOrder->update(['status' => CounterOrder::STATUS_CLOSED, 'closed_at' => now()]);

        return back()->with('success', 'آرڈر بند کر دیا گیا ہے۔ بعد کی فروخت کے لیے نیا آرڈر بنائیں۔');
    }

    private function customers(Request $request)
    {
        return Customers::where('user_id', $request->user()->businessOwnerId())
            ->whereNull('parent_id')->selectableForSales()->orderByDesc('updated_at')->orderByDesc('id')->limit(20)->get();
    }

    /**
     * Stock on hand for a cloth, read from the same stock row the sale draws from
     * at confirmation: the chosen color for per-color cloths, otherwise the first row.
     */
    private function clothStock(Cloth $cloth, ?string $color): float
    {
        $row = $cloth->tracksColors()
            ? $cloth->colors->firstWhere('color', trim((string) $color))
            : $cloth->colors->sortBy('id')->first();

        return (float) ($row?->length ?? 0);
    }

    private function ensureClothStock(Cloth $cloth, ?string $color, float $length, int $quantity): void
    {
        $needed = round($cloth->sellsPerSuit() ? $length * $quantity : $length, 2);
        $available = round($this->clothStock($cloth, $color), 2);

        if ($needed > $available) {
            throw ValidationException::withMessages([
                'length' => $available <= 0
                    ? 'اس کپڑے کا اسٹاک ختم ہے۔'
                    : 'اسٹاک میں صرف '.number_format($available, 2).' میٹر دستیاب ہے؛ '.number_format($needed, 2).' میٹر درکار ہیں۔',
            ]);
        }
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

    private function authorizeMeasurementProfile(
        Customers $profile,
        CounterOrder $counterOrder,
        int $ownerId,
    ): void {
        abort_unless($profile->user_id === $ownerId, 404);
        abort_unless(
            (int) ($profile->parent_id ?: $profile->id) === (int) $counterOrder->customer_id,
            404,
        );
    }

    private function profileHasSavedTemplate(
        Customers $profile,
        MeasurementTemplate $template,
        int $ownerId,
    ): bool {
        if ((int) $profile->measurement_template_id === (int) $template->id) {
            return true;
        }

        return CustomerMeasurementHistory::query()
            ->where('user_id', $ownerId)
            ->where('customer_id', $profile->id)
            ->where('measurement_template_id', $template->id)
            ->exists();
    }

    private function rowResponse(
        Request $request,
        CounterOrder $counterOrder,
        CounterOrderItem $item,
        string $message,
    ): JsonResponse {
        $counterOrder->refresh()->load([
            'items.cloth.brand',
            'items.cloth.type',
            'items.cloth.colors',
            'items.measurementProfile',
            'items.measurementTemplate',
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
                'previous_due' => app(CounterOrderService::class)->previousDue($counterOrder),
            ],
        ]);
    }
}
