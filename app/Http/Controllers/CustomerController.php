<?php

namespace App\Http\Controllers;

use App\Models\rack;
use App\Models\Tailor;
use App\Models\Options;
use App\Models\Customers;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\SaleStock;
use App\Models\Notification;
use App\Services\MeasurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private MeasurementService $measurements)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
        $canViewBalances = Auth::user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES);
        $customers = Customers::where('user_id', Auth::user()->businessOwnerId())
            ->where('parent_id', null)
            ->when($canViewBalances, fn ($query) => $query->withSum([
                'transactions as current_balance' => fn ($transactions) => $transactions->where('userId', Auth::user()->businessOwnerId()),
            ], 'remainingBalance'))
            ->get();

        return view('customer.list', compact('customers', 'canViewBalances'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
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
        return view('customer.create', compact('data', 'measurementFields', 'measurementValues'));
    }

    public function statement($id)
    {
        $customer = $this->ownedCustomer($id);
        $user = Auth::user();
        $canViewBalances = $user->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES);
        $canViewTailoring = $user->hasBusinessPermission(\App\Models\BusinessRole::TAILORING_ORDERS);
        $canViewShop = $user->hasBusinessPermission(\App\Models\BusinessRole::CLOTHING_SALES);
        $baseTransactions = Transaction::where('userId', $user->businessOwnerId())->where('customerId', $customer->id);
        $totalBalance = $canViewBalances ? (float) (clone $baseTransactions)->sum('remainingBalance') : null;
        $visibleTypes = array_values(array_filter([
            $canViewTailoring ? 'Tailor' : null,
            $canViewShop ? 'Sale' : null,
            $canViewBalances ? 'Payment' : null,
        ]));
        $transactions = $canViewBalances && $visibleTypes
            ? (clone $baseTransactions)->whereIn('Order_type', $visibleTypes)->latest()->paginate(30)
            : null;
        $orders = $canViewTailoring
            ? $customer->orders()->where('userId', $user->businessOwnerId())->latest()->limit(20)->get()
            : collect();
        $sales = collect();
        if ($canViewShop) {
            $legacySales = $customer->sales()
                ->where('user_id', $user->businessOwnerId())
                ->withCount('detail')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($sale) => (object) [
                    'id' => $sale->id,
                    'created_at' => $sale->created_at,
                    'items_count' => $sale->detail_count,
                ]);
            $stockSales = SaleStock::where('user_id', $user->businessOwnerId())
                ->where('c_id', $customer->id)
                ->latest()
                ->limit(100)
                ->get()
                ->groupBy(fn ($sale) => $sale->created_at?->format('Y-m-d H:i:s'))
                ->map(fn ($items) => (object) [
                    'id' => $items->first()->id,
                    'created_at' => $items->first()->created_at,
                    'items_count' => $items->count(),
                ])
                ->values();
            $sales = $legacySales->concat($stockSales)->sortByDesc('created_at')->take(20)->values();
        }

        return view('customer.statement', compact(
            'customer', 'totalBalance', 'transactions', 'orders', 'sales',
            'canViewBalances', 'canViewTailoring', 'canViewShop'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required', 'string', 'max:50',
                Rule::unique('customers', 'phone_number1')->where(
                    fn ($query) => $query->where('user_id', Auth::user()->businessOwnerId())
                ),
            ],
            'mobile_pin' => ['nullable', 'digits:6'],
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
        $daamanparts = explode("-", $request->add_daaman_type);
        $daaman = isset($daamanparts[1]) ? $daamanparts[1] : 0; // Using isset instead of null coalescing
        // dd($daaman);

        $obj->Daaman = $daaman;


        $plate_typeparts = explode("-", $request->plate_type);
        $platet_type = $request['plate_type'] = $plate_typeparts[1] ?? 0;
        // dd($platet_type);
        $obj->plate_type = $platet_type;

        $necktypeparts = explode("-", $request->add_neck_type);
        $neck_type = $request['neck_type'] = $necktypeparts[1] ?? 0;
        // dd($neck_type);
        $obj->necktype = $neck_type; //

        $jeabparts = explode("-", $request->add_pocket_type);
        $jeab_type = $request['jeab_type'] = $jeabparts[1] ?? 0;
        // dd($jeab_type);
        $obj->jeab = $jeab_type;

        $buttonparts = explode("-", $request->add_button_type);
        $button_type = $request['button_type'] = $buttonparts[1] ?? 0;
        // dd($button_type);
        $obj->button = $button_type;

        $sewing_typeparts = explode("-", $request->add_sewing_type);
        $sewing_type = $request['sewing_type'] = $sewing_typeparts[1] ?? 0;
        // dd($sewing_type);
        $obj->swingtype = $sewing_type;

        $shirt_button_typeparts = explode("-", $request->add_shirt_button_type);
        $shirt_button_type = $request['shirt_button_type'] = $shirt_button_typeparts[1] ?? 0;
        // dd($shirt_button_type);
        $obj->shirtbutton = $shirt_button_type;

        $sleeve_opening_typeparts = explode("-", $request->add_sleeve_opening_type);
        $sleeve_opening_type = $request['sleeve_opening_type'] = $sleeve_opening_typeparts[1] ?? 0;
        // dd($sleeve_opening_type);
        $obj->sleeve = $sleeve_opening_type;
        $obj->user_id = Auth::user()->businessOwnerId();
        $plainPin = $validated['mobile_pin'] ?? (string) random_int(100000, 999999);
        $obj->mobile_pin = Hash::make($plainPin);
        $obj->pin_changed_at = now();
        $obj->save();
        $this->measurements->syncCustomer($obj, $measurementFields, $validated['custom_measurements'] ?? []);
        // dd($obj);
        return redirect('admin/Customers')
            ->with('insert', 'گاہک کامیابی سے شامل کر دیا گیا ہے۔')
            ->with('customer_pin', $plainPin)
            ->with('customer_pin_name', $obj->name);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
        return view('customer.edit', compact('customer', 'optionTypes', 'measurementFields', 'measurementValues'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
        $measurementFields = $this->measurements->activeFields(Auth::user()->businessOwnerId());
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required', 'string', 'max:50',
                Rule::unique('customers', 'phone_number1')
                    ->where(fn ($query) => $query->where('user_id', Auth::user()->businessOwnerId()))
                    ->ignore($id),
            ],
            'mobile_pin' => ['nullable', 'digits:6'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'arms' => ['nullable', 'numeric', 'min:0'],
            'teraa' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], $this->measurements->rules($measurementFields)), [], $this->measurements->attributes($measurementFields));
        $obj = $this->ownedCustomer($id);
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
        $daamanparts = explode("-", $request->add_daaman_type);
