<?php

namespace App\Http\Controllers;

use App\Models\ClothType;
use Illuminate\Http\Request;

class ClothTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $cloth_types = ClothType::where('user_id',auth()->user()->id)->orderBy('id', 'DESC')->get();

            return view('clothtype.index', compact('cloth_types'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            return view('clothtype.create');
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
    public function store(Request $request)
    {
        try {

                $name = $request->input('name');
                $user_id = auth()->user()->id;
            ClothType::create([
                'name' => $name,
                'user_id' => $user_id
            ]);
            return redirect()->route('admin.clothtype.index')->with('insert', 'کپڑے کی قسم کامیابی کے ساتھ شامل کی گئی۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClothType  $clothType
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        try {
            abort("404");
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClothType  $clothType
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $cloth_type = ClothType::find($id);
            return view('clothtype.edit', compact('cloth_type'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClothType  $clothType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {

            ClothType::find($id)->update($request->all());
            return redirect()->route('admin.clothtype.index')->with('insert', 'کپڑے کی قسم کامیابی کے ساتھ اپ ڈیٹ ہو گئی۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClothType  $clothType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $cloth_type=ClothType::find($id);
            $cloth_type->delete();
            return back()->with('delete','ریکارڈ کامیابی سے حذف ہو گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
