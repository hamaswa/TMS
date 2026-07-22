<?php

namespace App\Http\Controllers;

use App\Models\rack;
use App\Models\Tailor;
use App\Models\Options;
use App\Models\Customers;
use App\Models\OptionType;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;

class CustomerController extends Controller
{
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
        $customers = Customers::where('user_id', Auth::user()->id)
                      ->where('parent_id', null)
                      ->whereNotNull('necktype')
                      ->whereNotNull('swingtype')
                      ->get();
        return view('customer.list', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // $data=[];
        // // dd(Auth::user()->id);
        // $data['optionTypes'] = DB::table('options')
        //                     ->join('option_types', 'options.option_id', '=', 'option_types.id')
        //                     ->select('options.option_id','options.user_id','option_types.Name as otn','option_types.type','option_types.slug')
        //                     ->where('options.user_id',Auth::user()->id)
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
        ->where('options.user_id', auth()->id())
        ->groupBy('options.option_id', 'options.user_id') // Group by both option_id and user_id
        ->get();


        // $data['optionTypes'] = OptionType::with('options')->where('user_id',Auth::user()->id)->get();
        // dd($data);
        $data['Tailors'] = Tailor::where('user_id', Auth::user()->id)->get();
        return view('customer.create', compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

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
        $obj->user_id = Auth::user()->id;
        $obj->save();
        // dd($obj);
        return redirect('admin/Customers')->with('insert', 'Customer Added');
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
        $customer = Customers::find($id);
        // $optionTypes = DB::table('options')
        // ->join('option_types', 'options.option_id', '=', 'option_types.id')
        // ->select('options.option_id','options.user_id','option_types.Name as otn','option_types.type','option_types.slug')
        // ->where('options.user_id',Auth::user()->id)
        // ->groupBy('options.option_id')
        // ->get();
    
        // dd($customer);
        $optionTypes = OptionType::select('options.option_id', 'options.user_id', 'option_types.Name as otn', 'option_types.type', 'option_types.slug')
            ->join('options', 'options.option_id', '=', 'option_types.id')
            ->where('options.user_id', auth()->user()->id)
            ->groupBy('options.option_id')
            ->get();
        // dd($optionTypes);
        return view('customer.edit', compact('customer', 'optionTypes'));
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
        $obj = Customers::find($id);
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
        $obj->user_id = Auth::user()->id;
        $obj->save();
        // dd($obj);
    return redirect('admin/Customers')->with('insert', 'Customer Update');
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
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('customerId', $req->customer_id)->sum('remainingBalance');

        // Retrieve the customer's name
        $customer = Customers::find($req->customer_id);
        $customerName = $customer ? $customer->name : 'Customer';

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($req->DirectPayment > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            return redirect()->back()->with([
                'balanceError' => "موجودہ واجبات Rs: {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے  Rs : {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے"
            ]);
        }

        // Proceed to save the transaction if the payment is within the allowed balance
        $transaction = new Transaction;
        $transaction->customerId = $req->customer_id;
        $transaction->remainingBalance = (-$req->DirectPayment);
        $transaction->recivedPayment = $req->DirectPayment;
        $transaction->Order_type = 'Tailor';
        $transaction->comment = $req->comment;
        $transaction->userId = Auth::user()->id;
        $transaction->save();

        return redirect('admin/Customers')->with('insert', " {$customerName} کے لئے آپ نے Rs{$req->DirectPayment} کی رقم درج کی ہے");
    }

    public function RackNo(Request $req)
    {
        $obj = new rack;
        $obj->rack_no = $req->RackNo;
        $obj->user_id = auth()->user()->id;
        $obj->save();

        return redirect('admin/Customers')->with('insert', 'Rack Number Added');
    }

    public function SaleDirectPayment(Request $req)
    {
        // Retrieve the current remaining balance for the customer
        $currentBalance = Transaction::where('customerId', $req->customer_id)->sum('remainingBalance');

        // Retrieve the customer's name
        $customer = Customers::find($req->customer_id);
        $customerName = $customer ? $customer->name : 'Customer';

        // Check if the DirectPayment amount is less than or equal to the current balance
        if ($req->DirectPayment > $currentBalance) {
            // If the payment exceeds the current balance, show an error message
            return redirect()->back()->with([
                'balanceError' => "موجودہ واجبات {$currentBalance} ہیں۔ {$customerName} کے لئے آپ نے {$req->DirectPayment} کی رقم درج کی ہے جو دستیاب واجبات سے زیادہ ہے"
            ]);
        }
        $obj = new Transaction;
        $obj->customerId = $req->customer_id;
        $obj->remainingBalance = (-$req->DirectPayment);
        $obj->recivedPayment = $req->DirectPayment;
        $obj->Order_type = $req->comment;
        $obj->comment = $req->comment;
        $obj->userId = Auth::user()->id;
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
                'user_id' => auth()->user()->id
            ]);

            return redirect()->route('admin.stock.index')->with('insert', 'نیا کسٹمر شامل کیا گیا ہے۔');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

}