$daaman = isset($daamanparts[1]) ? trim($daamanparts[1]) : 0;
$obj->Daaman = $daaman;

        


        $plate_typeparts = explode("-", $request->plate_type);
        $platet_type = $request['plate_type'] = $plate_typeparts[1] ?? 0;
        // dd($platet_type);
        $obj->plate_type = $platet_type;

        $necktypeparts = explode("-", $request->add_neck_type);
        $neck_type = $request['neck_type'] = $necktypeparts[1] ?? 0;
        // dd($neck_type);
        $obj->necktype = $neck_type; //

        $jeabparts = explode("-", $request->add_pocket_type);
        $jeab_type = $request['jeab_type'] = $jeabparts[1] ?? 0;
        // dd($jeab_type);
        $obj->jeab = $jeab_type;

        $buttonparts = explode("-", $request->add_button_type);
        $button_type = $request['button_type'] = $buttonparts[1] ?? 0;
        // dd($button_type);
        $obj->button = $button_type;

        $sewing_typeparts = explode("-", $request->add_sewing_type);
        $sewing_type = $request['sewing_type'] = $sewing_typeparts[1] ?? 0;
        // dd($sewing_type);
        $obj->swingtype = $sewing_type;

        $shirt_button_typeparts = explode("-", $request->add_shirt_button_type);
        $shirt_button_type = $request['shirt_button_type'] = $shirt_button_typeparts[1] ?? 0;
        // dd($shirt_button_type);
        $obj->shirtbutton = $shirt_button_type;

        $sleeve_opening_typeparts = explode("-", $request->add_sleeve_opening_type);
        $sleeve_opening_type = $request['sleeve_opening_type'] = $sleeve_opening_typeparts[1] ?? 0;
        // dd($sleeve_opening_type);
        $obj->sleeve = $sleeve_opening_type;
        $obj->user_id = Auth::user()->businessOwnerId();
        if (! empty($validated['mobile_pin'])) {
            $obj->mobile_pin = Hash::make($validated['mobile_pin']);
            $obj->pin_failed_attempts = 0;
            $obj->pin_locked_until = null;
            $obj->pin_changed_at = now();
            $obj->tokens()->delete();
        }
        $obj->save();
        $this->measurements->syncCustomer($obj, $measurementFields, $validated['custom_measurements'] ?? []);
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
     * @return \Illuminate\Http\Response
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
        ]);
        $customer = $this->ownedCustomer($validated['customer_id']);
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customer->id)->sum('remainingBalance');

        // Retrieve the customer's name
        $customerName = $customer->name;

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($validated['DirectPayment'] > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            return redirect()->back()->with([
                'balanceError' => "موجودہ واجبات Rs: {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے  Rs : {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے"
            ]);
        }

        // Proceed to save the transaction if the payment is within the allowed balance
        $transaction = new Transaction;
        $transaction->customerId = $customer->id;
        $transaction->remainingBalance = (-$validated['DirectPayment']);
        $transaction->recivedPayment = $validated['DirectPayment'];
        $transaction->Order_type = 'Payment';
        $transaction->comment = $validated['comment'] ?? null;
        $transaction->userId = Auth::user()->businessOwnerId();
        $transaction->save();

        return redirect('admin/Customers')->with('insert', " {$customerName} کے لئے آپ نے Rs{$req->DirectPayment} کی رقم درج کی ہے");
    }

    public function RackNo(Request $req)
    {
        $validated = $req->validate(['RackNo' => ['required', 'string', 'max:100']]);
        $obj = new rack;
        $obj->rack_no = $validated['RackNo'];
        $obj->user_id = auth()->user()->businessOwnerId();
        $obj->save();

        return redirect('admin/Customers')->with('insert', 'Rack Number Added');
    }

    public function SaleDirectPayment(Request $req)
    {
        $validated = $req->validate([
            'customer_id' => ['required', 'integer'],
            'DirectPayment' => ['required', 'numeric', 'gt:0'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);
        $customer = $this->ownedCustomer($validated['customer_id']);
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('userId', Auth::user()->businessOwnerId())->where('customerId', $customer->id)->sum('remainingBalance');

        // Retrieve the customer's name
        $customerName = $customer->name;

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($validated['DirectPayment'] > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            return redirect()->back()->with([
                'balanceError' => "موجودہ واجبات {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے"
            ]);
        }
        $obj = new Transaction;
        $obj->customerId = $customer->id;
        $obj->remainingBalance = (-$validated['DirectPayment']);
        $obj->recivedPayment = $validated['DirectPayment'];
        $obj->Order_type = 'Payment';
        $obj->comment = $validated['comment'] ?? null;
        $obj->userId = Auth::user()->businessOwnerId();
        $obj->save();

        return redirect('admin/customers-record')->with('insert', " {$customerName} کے لئے آپ نے {$req->DirectPayment} کی رقم درج کی ہے");
    }

    public function saleCustomer()
    {
        return view('stock.customer');
    }

    public function AddsaleCustomer(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_name' => 'required',
                'customer_num' => 'required'
            ]);

            Customers::create([
                'name' => $validatedData['customer_name'],
                'phone_number1' => $validatedData['customer_num'],
                'user_id' => auth()->user()->businessOwnerId()
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

}
