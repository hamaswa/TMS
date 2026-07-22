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
        $user_id = Auth::user()->id;
        $OptionTypes = OptionType::get();
      
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $slug = Str::slug($request->input('Name'), '_');
        $obj = new OptionType();
        $obj->Name =$request->input('Name');
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
       $OptionType = OptionType::find($id);
       $OptionTypes = OptionType::get();
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
        $slug = Str::slug($request->input('Name'), '_');
        $obj = OptionType::find($id);
        $obj->Name =$request->input('Name');
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
        $obj = OptionType::find($id);
        $obj->delete();
        return back()->with('del','OptionType Deleted!');
    }
}