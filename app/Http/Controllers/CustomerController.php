<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\rack;
use App\Models\SaleStock;
use App\Models\Tailor;
use App\Models\Transaction;
use App\Rules\PakistanMobileNumber;
use App\Rules\UniqueCustomerPhone;
use App\Services\MeasurementService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(private MeasurementService $measurements) {}

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
        $canViewBalances = Auth::user()->hasBusinessPermission(BusinessRole::CUSTOMER_BALANCES);
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())
            ->where('parent_id', null)
            ->when($canViewBalances, fn ($query) => $query->withSum([
                'transactions as current_balance' => fn ($transactions) => $transactions->where('userId', Auth::user()->businessOwnerId()),
            ], 'remainingBalance'))
            ->get();

        return view('customer.list', compact('customers', 'canViewBalances'));
    }

    public function accounts()
    {
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())
            ->whereNull('parent_id')
            ->withSum([
                'transactions as current_balance' => fn ($transactions) => $transactions
                    ->where('userId', Auth::user()->businessOwnerId()),
            ], 'remainingBalance')
            ->orderBy('name')
            ->get();

        return view('customer.accounts', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        // $data=[];
        // // dd(Auth::user()->businessOwnerId());
        // $data['optionTypes'] = DB::table('options')
        //                     ->join('option_types', 'options.option_id', '=', 'option_types.id')
        //                     ->select('options.option_id','options.user_id','option_types.Name as otn','option_types.type','option_types.slug')
        //                     ->where('options.user_id',Auth::user()->businessOwnerId())
        //                     ->groupBy('options.option_id')
        //                     ->get();
        $data['optionTypes'] = OptionType::select(
            'options.option_id',
            'options.user_id',
            DB::raw('MAX(option_types.Name) as otn'),
            DB::raw('MAX(option_types.type) as type'),
            DB::raw('MAX(option_types.slug) as slug')
        )
            ->join('options', 'options.option_id', '=', 'option_types.id')
            ->where('options.user_id', auth()->user()->businessOwnerId())
            ->groupBy('options.option_id', 'options.user_id') // Group by both option_id and user_id
            ->get();

        // $data['optionTypes'] = OptionType::with('options')->where('user_id',Auth::user()->businessOwnerId())->get();
        // dd($data);
        $data['Tailors'] = Tailor::where('user_id', Auth::user()->businessOwnerId())->get();
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $measurementValues = collect();
        $measurementTemplates = $this->measurementTemplates();

        return view('customer.create', compact('data', 'measurementFields', 'measurementValues', 'measurementTemplates'));
    }

    public function statement($id)
    {
        $customer = $this->ownedCustomer($id);
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
                ->with('tailor:id,name')
                ->withSum(['transactions as outstanding_amount' => fn ($query) => $query->where('userId', $ownerId)], 'remainingBalance')
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
                ->map(fn ($sale) => (object) [
                    'id' => $sale->id,
                    'source' => 'legacy',
                    'created_at' => $sale->created_at,
                    'items_count' => $sale->detail->sum('quantity'),
                    'summary' => $sale->detail->pluck('product_name')->filter()->take(3)->implode('، '),
                    'amount' => $sale->detail->sum(fn ($detail) => (float) $detail->price * (int) $detail->quantity),
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
                ->groupBy(fn ($sale) => $sale->counter_sale_receipt_id
                    ? 'receipt-'.$sale->counter_sale_receipt_id
                    : 'legacy-'.$sale->created_at?->format('Y-m-d H:i:s'));
            $stockTransactions = Transaction::where('userId', $ownerId)
                ->where('Order_type', 'Sale')
                ->whereIn('sale_id', $stockGroups->map(fn ($items) => $items->first()->id))
                ->get()->keyBy('sale_id');
            $stockSales = $stockGroups
                ->map(function ($items) use ($stockTransactions) {
                    $first = $items->first();
                    $transaction = $stockTransactions->get($first->id);

                    return (object) [
                        'id' => $first->id,
                        'source' => 'stock',
                        'created_at' => $first->created_at,
                        'items_count' => $items->count(),
                        'summary' => $items->map(fn ($item) => collect([$item->brand?->name, $item->type?->name, $item->color])->filter()->implode(' '))->filter()->take(3)->implode('، '),
                        'amount' => $items->sum(fn ($item) => (float) $item->selling_price * (float) $item->length),
                        'received' => (float) ($transaction?->recivedPayment ?? 0),
                        'balance' => (float) ($transaction?->remainingBalance ?? 0),
                        'status' => $first->receipt?->status ?? 'completed',
                        'cancellation_reason' => $first->receipt?->cancellation_reason,
                    ];
                })
                ->values();
            $sales = $legacySales->concat($stockSales)->sortByDesc('created_at')->take(50)->values();
        }

        $systemMeasurements = collect();
        $customMeasurements = collect();
        $measurementHistories = collect();
        if ($canManageMeasurements) {
            $systemMeasurements = collect(MeasurementService::SYSTEM_FIELDS)
                ->map(fn (array $meta, string $key) => [
                    'label' => $meta['label'],
                    'value' => $customer->{$key},
                    'unit' => $meta['unit'],
                ])->filter(fn (array $measurement) => $measurement['value'] !== null && $measurement['value'] !== '');
            $customMeasurements = $customer->measurementValues()
                ->with('field')
                ->whereHas('field', fn ($query) => $query->where('user_id', $ownerId)->where('is_active', true))
                ->get()->sortBy(fn ($value) => [$value->field->sort_order, $value->field->label]);
            $measurementHistories = $customer->measurementHistories()
                ->with(['template:id,name', 'recorder:id,name', 'values'])
                ->limit(12)->get();
        }

        $tabs = collect([
            'overview' => ['label' => 'خلاصہ', 'icon' => 'fa-th-large'],
            'transactions' => $canViewBalances ? ['label' => 'کھاتہ اور ادائیگیاں', 'icon' => 'fa-receipt'] : null,
            'tailoring' => $canViewTailoring ? ['label' => 'ٹیلرنگ آرڈرز', 'icon' => 'fa-cut'] : null,
            'shop' => $canViewShop ? ['label' => 'کپڑے کی خریداری', 'icon' => 'fa-shopping-bag'] : null,
            'measurements' => $canManageMeasurements ? ['label' => 'پیمائش', 'icon' => 'fa-ruler-combined'] : null,
            'profile' => ['label' => 'ذاتی معلومات', 'icon' => 'fa-user'],
        ])->filter();
        $activeTab = request()->string('tab')->toString();
        $activeTab = $tabs->has($activeTab) ? $activeTab : 'overview';
        $paymentRoute = $canViewBalances ? route('admin.customer-payments.store') : null;

        return view('customer.statement', compact(
            'customer', 'totalBalance', 'transactions', 'orders', 'sales',
            'canViewBalances', 'canViewTailoring', 'canViewShop', 'canManageMeasurements',
            'totalReceived', 'systemMeasurements', 'customMeasurements', 'measurementHistories',
            'tabs', 'activeTab', 'paymentRoute'
        ));
    }

    public function show($id)
    {
        $this->ownedCustomer($id);

        return redirect()->route('admin.customers.statement', $id);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $measurementTemplates = $this->measurementTemplates();
        $measurementTemplate = $measurementTemplates->firstWhere('id', (int) $request->input('measurement_template_id'));
        $measurementFields = $this->measurements->fieldsForTemplate(
            $this->measurements->activeFields(Auth::user()->businessOwnerId()),
            $measurementTemplate,
        );
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required', 'string', 'max:50', new PakistanMobileNumber,
                new UniqueCustomerPhone(Auth::user()->businessOwnerId()),
            ],
            'mobile_pin' => ['nullable', 'digits:6'],
            'measurement_template_id' => ['nullable', Rule::in($measurementTemplates->pluck('id')->all())],
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
        ], $this->measurements->rules($measurementFields)), [], $this->measurements->attributes($measurementFields));

        $obj = new Customers;
        $obj->name = $request->name;
        $obj->phone_number1 = $request->contact;
        $obj->length = $request->length;
        $obj->arms = $request->arms;
        $obj->teraa = $request->teraa;
        $obj->senaChorai = $request->senaChorai; //
        $obj->damanchorai = $request->damanchorai;
        $obj->shalwar = $request->shalwar; //
        $obj->pancha = $request->pancha; //
        $obj->shalwarGheer = $request->shalwarGheer; //
        $obj->shoulder = $request->monda;
        $obj->chuta = $request->chuta;
        $obj->note = $request->note;

        // select option
        $daamanparts = explode('-', $request->add_daaman_type);
        $daaman = isset($daamanparts[1]) ? $daamanparts[1] : 0; // Using isset instead of null coalescing
        // dd($daaman);

        $obj->Daaman = $daaman;

        $plate_typeparts = explode('-', $request->plate_type);
        $platet_type = $request['plate_type'] = $plate_typeparts[1] ?? 0;
        // dd($platet_type);
        $obj->plate_type = $platet_type;

        $necktypeparts = explode('-', $request->add_neck_type);
        $neck_type = $request['neck_type'] = $necktypeparts[1] ?? 0;
        // dd($neck_type);
        $obj->necktype = $neck_type; //

        $jeabparts = explode('-', $request->add_pocket_type);
        $jeab_type = $request['jeab_type'] = $jeabparts[1] ?? 0;
        // dd($jeab_type);
        $obj->jeab = $jeab_type;

        $buttonparts = explode('-', $request->add_button_type);
        $button_type = $request['button_type'] = $buttonparts[1] ?? 0;
        // dd($button_type);
        $obj->button = $button_type;

        $sewing_typeparts = explode('-', $request->add_sewing_type);
        $sewing_type = $request['sewing_type'] = $sewing_typeparts[1] ?? 0;
        // dd($sewing_type);
        $obj->swingtype = $sewing_type;

        $shirt_button_typeparts = explode('-', $request->add_shirt_button_type);
        $shirt_button_type = $request['shirt_button_type'] = $shirt_button_typeparts[1] ?? 0;
        // dd($shirt_button_type);
        $obj->shirtbutton = $shirt_button_type;

        $sleeve_opening_typeparts = explode('-', $request->add_sleeve_opening_type);
        $sleeve_opening_type = $request['sleeve_opening_type'] = $sleeve_opening_typeparts[1] ?? 0;
        // dd($sleeve_opening_type);
        $obj->sleeve = $sleeve_opening_type;
        $obj->user_id = Auth::user()->businessOwnerId();
        $obj->measurement_template_id = $measurementTemplate?->id;
        $plainPin = $validated['mobile_pin'] ?? (string) random_int(100000, 999999);
        $obj->mobile_pin = Hash::make($plainPin);
        $obj->pin_changed_at = now();
        DB::transaction(function () use ($obj, $measurementFields, $validated, $measurementTemplate) {
            $obj->save();
            $this->measurements->syncCustomer($obj, $measurementFields, $validated['custom_measurements'] ?? []);
            $this->measurements->recordHistory(
                $obj,
                Auth::user()->businessOwnerId(),
                $measurementTemplate,
                Auth::id(),
                'customer_created',
            );
        });

        // dd($obj);
        return redirect('admin/Customers')
            ->with('insert', 'گاہک کامیابی سے شامل کر دیا گیا ہے۔')
            ->with('customer_pin', $plainPin)
            ->with('customer_pin_name', $obj->name);
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
        $optionTypes = OptionType::select('options.option_id', 'options.user_id', 'option_types.Name as otn', 'option_types.type', 'option_types.slug')
            ->join('options', 'options.option_id', '=', 'option_types.id')
            ->where('options.user_id', auth()->user()->businessOwnerId())
            ->groupBy('options.option_id')
            ->get();
        // dd($optionTypes);
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $measurementValues = $customer->measurementValues()->pluck('value', 'measurement_field_id');
        $measurementTemplates = $this->measurementTemplates();

        return view('customer.edit', compact('customer', 'optionTypes', 'measurementFields', 'measurementValues', 'measurementTemplates'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $measurementTemplates = $this->measurementTemplates();
        $measurementTemplate = $measurementTemplates->firstWhere('id', (int) $request->input('measurement_template_id'));
        $measurementFields = $this->measurements->fieldsForTemplate(
            $this->measurements->activeFields(Auth::user()->businessOwnerId()),
            $measurementTemplate,
        );
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required', 'string', 'max:50', new PakistanMobileNumber,
                new UniqueCustomerPhone(Auth::user()->businessOwnerId(), (int) $id),
            ],
            'mobile_pin' => ['nullable', 'digits:6'],
            'measurement_template_id' => ['nullable', Rule::in($measurementTemplates->pluck('id')->all())],
            'length' => ['nullable', 'numeric', 'min:0'],
            'arms' => ['nullable', 'numeric', 'min:0'],
            'teraa' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], $this->measurements->rules($measurementFields)), [], $this->measurements->attributes($measurementFields));
        $obj = $this->ownedCustomer($id);
        $previousTemplate = $obj->measurementTemplate;
        $previousRows = $this->measurements->measurementRows($obj, Auth::user()->businessOwnerId(), $previousTemplate);
        $previousFingerprint = $this->measurements->measurementFingerprint($previousRows, $previousTemplate);
        $obj->name = $request->name;
        $obj->phone_number1 = $request->contact;
        $obj->length = $request->length;
        $obj->arms = $request->arms;
        $obj->teraa = $request->teraa;
        $obj->senaChorai = $request->senaChorai; //
        $obj->damanchorai = $request->damanchorai;
        $obj->shalwar = $request->shalwar; //
        $obj->pancha = $request->pancha; //
        $obj->shalwarGheer = $request->shalwarGheer; //
        $obj->shoulder = $request->monda;
        $obj->chuta = $request->chuta;
        $obj->note = $request->note;

        // select option
        $daamanparts = explode('-', $request->add_daaman_type);
        $daaman = isset($daamanparts[1]) ? trim($daamanparts[1]) : 0;
        $obj->Daaman = $daaman;

        $plate_typeparts = explode('-', $request->plate_type);
        $platet_type = $request['plate_type'] = $plate_typeparts[1] ?? 0;
        // dd($platet_type);
        $obj->plate_type = $platet_type;

        $necktypeparts = explode('-', $request->add_neck_type);
        $neck_type = $request['neck_type'] = $necktypeparts[1] ?? 0;
        // dd($neck_type);
        $obj->necktype = $neck_type; //

        $jeabparts = explode('-', $request->add_pocket_type);
        $jeab_type = $request['jeab_type'] = $jeabparts[1] ?? 0;
        // dd($jeab_type);
        $obj->jeab = $jeab_type;

        $buttonparts = explode('-', $request->add_button_type);
        $button_type = $request['button_type'] = $buttonparts[1] ?? 0;
        // dd($button_type);
        $obj->button = $button_type;

        $sewing_typeparts = explode('-', $request->add_sewing_type);
        $sewing_type = $request['sewing_type'] = $sewing_typeparts[1] ?? 0;
        // dd($sewing_type);
        $obj->swingtype = $sewing_type;

        $shirt_button_typeparts = explode('-', $request->add_shirt_button_type);
        $shirt_button_type = $request['shirt_button_type'] = $shirt_button_typeparts[1] ?? 0;
        // dd($shirt_button_type);
        $obj->shirtbutton = $shirt_button_type;

        $sleeve_opening_typeparts = explode('-', $request->add_sleeve_opening_type);
        $sleeve_opening_type = $request['sleeve_opening_type'] = $sleeve_opening_typeparts[1] ?? 0;
        // dd($sleeve_opening_type);
        $obj->sleeve = $sleeve_opening_type;
        $obj->user_id = Auth::user()->businessOwnerId();
        $obj->measurement_template_id = $measurementTemplate?->id;
        DB::transaction(function () use ($obj, $validated, $measurementFields, $measurementTemplate, $previousTemplate, $previousRows, $previousFingerprint) {
            if (! empty($validated['mobile_pin'])) {
                $obj->mobile_pin = Hash::make($validated['mobile_pin']);
                $obj->pin_failed_attempts = 0;
                $obj->pin_locked_until = null;
                $obj->pin_changed_at = now();
                $obj->tokens()->delete();
            }
            $obj->save();
            $this->measurements->syncCustomer($obj, $measurementFields, $validated['custom_measurements'] ?? []);

            $currentRows = $this->measurements->measurementRows($obj, Auth::user()->businessOwnerId(), $measurementTemplate);
            $currentFingerprint = $this->measurements->measurementFingerprint($currentRows, $measurementTemplate);
            if (! hash_equals($previousFingerprint, $currentFingerprint)) {
                if (! $obj->measurementHistories()->exists()) {
                    $this->measurements->recordHistoryRows($obj, $previousRows, $previousTemplate, Auth::id(), 'baseline');
                }
                $this->measurements->recordHistoryRows($obj, $currentRows, $measurementTemplate, Auth::id(), 'customer_update');
            }
        });
        // dd($obj);
        $response = redirect('admin/Customers')->with('insert', 'گاہک کی معلومات محفوظ کر دی گئی ہیں۔');

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
            'DirectPayment' => ['required', 'numeric', 'gt:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_on' => ['nullable', 'date'],
            'return_to_statement' => ['nullable', 'boolean'],
            'return_to_accounts' => ['nullable', 'boolean'],
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
                'balanceError' => "موجودہ واجبات Rs: {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے  Rs : {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے",
            ]);
        }

        // Proceed to save the transaction if the payment is within the allowed balance
        $transaction = new Transaction;
        $transaction->customerId = $customer->id;
        $transaction->remainingBalance = (-$validated['DirectPayment']);
        $transaction->recivedPayment = $validated['DirectPayment'];
        $transaction->Order_type = 'Payment';
        $transaction->comment = $validated['comment'] ?? null;
        $transaction->payment_method = $paymentMethod;
        $transaction->payment_reference = $validated['payment_reference'] ?? null;
        $transaction->paid_on = $validated['paid_on'] ?? now()->toDateString();
        $transaction->userId = Auth::user()->businessOwnerId();
        $transaction->save();

        $response = $req->boolean('return_to_statement')
            ? redirect()->route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions'])
            : ($req->boolean('return_to_accounts')
                ? redirect()->route('admin.customer-accounts.index')
                : redirect('admin/Customers'));

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
                'required', 'string', 'max:50', new PakistanMobileNumber,
                new UniqueCustomerPhone(auth()->user()->businessOwnerId()),
            ],
        ]);

        try {
            Customers::create([
                'name' => $validatedData['customer_name'],
                'phone_number1' => $validatedData['customer_num'],
                'user_id' => auth()->user()->businessOwnerId(),
            ]);

            return redirect()->route('admin.stock.index')->with('insert', 'نیا کسٹمر شامل کیا گیا ہے۔');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    private function ownedCustomer($id): Customers
    {
        return Customers::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function measurementTemplates()
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())
            ->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
    }
}
