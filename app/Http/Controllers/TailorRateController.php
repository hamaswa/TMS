<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\Tailorsalary;
use Illuminate\Http\Request;

class TailorRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        dd("hie");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        try {
            $tailor=Tailor::find($id);
            $types=Options::where('option_id',1)->where('user_id',auth()->user()->id)->get();

            return view('tailor.rate-create',compact('types','tailor'));

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id)
    {
        try {
            $request['tailor_id']=$id;
            $formData=$request->validate([
                'tailor_id' => 'required',
                'options_id' => 'required',
                'price' => 'required'
            ]);
            
            Tailorsalary::create($formData);

            return back()->with('insert','درزی کی رقم کامیابی کے ساتھ شامل کی گئی۔');

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tailorsalary  $tailorsalary
     * @return \Illuminate\Http\Response
     */
    public function show(Tailorsalary $tailorsalary)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tailorsalary  $tailorsalary
     * @return \Illuminate\Http\Response
     */
    public function edit(Tailorsalary $tailorsalary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tailorsalary  $tailorsalary
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tailorsalary $tailorsalary)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tailorsalary  $tailorsalary
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {

            $rate=Tailorsalary::find($id);

            $rate->delete();

            return back()->with('delete','درجی کی رقم کامیابی کے ساتھ حذف کر دی گئی۔');

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
