<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\OptionType;
use App\Models\Options;
use DB;
use App\Models\User;
class OptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $OptionTypes = OptionType::get();
        return view("Options.list",compact('OptionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $OptionTypes = OptionType::get();
        return view("Options.index",compact('OptionTypes'));
    }

    public function add($id)
    {
        $OptionTypes = OptionType::get();  //for side menu
        $optionType = OptionType::find($id); //this for title top of options list
             
        // $options = User::with('options.optiontype')->where('id', Auth::user()->id)->find($id);
        $options = DB::table('option_types')
        ->select('options.*','option_types.name as otname')
        ->leftjoin('options','option_types.id','=','options.option_id')
        ->leftjoin('users','options.user_id','=','users.id')
        ->where('users.id',Auth::user()->id)
        ->where('option_types.id',$id)
        ->get(); 
    //    dd($options);
        return view('Options.create',compact('options','optionType','OptionTypes'));
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
        $obj = new Options;
        $obj->Name = $request->input('Name');
        $obj->slug = $slug;
        $obj->option_id=$request->OptionTypeId;
        $obj->user_id = Auth::user()->id;
        $obj->save();
        // dd($obj);
        return redirect('admin/Options/add/'.$request->OptionTypeId);
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
        $Option = Options::find($id);
        $OptionTypes = OptionType::get();
        return view('Options.edit',compact('Option','OptionTypes'));
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
        $OptionTypeId = $request->OptionTypeId; // this is a OptionTYPE id for redirect page bcz we need in url
        $slug = Str::slug($request->input('Name'), '_');
        $obj = Options::find($id);
        $obj->Name = $request->input('Name');
        $obj->slug = $slug;
        $obj->save();
        return redirect('admin/Options/add/'.$OptionTypeId)->with('update','Option Updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $obj = Options::find($id);
        $obj->delete();
        return back()->with('del','Option Deleted');
    }
}