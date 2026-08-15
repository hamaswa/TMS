<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\Tailorsalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductionWorkforceService;

class TailorRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $tailor = Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.tailor-rates', $tailor)->with('openRateModal', true);
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
            Tailor::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
            $request['tailor_id']=$id;
            $formData=$request->validate([
                'tailor_id' => ['required', 'integer'],
                'options_id' => ['required', 'integer'],
                'price' => ['required', 'numeric', 'min:0.01']
            ]);
            Options::where('user_id', Auth::user()->businessOwnerId())
                ->where('option_id', 1)
                ->findOrFail($formData['options_id']);
            
            $rate = Tailorsalary::create($formData);
            app(ProductionWorkforceService::class)->syncRate($rate);

            return redirect()->route('admin.tailor-rates', $id)
                ->with('insert','درزی کی رقم کامیابی کے ساتھ شامل کی گئی۔');

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

            $rate = Tailorsalary::whereHas('tailor', function ($query) {
                $query->where('user_id', Auth::user()->businessOwnerId());
            })->findOrFail($id);

            app(ProductionWorkforceService::class)->retireRate($rate);
            $rate->delete();

            return back()->with('delete','درزی کی اجرت حذف کر دی گئی ہے۔');

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
