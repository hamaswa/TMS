<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\rack;
use App\Models\SaleStock;
use App\Models\Tailor;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\PakistanMobileNumber;
use App\Rules\UniqueCustomerPhone;
use App\Services\MeasurementService;
use App\Services\CustomerLedgerService;
use App\Support\PakistanPhoneNumber;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(
        private MeasurementService $measurements,
        private CustomerLedgerService $customerLedger
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function test()
    {
        try {
            $customers = Customers::all();

            return response()->json(['customer' => $customers]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $user = Auth::user();
        $canViewBalances = $user->hasBusinessPermission(BusinessRole::CUSTOMER_BALANCES);
        $canCreateTailoringOrder = $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS)
            || $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES);
        $canManageMeasurements = $user->hasModule(User::MODULE_TAILORING)
            && $user->hasBusinessPermission(BusinessRole::TAILORING_CUSTOMERS);
        $canCreateBasicCustomer = $user->hasModule(User::MODULE_CLOTHING)
            && $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES);
        $canViewTailoringOrders = $user->hasModule(User::MODULE_TAILORING)
            && $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS);
        $customers = $this->customerDirectoryQuery((string) request('search', ''))
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->hydrateCustomerDirectory($customers, $canViewBalances);
        $stats = $this->customerDirectoryStats($canViewBalances);
        $createdCustomer = request()->filled('created')
            ? Customers::where('user_id', $user->businessOwnerId())->find(request()->integer('created'))
            : null;

        return view('customer.list', array_merge(
            compact(
                'customers',
                'canViewBalances',
                'canCreateTailoringOrder',
                'canManageMeasurements',
                'canCreateBasicCustomer',
                'canViewTailoringOrders',
                'createdCustomer'
            ),
            $stats,
        ));
    }

    public function searchDirectory(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $user = Auth::user();
        $canViewBalances = $user->hasBusinessPermission(BusinessRole::CUSTOMER_BALANCES);
        $canCreateTailoringOrder = $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS)
            || $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES);
        $canManageMeasurements = $user->hasModule(User::MODULE_TAILORING)
            && $user->hasBusinessPermission(BusinessRole::TAILORING_CUSTOMERS);
        $canViewTailoringOrders = $user->hasModule(User::MODULE_TAILORING)
            && $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS);
        $customers = $this->customerDirectoryQuery((string) ($validated['search'] ?? ''))
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->hydrateCustomerDirectory($customers, $canViewBalances);

        return response()->json([
            'html' => view('customer.partials.directory-rows', compact(
                'customers',
                'canViewBalances',
                'canCreateTailoringOrder',
                'canManageMeasurements',
                'canViewTailoringOrders'
            ))->render(),
            'count' => $customers->total(),
            'from' => $customers->firstItem(),
            'to' => $customers->lastItem(),
            'pagination' => $customers->links()->render(),
        ]);
    }

    public function accounts()
    {
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())
            ->whereNull('parent_id')
            ->withSum([
                'transactions as current_balance' => fn($transactions) => $transactions
                    ->where('userId', Auth::user()->businessOwnerId()),
            ], 'remainingBalance')
            ->orderBy('name')
            ->get();

        return view('customer.accounts', compact('customers'));
    }

    private function optionChoicesFor($optionTypes)
    {
        return DB::table('options')
            ->where('user_id', Auth::user()->businessOwnerId())
            ->whereIn('option_id', $optionTypes->pluck('option_id'))
            ->get()
            ->groupBy('option_id');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $data['optionTypes'] = OptionType::query()
            ->whereIn('type', ['necktype', 'sleeve', 'daaman', 'jeab', 'swingtype', 'button', 'plate_type'])
            ->select('id as option_id', 'Name as otn', 'type', 'slug')
            ->orderBy('id')
            ->get();

        $optionTypes   = $data['optionTypes'];
        $optionChoices = $this->optionChoicesFor($optionTypes);

        $data['Tailors'] = Tailor::where('user_id', Auth::user()->businessOwnerId())->get();
        $measurementFields    = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $measurementValues    = collect();
        $measurementTemplates = $this->measurementTemplates();
        $parentCustomer = null;

        if (request()->filled('parent')) {
            $parentCustomer = Customers::where('user_id', Auth::user()->businessOwnerId())
                ->whereNull('parent_id')
                ->findOrFail(request()->integer('parent'));
        }

        return view('customer.create', compact(
            'data',
            'optionTypes',
            'optionChoices',
            'measurementFields',
            'measurementValues',
            'measurementTemplates',
            'parentCustomer'
        ));
    }

    public function statement($id)
    {
        $customer = $this->ownedCustomer($id);
        if ($customer->parent_id !== null) {
            $customer = $this->ownedCustomer($customer->parent_id);
        }
        $user = Auth::user();
        $ownerId = $user->businessOwnerId();
        $tailoringEnabled = (bool) ($user->business?->tailoring_enabled ?? $user->tailoring_access);
        $clothingEnabled = (bool) ($user->business?->clothing_enabled ?? $user->clothing_access);
        $canViewBalances = $user->hasBusinessPermission(BusinessRole::CUSTOMER_BALANCES);
        $canViewTailoring = $tailoringEnabled && $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS);
        $canViewShop = $clothingEnabled && $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES);
        $canManageMeasurements = $tailoringEnabled && $user->hasBusinessPermission(BusinessRole::TAILORING_CUSTOMERS);
        $baseTransactions = Transaction::where('userId', $user->businessOwnerId())->where('customerId', $customer->id);
        $totalBalance = $canViewBalances ? (float) (clone $baseTransactions)->sum('remainingBalance') : null;
        $totalReceived = $canViewBalances ? (float) (clone $baseTransactions)->sum('recivedPayment') : null;
        $visibleTypes = array_values(array_filter([
            $canViewTailoring ? 'Tailor' : null,
            $canViewShop ? 'Sale' : null,
            $canViewShop ? 'Sale Cancellation' : null,
            $canViewBalances ? 'Payment' : null,
        ]));
        $transactions = $canViewBalances && $visibleTypes
            ? (clone $baseTransactions)->whereIn('Order_type', $visibleTypes)->latest()->paginate(20, ['*'], 'ledger_page')->withQueryString()
            : null;
        $orders = $canViewTailoring
            ? $customer->orders()->where('userId', $ownerId)
            ->with(['tailor:id,name', 'customers:id,name,serial_number', 'measurementTemplate:id,name'])
            ->withSum(['transactions as outstanding_amount' => fn($query) => $query->where('userId', $ownerId)], 'remainingBalance')
            ->latest()->limit(50)->get()
            : collect();
        $sales = collect();
        if ($canViewShop) {
            $legacySales = $customer->sales()
                ->where('user_id', $ownerId)
                ->with(['detail:id,sale_id,product_name,quantity,price', 'transaction:id,sale_id,recivedPayment,remainingBalance'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn($sale) => (object) [
                    'id' => $sale->id,
                    'source' => 'legacy',
                    'display_number' => '#' . $sale->id,
                    'counter_order_id' => null,
                    'created_at' => $sale->created_at,
                    'items_count' => $sale->detail->sum('quantity'),
                    'summary' => $sale->detail->pluck('product_name')->filter()->take(3)->implode('، '),
                    'amount' => $sale->detail->sum(fn($detail) => (float) $detail->price * (int) $detail->quantity),
                    'received' => (float) ($sale->transaction?->recivedPayment ?? 0),
                    'balance' => (float) ($sale->transaction?->remainingBalance ?? 0),
                    'status' => $sale->status,
                    'cancellation_reason' => $sale->cancellation_reason,
                ]);
            $stockGroups = SaleStock::where('user_id', $ownerId)
                ->where('c_id', $customer->id)
                ->with(['type:id,name', 'brand:id,name', 'receipt'])
                ->latest()
                ->limit(200)
                ->get()
                ->groupBy(fn($sale) => $sale->counter_sale_receipt_id
                    ? 'receipt-' . $sale->counter_sale_receipt_id
                    : 'legacy-' . $sale->created_at?->format('Y-m-d H:i:s'));
            $stockTransactions = Transaction::where('userId', $ownerId)
                ->where('Order_type', 'Sale')
                ->whereIn('sale_id', $stockGroups->map(fn($items) => $items->first()->id))
                ->get()->keyBy('sale_id');
            $counterOrderLinks = CounterOrderItem::query()
                ->where('source_record_type', 'counter_sale_receipt')
                ->whereIn('source_record_id', $stockGroups->map(fn($items) => $items->first()->counter_sale_receipt_id)->filter())
                ->whereHas('counterOrder', fn($query) => $query->where('user_id', $ownerId))
                ->with('counterOrder:id,reference,serial_number')
                ->get()
                ->keyBy('source_record_id');
            $stockSales = $stockGroups
                ->map(function ($items) use ($stockTransactions, $counterOrderLinks) {
                    $first = $items->first();
                    $transaction = $stockTransactions->get($first->id);
                    $counterOrder = $counterOrderLinks->get($first->counter_sale_receipt_id)?->counterOrder;

                    return (object) [
                        'id' => $first->id,
                        'source' => 'stock',
                        'display_number' => $counterOrder?->displayNumber()
                            ?: ($first->receipt?->receipt_number ?: '#' . $first->id),
                        'counter_order_id' => $counterOrder?->id,
                        'created_at' => $first->created_at,
                        'items_count' => $items->count(),
                        'summary' => $items->map(fn($item) => collect([$item->brand?->name, $item->type?->name, $item->color])->filter()->implode(' '))->filter()->take(3)->implode('، '),
                        'amount' => $items->sum(fn($item) => (float) $item->selling_price * (float) $item->length),
                        'received' => (float) ($transaction?->recivedPayment ?? 0),
                        'balance' => (float) ($transaction?->remainingBalance ?? 0),
                        'status' => $first->receipt?->status ?? 'completed',
                        'cancellation_reason' => $first->receipt?->cancellation_reason,
                    ];
                })
                ->values();
            $sales = $legacySales->concat($stockSales)->sortByDesc('created_at')->take(50)->values();
        }
        if ($transactions) {
            $saleDisplayNumbers = $sales->keyBy(fn($sale) => (string) $sale->id);
            $transactions->getCollection()->each(function ($transaction) use ($saleDisplayNumbers) {
                if ($transaction->sale_id) {
                    $transaction->setAttribute(
                        'sale_display_number',
                        $saleDisplayNumbers->get((string) $transaction->sale_id)?->display_number
                    );
                }
            });
        }

        $systemMeasurements = collect();
        $customMeasurements = collect();
        $measurementHistories = collect();
        $measurementProfiles = collect([$customer]);
        $measurementProfile = $customer;
        if ($canManageMeasurements) {
            $measurementProfiles = Customers::where('user_id', $ownerId)
                ->where(function ($query) use ($customer) {
                    $query->whereKey($customer->id)->orWhere('parent_id', $customer->id);
                })
                ->with('measurementTemplate:id,name')
                ->withCount(['measurementValues', 'measurementHistories'])
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$customer->id])
                ->orderBy('name')
                ->get();
            $requestedProfileId = request()->integer('profile');
            $measurementProfile = $measurementProfiles->firstWhere('id', $requestedProfileId) ?? $customer;
            $systemMeasurements = collect(MeasurementService::SYSTEM_FIELDS)
                ->map(fn(array $meta, string $key) => [
                    'label' => $meta['label'],
                    'value' => $measurementProfile->{$key},
                    'unit' => $meta['unit'],
                ])->filter(fn(array $measurement) => $measurement['value'] !== null
                    && $measurement['value'] !== ''
                    && ! ($measurement['unit'] === '' && (string) $measurement['value'] === '0'));
            $customMeasurements = $measurementProfile->measurementValues()
                ->with('field')
                ->whereHas('field', fn($query) => $query->where('user_id', $ownerId)->where('is_active', true))
                ->get()->sortBy(fn($value) => [$value->field->sort_order, $value->field->label]);
            $measurementHistories = $measurementProfile->measurementHistories()
                ->with(['template:id,name', 'recorder:id,name', 'values'])
                ->limit(12)->get();
            $preferenceSourceKeys = collect(['necktype', 'sleeve', 'Daaman', 'jeab', 'swingtype', 'button', 'plate_type'])
                ->map(fn(string $key) => 'system.' . $key);
            $measurementHistories->each(function ($history) use ($preferenceSourceKeys) {
                $history->setRelation('values', $history->values
                    ->reject(fn($value) => $preferenceSourceKeys->contains($value->source_key)
                        && in_array(strtolower(trim((string) $value->value)), ['', '0', 'none', 'null'], true))
                    ->values());
            });
        }
        $hasTailoringActivity = $orders->isNotEmpty()
            || $measurementProfiles->count() > 1
            || $systemMeasurements->isNotEmpty()
            || $customMeasurements->isNotEmpty()
            || $measurementHistories->isNotEmpty();
        $isClothOnlyCustomer = $canViewShop && ! $hasTailoringActivity;

        $tabs = collect([
            'overview' => ['label' => 'خلاصہ', 'icon' => 'fa-th-large'],
            'transactions' => $canViewBalances ? ['label' => 'کھاتہ اور ادائیگیاں', 'icon' => 'fa-receipt'] : null,
            'tailoring' => $canViewTailoring && $hasTailoringActivity ? ['label' => 'ٹیلرنگ آرڈرز', 'icon' => 'fa-cut'] : null,
            'shop' => $canViewShop ? ['label' => 'کپڑے کی خریداری', 'icon' => 'fa-shopping-bag'] : null,
            'measurements' => $canManageMeasurements && $hasTailoringActivity ? ['label' => 'پیمائش', 'icon' => 'fa-ruler-combined'] : null,
            'profile' => ['label' => 'ذاتی معلومات', 'icon' => 'fa-user'],
        ])->filter();
        $activeTab = request()->string('tab')->toString();
        $activeTab = $tabs->has($activeTab) ? $activeTab : 'overview';
        $paymentRoute = $canViewBalances ? route('admin.customer-payments.store') : null;

        return view('customer.statement', compact(
            'customer',
            'totalBalance',
            'transactions',
            'orders',
            'sales',
            'canViewBalances',
            'canViewTailoring',
            'canViewShop',
            'canManageMeasurements',
            'totalReceived',
            'systemMeasurements',
            'customMeasurements',
            'measurementHistories',
            'measurementProfiles',
            'measurementProfile',
            'tabs',
            'activeTab',
            'paymentRoute',
            'hasTailoringActivity',
            'isClothOnlyCustomer'
        ));
    }

    public function show($id)
    {
        $this->ownedCustomer($id);

        return redirect()->route('admin.customers.statement', $id);
    }

    /**
     * Store a newly created customer.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $basicSave = $request->boolean('save_basic_customer');

        $parentCustomer = null;

        if ($request->filled('parent_customer_id')) {
            $request->validate([
                'parent_customer_id' => ['integer'],
            ]);

            $parentCustomer = Customers::where(
                'user_id',
                Auth::user()->businessOwnerId()
            )
                ->whereNull('parent_id')
                ->findOrFail($request->integer('parent_customer_id'));

            $request->merge([
                'contact' => $parentCustomer->phone_number1,
            ]);
        }

        $ownerId = Auth::user()->businessOwnerId();

        $measurementTemplates = $this->measurementTemplates();

        // A basic save creates the customer without assigning a measurement template.
        $measurementTemplate = $basicSave
            ? null
            : $measurementTemplates->firstWhere(
                'id',
                (int) $request->input('measurement_template_id')
            );

        $measurementFields = $this->measurements->fieldsForTemplate(
            $this->measurements->activeFields($ownerId),
            $measurementTemplate,
        );

        // Always validate the customer's essential information.
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required',
                'string',
                'max:50',
                new PakistanMobileNumber,
            ],
            'parent_customer_id' => ['nullable', 'integer'],
            'mobile_pin' => ['nullable', 'digits:6'],
        ];

        $attributes = [];

        // Only validate measurement and template fields when the user
        // explicitly chooses to save the measurements.
        if (! $basicSave) {
            $rules = array_merge($rules, [
                'measurement_template_id' => [
                    'nullable',
                    Rule::in($measurementTemplates->pluck('id')->all()),
                ],
                'length' => ['nullable', 'numeric', 'min:0'],
                'arms' => ['nullable', 'numeric', 'min:0'],
                'teraa' => ['nullable', 'numeric', 'min:0'],
                'senaChorai' => ['nullable', 'numeric', 'min:0'],
                'damanchorai' => ['nullable', 'numeric', 'min:0'],
                'shalwar' => ['nullable', 'numeric', 'min:0'],
                'pancha' => ['nullable', 'numeric', 'min:0'],
                'shalwarGheer' => ['nullable', 'numeric', 'min:0'],
                'monda' => ['nullable', 'numeric', 'min:0'],
                'chuta' => ['nullable', 'numeric', 'min:0'],
                'note' => ['nullable', 'string', 'max:2000'],
            ]);

            $rules = array_merge(
                $rules,
                $this->measurements->rules($measurementFields)
            );

            $attributes = $this->measurements->attributes($measurementFields);
        }

        $validated = $request->validate(
            $rules,
            [],
            $attributes
        );

        $existingCustomer = $parentCustomer
            ?: $this->existingRootCustomerByPhone($validated['contact']);

        $obj = new Customers;

        $obj->name = $validated['name'];
        $obj->phone_number1 = $existingCustomer?->phone_number1
            ?? $validated['contact'];

        // A basic save deliberately leaves all measurements empty.
        $obj->length = $basicSave ? null : $request->input('length');
        $obj->arms = $basicSave ? null : $request->input('arms');
        $obj->teraa = $basicSave ? null : $request->input('teraa');
        $obj->senaChorai = $basicSave ? null : $request->input('senaChorai');
        $obj->damanchorai = $basicSave ? null : $request->input('damanchorai');
        $obj->shalwar = $basicSave ? null : $request->input('shalwar');
        $obj->pancha = $basicSave ? null : $request->input('pancha');
        $obj->shalwarGheer = $basicSave ? null : $request->input('shalwarGheer');
        $obj->shoulder = $basicSave ? null : $request->input('monda');
        $obj->chuta = $basicSave ? null : $request->input('chuta');
        $obj->note = $basicSave ? null : $request->input('note');

        if ($existingCustomer) {
            $obj->parent_id = $existingCustomer->id;
        }

        // Preserve existing preference handling for a full measurement save.
        $obj->Daaman = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_daaman_type'));

        $obj->plate_type = $basicSave
            ? null
            : $this->preferenceValue($request->input('plate_type'));

        $obj->necktype = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_neck_type'));

        $obj->jeab = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_pocket_type'));

        $obj->button = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_button_type'));

        $obj->swingtype = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_sewing_type'));

        $obj->shirtbutton = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_shirt_button_type'));

        $obj->sleeve = $basicSave
            ? null
            : $this->preferenceValue($request->input('add_sleeve_opening_type'));

        $obj->user_id = $ownerId;

        if (! $existingCustomer) {
            $obj->acquisition_source = 'tailoring';
        }

        $obj->measurement_template_id = $measurementTemplate?->id;

        $isSecondaryProfile = $obj->parent_id !== null;

        $plainPin = $isSecondaryProfile
            ? null
            : ($validated['mobile_pin'] ?? (string) random_int(100000, 999999));

        $obj->mobile_pin = $plainPin ? Hash::make($plainPin) : null;
        $obj->pin_changed_at = $plainPin ? now() : null;

        // Basic saves must not persist measurements or create measurement history.
        $systemValues = $basicSave ? [] : [
            'length' => $request->input('length'),
            'arms' => $request->input('arms'),
            'teraa' => $request->input('teraa'),
            'senaChorai' => $request->input('senaChorai'),
            'damanchorai' => $request->input('damanchorai'),
            'shalwar' => $request->input('shalwar'),
            'pancha' => $request->input('pancha'),
            'shalwarGheer' => $request->input('shalwarGheer'),
            'shoulder' => $request->input('monda'),
            'chuta' => $request->input('chuta'),
            'necktype' => $this->preferenceValue($request->input('add_neck_type')),
            'sleeve' => $this->preferenceValue($request->input('add_sleeve_opening_type')),
            'Daaman' => $this->preferenceValue($request->input('add_daaman_type')),
            'jeab' => $this->preferenceValue($request->input('add_pocket_type')),
            'swingtype' => $this->preferenceValue($request->input('add_sewing_type')),
            'button' => $this->preferenceValue($request->input('add_button_type')),
            'plate_type' => $this->preferenceValue($request->input('plate_type')),
        ];

        $customValues = $basicSave
            ? []
            : ($validated['custom_measurements'] ?? []);

        $filled = fn($value) => $value !== null && $value !== '';

        $templateKeys = $measurementTemplate?->system_fields ?? [];

        $hasMeasurements = ! $basicSave
            && $measurementTemplate
            && (
                collect($systemValues)->only($templateKeys)->contains($filled)
                || collect($customValues)->contains($filled)
            );

        // if (! $basicSave) {
        //     dd([
        //         'basic_save' => $basicSave,
        //         'selected_template_id' => $request->input('measurement_template_id'),
        //         'resolved_template_id' => $measurementTemplate?->id,
        //         'template_custom_field_ids' => $measurementTemplate?->custom_field_ids,
        //         'available_measurement_fields' => $measurementFields->map(fn($field) => [
        //             'id' => $field->id,
        //             'label' => $field->label,
        //         ])->values()->all(),
        //         'submitted_custom_measurements' => $request->input('custom_measurements', []),
        //         'validated_custom_measurements' => $validated['custom_measurements'] ?? [],
        //         'has_measurements' => $hasMeasurements,
        //     ]);
        // }

        DB::transaction(function () use (
            $obj,
            $measurementFields,
            $measurementTemplate,
            $systemValues,
            $customValues,
            $ownerId,
            $hasMeasurements
        ) {
            $obj->save();

            // Do not create measurement records when no measurements were entered.
            if (! $hasMeasurements) {
                return;
            }

            $this->measurements->saveTemplateMeasurements(
                $obj,
                $ownerId,
                $measurementTemplate,
                $systemValues,
                $customValues,
                $measurementFields,
                Auth::id(),
                'customer_created',
            );

            $this->measurements->recordHistory(
                $obj,
                $ownerId,
                $measurementTemplate,
                Auth::id(),
                'customer_created',
            );
        });

        

        if ($isSecondaryProfile) {
            return redirect()->route('admin.customers.statement', [
                'id' => $existingCustomer->id,
                'tab' => 'measurements',
                'profile' => $obj->id,
            ])->with(
                'insert',
                $basicSave
                    ? "{$obj->name} کا نیا پروفائل بغیر پیمائش کے شامل کر دیا گیا ہے۔"
                    : "{$obj->name} کا نیا ناپ/پروفائل موجودہ گاہک کے ساتھ شامل کر دیا گیا ہے۔"
            );
        }

        $redirect = redirect()
            ->route('admin.Customers.index', ['created' => $obj->id])
            ->with(
                'insert',
                $basicSave
                    ? 'گاہک کامیابی سے محفوظ کر لیا گیا ہے۔ پیمائش بعد میں شامل کی جا سکتی ہے۔'
                    : 'گاہک کامیابی سے شامل کر دیا گیا ہے۔'
            );

        // Only show the PIN for a newly created root customer, as before.
        if (! $isSecondaryProfile) {
            $redirect->with('customer_pin', $plainPin)
                ->with('customer_pin_name', $obj->name);
        }

        return $redirect;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $customer = $this->ownedCustomer($id);
        // $optionTypes = DB::table('options')
        // ->join('option_types', 'options.option_id', '=', 'option_types.id')
        // ->select('options.option_id','options.user_id','option_types.Name as otn','option_types.type','option_types.slug')
        // ->where('options.user_id',Auth::user()->businessOwnerId())
        // ->groupBy('options.option_id')
        // ->get();

        // dd($customer);
        $optionTypes = OptionType::query()
            ->whereIn('type', ['necktype', 'sleeve', 'daaman', 'jeab', 'swingtype', 'button', 'plate_type'])
            ->select('id as option_id', 'Name as otn', 'type', 'slug')->orderBy('id')->get();
        // dd($optionTypes);
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $measurementValues = $customer->measurementValues()->pluck('value', 'measurement_field_id');
        $measurementTemplates = $this->measurementTemplates();
        if ($customer->measurement_template_id === null) {
            $customer->measurement_template_id = $measurementTemplates->firstWhere('is_default', true)?->id;
        }
        $selectedTemplateId = (int) request('measurement_template_id', $customer->measurement_template_id);
        $selectedMeasurementTemplate = $measurementTemplates->firstWhere('id', $selectedTemplateId);
        $savedMeasurementValues = $this->measurements
            ->savedMeasurementRows(
                $customer,
                Auth::user()->businessOwnerId(),
                $selectedMeasurementTemplate
            )
            ->pluck('value', 'source_key');
        $parentCustomer = $customer->parent_id !== null
            ? $this->ownedCustomer($customer->parent_id)
            : null;

        $allTemplateValues = $measurementTemplates->mapWithKeys(fn($t) => [
            $t->id => $this->measurements
                ->savedMeasurementRows($customer, Auth::user()->businessOwnerId(), $t)
                ->pluck('value', 'source_key'),
        ]);

        $optionChoices = $this->optionChoicesFor($optionTypes);

        // dd($optionChoices->first()->first());

        return view('customer.edit', compact(
            'customer',
            'optionTypes',
            'measurementFields',
            'measurementValues',
            'measurementTemplates',
            'parentCustomer',
            'savedMeasurementValues',
            'allTemplateValues',
            'optionChoices'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $obj = $this->ownedCustomer($id);

        $parentCustomer = $obj->parent_id !== null
            ? Customers::where('user_id', Auth::user()->businessOwnerId())
            ->whereNull('parent_id')
            ->findOrFail($obj->parent_id)
            : null;

        if ($parentCustomer) {
            $request->merge([
                'contact' => $parentCustomer->phone_number1,
                'mobile_pin' => null,
            ]);
        }

        $ownerId = Auth::user()->businessOwnerId();
        $measurementTemplates = $this->measurementTemplates();

        // Use the selected template to determine which measurements can be edited.
        $measurementTemplate = $measurementTemplates->firstWhere(
            'id',
            (int) $request->input('measurement_template_id')
        );

        $measurementFields = $this->measurements->fieldsForTemplate(
            $this->measurements->activeFields($ownerId),
            $measurementTemplate,
        );

        $contactRules = [
            'required',
            'string',
            'max:50',
            new PakistanMobileNumber,
        ];

        if (! $parentCustomer) {
            $contactRules[] = new UniqueCustomerPhone(
                $ownerId,
                (int) $id
            );
        }

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'contact' => $contactRules,
            'mobile_pin' => ['nullable', 'digits:6'],
            'return_customer' => ['nullable', 'integer'],
            'return_search' => ['nullable', 'string', 'max:200'],
            'return_counter_order' => ['nullable', 'integer'],
            'return_profile' => ['nullable', 'integer'],
            'measurement_template_id' => [
                'nullable',
                Rule::in($measurementTemplates->pluck('id')->all()),
            ],
            'length' => ['nullable', 'numeric', 'min:0'],
            'arms' => ['nullable', 'numeric', 'min:0'],
            'teraa' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], $this->measurements->rules($measurementFields)), [], $this->measurements->attributes($measurementFields));

        // Update customer information only.
        // Template-specific measurements are stored in measurement history.
        $obj->name = $validated['name'];
        $obj->phone_number1 = $validated['contact'];
        $obj->note = $validated['note'] ?? null;
        $obj->user_id = $ownerId;

        DB::transaction(function () use (
            $obj,
            $validated,
            $measurementFields,
            $measurementTemplate,
            $ownerId
        ) {
            if (! empty($validated['mobile_pin'])) {
                $obj->mobile_pin = Hash::make($validated['mobile_pin']);
                $obj->pin_failed_attempts = 0;
                $obj->pin_locked_until = null;
                $obj->pin_changed_at = now();
                $obj->tokens()->delete();
            }

            $obj->save();

            // Save measurement values independently for the selected template.
            if ($measurementTemplate) {
                $this->measurements->saveTemplateMeasurements(
                    $obj,
                    $ownerId,
                    $measurementTemplate,
                    [
                        'length' => request()->input('length'),
                        'arms' => request()->input('arms'),
                        'teraa' => request()->input('teraa'),
                        'senaChorai' => request()->input('senaChorai'),
                        'damanchorai' => request()->input('damanchorai'),
                        'shalwar' => request()->input('shalwar'),
                        'pancha' => request()->input('pancha'),
                        'shalwarGheer' => request()->input('shalwarGheer'),
                        'shoulder' => request()->input('monda'),
                        'chuta' => request()->input('chuta'),

                        // These keys must match the preference field slugs in Blade.
                        // Array keys are the system field keys; input names are the select slugs from Blade.
                        'necktype' => $this->preferenceValue(request()->input('add_neck_type')),
                        'sleeve' => $this->preferenceValue(request()->input('add_sleeve_opening_type')),
                        'Daaman' => $this->preferenceValue(request()->input('add_daaman_type')),
                        'jeab' => $this->preferenceValue(request()->input('add_pocket_type')),
                        'swingtype' => $this->preferenceValue(request()->input('add_sewing_type')),
                        'button' => $this->preferenceValue(request()->input('add_button_type')),
                        'plate_type' => $this->preferenceValue(request()->input('plate_type')),
                    ],
                    $validated['custom_measurements'] ?? [],
                    $measurementFields,
                    Auth::id(),
                    'customer_update',
                );
            }

            // Existing orders are intentionally not updated here.
            // Their saved measurement snapshots and template IDs remain unchanged.
        });

        $returnCustomer = (int) ($validated['return_customer'] ?? 0);
        $returnSearch = trim((string) ($validated['return_search'] ?? ''));
        $returnCounterOrder = (int) ($validated['return_counter_order'] ?? 0);

        if ($returnCounterOrder > 0) {
            $counterOrder = CounterOrder::where('user_id', $ownerId)
                ->whereKey($returnCounterOrder)
                ->where('customer_id', $obj->parent_id ?: $obj->id)
                ->first();

            if ($counterOrder) {
                return redirect()->route('admin.counter-orders.edit', [
                    'counterOrder' => $counterOrder,
                    'profile' => (int) ($validated['return_profile'] ?? $obj->id),
                ])->with('success', 'پیمائش محفوظ ہو گئی ہے۔ اب سلائی آرڈر شامل کریں۔');
            }
        }

        $returnContext = array_filter([
            'return_customer' => $returnCustomer > 0 ? $returnCustomer : null,
            'return_search' => $returnSearch !== '' ? $returnSearch : null,
            'measurement_template_id' => $measurementTemplate?->id,
        ], fn($value) => $value !== null);

        $editUrl = route('admin.Customers.edit', $obj);

        if ($returnContext !== []) {
            $editUrl .= '?' . http_build_query($returnContext);
        }

        $response = redirect($editUrl);
        $response->with('insert', 'گاہک کی معلومات محفوظ کر دی گئی ہیں۔');

        if (! empty($validated['mobile_pin'])) {
            $response->with('customer_pin', $validated['mobile_pin'])
                ->with('customer_pin_name', $obj->name);
        }

        return $response;
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function DirectPayment(Request $req)
    {
        $validated = $req->validate([
            'customer_id' => ['required', 'integer'],
            'order_id' => ['nullable', 'integer'],
            'DirectPayment' => ['required', 'numeric', 'gt:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_on' => ['nullable', 'date'],
            'return_to_statement' => ['nullable', 'boolean'],
            'return_to_accounts' => ['nullable', 'boolean'],
            'preserve_customer_context' => ['nullable', 'boolean'],
            'customer_search' => ['nullable', 'string', 'max:200'],
        ]);
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        if (PaymentMethods::requiresReference($paymentMethod) && blank($validated['payment_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_reference' => 'منتخب ادائیگی کے طریقے کا حوالہ نمبر درج کریں۔',
            ]);
        }
        $customer = $this->ownedCustomer($validated['customer_id']);
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customer->id)->sum('remainingBalance');

        $order = null;
        if (! empty($validated['order_id'])) {
            $order = Order::where('userId', Auth::user()->businessOwnerId())
                ->where('customerId', $customer->id)
                ->findOrFail($validated['order_id']);
            $orderBalance = $this->customerLedger->orderBalance($order);

            if ((float) $validated['DirectPayment'] > $orderBalance) {
                $message = "آرڈر #{$order->id} کا موجودہ بقایا Rs: {$orderBalance} ہے۔ درج کی گئی رقم اس سے زیادہ نہیں ہو سکتی۔";
                if ($req->expectsJson()) {
                    return response()->json(['message' => $message, 'errors' => ['DirectPayment' => [$message]]], 422);
                }

                return redirect()->back()->with([
                    'balanceError' => $message,
                ]);
            }
        }

        // Retrieve the customer's name
        $customerName = $customer->name;

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($validated['DirectPayment'] > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            $message = "موجودہ واجبات Rs: {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے Rs: {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے";
            if ($req->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['DirectPayment' => [$message]]], 422);
            }

            return redirect()->back()->with([
                'balanceError' => $message,
            ]);
        }

        // Proceed to save the transaction if the payment is within the allowed balance
        $transaction = new Transaction;
        $transaction->customerId = $customer->id;
        $transaction->remainingBalance = (-$validated['DirectPayment']);
        $transaction->recivedPayment = $validated['DirectPayment'];
        $transaction->Order_type = 'Payment';
        $transaction->orderId = $order?->id;
        $transaction->comment = $validated['comment'] ?? null;
        $transaction->payment_method = $paymentMethod;
        $transaction->payment_reference = $validated['payment_reference'] ?? null;
        $transaction->paid_on = $validated['paid_on'] ?? now()->toDateString();
        $transaction->userId = Auth::user()->businessOwnerId();
        $transaction->save();

        if ($req->expectsJson()) {
            return response()->json([
                'message' => "{$customerName} کے لئے Rs {$validated['DirectPayment']} کی رقم درج کر دی گئی ہے۔",
                'customerId' => $customer->id,
                'balance' => max(0, (float) $currentBalance - (float) $validated['DirectPayment']),
                'stats' => $this->customerDirectoryStats(true),
            ]);
        }

        if ($req->boolean('return_to_statement')) {
            $response = redirect()->route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']);
        } elseif ($req->boolean('return_to_accounts')) {
            $response = redirect()->route('admin.customer-accounts.index');
        } elseif ($req->boolean('preserve_customer_context')) {
            $query = ['customer' => $customer->id];
            $search = trim((string) ($validated['customer_search'] ?? ''));

            if ($search !== '') {
                $query['search'] = $search;
            }

            $response = redirect(url('admin/Customers') . '?' . http_build_query($query) . '#orderDetail');
        } else {
            $response = redirect('admin/Customers');
        }

        return $response->with('insert', " {$customerName} کے لئے آپ نے Rs{$req->DirectPayment} کی رقم درج کی ہے");
    }

    public function RackNo(Request $req)
    {
        $validated = $req->validate(['RackNo' => ['required', 'string', 'max:100']]);
        $obj = new rack;
        $obj->rack_no = $validated['RackNo'];
        $obj->user_id = auth()->user()->businessOwnerId();
        $obj->save();

        return redirect('admin/Customers')->with('insert', 'ریک نمبر کامیابی سے شامل کر دیا گیا ہے۔');
    }

    public function SaleDirectPayment(Request $req)
    {
        $validated = $req->validate([
            'customer_id' => ['required', 'integer'],
            'DirectPayment' => ['required', 'numeric', 'gt:0'],
            'comment' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_on' => ['nullable', 'date'],
            'return_to_statement' => ['nullable', 'boolean'],
        ]);
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        if (PaymentMethods::requiresReference($paymentMethod) && blank($validated['payment_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_reference' => 'منتخب ادائیگی کے طریقے کا حوالہ نمبر درج کریں۔',
            ]);
        }
        $customer = $this->ownedCustomer($validated['customer_id']);
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customer->id)->sum('remainingBalance');

        // Retrieve the customer's name
        $customerName = $customer->name;

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($validated['DirectPayment'] > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            return redirect()->back()->with([
                'balanceError' => "موجودہ واجبات {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے",
            ]);
        }
        $obj = new Transaction;
        $obj->customerId = $customer->id;
        $obj->remainingBalance = (-$validated['DirectPayment']);
        $obj->recivedPayment = $validated['DirectPayment'];
        $obj->Order_type = 'Payment';
        $obj->comment = $validated['comment'] ?? null;
        $obj->payment_method = $paymentMethod;
        $obj->payment_reference = $validated['payment_reference'] ?? null;
        $obj->paid_on = $validated['paid_on'] ?? now()->toDateString();
        $obj->userId = Auth::user()->businessOwnerId();
        $obj->save();

        $response = $req->boolean('return_to_statement')
            ? redirect()->route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions'])
            : redirect('admin/customers-record');

        return $response->with('insert', " {$customerName} کے لئے آپ نے {$req->DirectPayment} کی رقم درج کی ہے");
    }

    public function saleCustomer()
    {
        return view('stock.customer');
    }

    public function AddsaleCustomer(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_num' => [
                'required',
                'string',
                'max:50',
                new PakistanMobileNumber,
                new UniqueCustomerPhone(auth()->user()->businessOwnerId()),
            ],
        ]);

        try {
            Customers::create([
                'name' => $validatedData['customer_name'],
                'phone_number1' => $validatedData['customer_num'],
                'user_id' => auth()->user()->businessOwnerId(),
                'acquisition_source' => 'shop_customer',
            ]);

            $customer = Customers::where('user_id', auth()->user()->businessOwnerId())
                ->where('phone_number1_normalized', PakistanPhoneNumber::normalize($validatedData['customer_num']))
                ->latest('id')
                ->first();

            return redirect()->route('admin.Customers.index', ['created' => $customer?->id])
                ->with('insert', 'نیا گاہک شامل کر دیا گیا ہے۔');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    private function ownedCustomer($id): Customers
    {
        return Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function existingRootCustomerByPhone(string $phone): ?Customers
    {
        $ownerId = Auth::user()->businessOwnerId();
        $normalized = PakistanPhoneNumber::normalize($phone);
        $query = Customers::where('user_id', $ownerId)->whereNull('parent_id');

        if ($normalized) {
            $customer = (clone $query)->where('phone_number1_normalized', $normalized)->first();
            if ($customer) {
                return $customer;
            }
        }

        return $query->get()->first(
            fn(Customers $customer) => PakistanPhoneNumber::normalize($customer->phone_number1) === $normalized
        );
    }

    private function customerDirectoryQuery(string $search = '')
    {
        $ownerId = Auth::user()->businessOwnerId();
        $search = trim($search);
        $familyParentIds = collect();

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $familyParentIds = Customers::query()
                ->where('user_id', $ownerId)
                ->whereNotNull('parent_id')
                ->where(function ($family) use ($like) {
                    $family->where('name', 'like', $like)
                        ->orWhere('phone_number1', 'like', $like);
                })
                ->pluck('parent_id')
                ->filter()
                ->map(fn($parentId) => (int) $parentId)
                ->unique()
                ->values();
        }

        return Customers::query()
            ->where('user_id', $ownerId)
            ->whereNull('parent_id')
            ->with(['familyMeasurementProfiles' => fn($query) => $query
                ->with('measurementTemplate')])
            ->when($search !== '', function ($query) use ($search, $familyParentIds) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($searchQuery) use ($search, $like, $familyParentIds) {
                    $searchQuery->where('name', 'like', $like)
                        ->orWhere('phone_number1', 'like', $like);
                    if ($familyParentIds->isNotEmpty()) {
                        $searchQuery->orWhereIn('id', $familyParentIds->all());
                    }
                    if (ctype_digit($search)) {
                        $searchQuery->orWhere('serial_number', (int) $search);
                    }
                });
            });
    }

    private function hydrateCustomerDirectory(LengthAwarePaginator $customers, bool $canViewBalances): void
    {
        $ownerId = Auth::user()->businessOwnerId();
        $pageCustomers = $customers->getCollection();
        $customerIds = $pageCustomers->pluck('id')->map(fn($id) => (string) $id)->all();

        if ($canViewBalances && $customerIds !== []) {
            $balances = Transaction::query()
                ->where('userId', (string) $ownerId)
                ->whereIn('customerId', $customerIds)
                ->selectRaw('customerId, SUM(remainingBalance) AS current_balance')
                ->groupBy('customerId')
                ->get()
                ->keyBy(fn($balance) => (string) $balance->customerId);

            foreach ($pageCustomers as $customer) {
                $customer->setAttribute(
                    'current_balance',
                    (float) ($balances->get((string) $customer->id)->current_balance ?? 0)
                );
            }
        }

        $profiles = $pageCustomers->flatMap(
            fn(Customers $customer) => $customer->familyMeasurementProfiles
        );
        $profileIds = $profiles->pluck('id')->map(fn($id) => (string) $id)->all();
        if ($profileIds === []) {
            return;
        }

        $orderTotals = Order::query()
            ->where('userId', (string) $ownerId)
            ->whereIn('sub_customer', $profileIds)
            ->selectRaw('sub_customer, COUNT(*) AS profile_orders_count, SUM(totalPayment) AS orders_total')
            ->groupBy('sub_customer')
            ->get()
            ->keyBy(fn($orderTotal) => (string) $orderTotal->sub_customer);

        foreach ($profiles as $profile) {
            $summary = $orderTotals->get((string) $profile->id);
            $profile->setAttribute('profile_orders_count', (int) ($summary->profile_orders_count ?? 0));
            $profile->setAttribute('orders_total', (float) ($summary->orders_total ?? 0));
        }
    }

    private function customerDirectoryStats(bool $canViewBalances): array
    {
        $ownerId = Auth::user()->businessOwnerId();
        $customerCount = Customers::where('user_id', $ownerId)->whereNull('parent_id')->count();

        if (! $canViewBalances) {
            return compact('customerCount') + [
                'totalBalance' => 0.0,
                'customersWithBalance' => 0,
                'settledCustomers' => 0,
            ];
        }

        $balanceTotals = Transaction::query()
            ->selectRaw('customerId, SUM(remainingBalance) AS current_balance')
            ->where('userId', $ownerId)
            ->groupBy('customerId');
        $stats = DB::query()
            ->fromSub($balanceTotals, 'customer_balances')
            ->selectRaw('COALESCE(SUM(customer_balances.current_balance), 0) AS total_balance')
            ->selectRaw('SUM(CASE WHEN COALESCE(customer_balances.current_balance, 0) > 0 THEN 1 ELSE 0 END) AS customers_with_balance')
            ->first();
        $customersWithBalance = (int) ($stats->customers_with_balance ?? 0);

        return compact('customerCount', 'customersWithBalance') + [
            'totalBalance' => (float) ($stats->total_balance ?? 0),
            'settledCustomers' => max(0, $customerCount - $customersWithBalance),
        ];
    }

    private function measurementTemplates()
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())
            ->where('is_active', true)->with('templateOptions.optionType')
            ->orderByDesc('is_default')->orderBy('name')->get();
    }

    private function preferenceValue(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d+\s*-\s*(.+)$/u', $value, $matches)) {
            return trim($matches[1]);
        }

        return $value;
    }
}
