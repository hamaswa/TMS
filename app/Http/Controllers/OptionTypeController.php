<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\OptionType;
use App\Models\User;
use DB;
class OptionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = Auth::user()->businessOwnerId();
        $OptionTypes = $this->availableOptionTypes()->get();
      
        $options = DB::table('option_types')
                ->select('options.*','option_types.name as otname')
                ->leftjoin('options','option_types.id','=','options.option_id')
                ->leftjoin('users','options.user_id','=','users.id')
                ->where('users.id',$user_id)
                ->where('option_types.id',1)
                ->first();    
        //    dd($options);  
        return view("OptionType.list",compact('OptionTypes','options'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('admin.OptionType.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate(['Name' => ['required', 'string', 'max:255']]);
        $slug = Str::slug($validated['Name'], '_');
        $obj = new OptionType();
        $obj->user_id = Auth::user()->businessOwnerId();
        $obj->Name =$validated['Name'];
        $obj->slug =$slug;
        $obj->type =$slug;
        $obj->save();
        return back()->with('insert','Option Type Inserted');
       

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->availableOptionTypes()->findOrFail($id);

        return redirect()->route('admin.options.add', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       $OptionType = OptionType::where('user_id', Auth::user()->businessOwnerId())->find($id);
       if (! $OptionType) {
           $this->availableOptionTypes()->findOrFail($id);

           return redirect()->route('admin.OptionType.index')
               ->with('insert', 'محفوظ سسٹم آپشن کی قسم تبدیل نہیں کی جا سکتی۔');
       }
       $OptionTypes = $this->availableOptionTypes()->get();
       return view('OptionType.edit',compact('OptionTypes','OptionType'));
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
        $validated = $request->validate(['Name' => ['required', 'string', 'max:255']]);
        $slug = Str::slug($validated['Name'], '_');
        $obj = OptionType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $obj->Name =$validated['Name'];
        $obj->slug =$slug;
        $obj->type =$slug;
        $obj->save();
        return redirect('admin/OptionType')->with('update','Data Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $obj = OptionType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $obj->delete();
        return back()->with('del','OptionType Deleted!');
    }

    private function availableOptionTypes()
    {
        return OptionType::where(function ($query) {
            $query->whereNull('user_id')->orWhere('user_id', Auth::user()->businessOwnerId());
        });
    }
}
