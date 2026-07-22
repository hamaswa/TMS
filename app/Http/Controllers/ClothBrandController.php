<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ClothBrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $cloth_brands = ClothBrand::where('user_id',auth()->user()->id)->latest()->get();
            return view('clothbrand.index', compact('cloth_brands'));
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

            return view('clothbrand.create');
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
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            // $image->move('public/images/setting/', $imageName);
            // $path = $file->storeAs('BrandImages', $fileName, 'public');
            $file->move('public/images/setting/', $fileName);
            $user_id = auth()->user()->id;

            // Check if the brand name already exists
            $existingBrand = ClothBrand::where('name', $name)->first();

            if ($existingBrand) {
                // If the brand name exists, use the existing slug
                $brand_slug = $existingBrand->brand_slug;
            } else {
                // If the brand name does not exist, generate a new slug
                $brand_slug = Str::slug($name);
            }
            ClothBrand::create([
                'name' => $name,
                'brand_logo' => $fileName,
                'user_id' => $user_id,
                'brand_slug' => $brand_slug
            ]);
            return redirect()->route('admin.clothbrand.index')->with('insert', 'کپڑے کی کمپنی کامیابی کے ساتھ شامل کی گئی۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClothBrand  $clothBrand
     * @return \Illuminate\Http\Response
     */
    public function show(ClothBrand $clothBrand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClothBrand  $clothBrand
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $cloth_brand = ClothBrand::find($id);
            return view('clothbrand.edit', compact('cloth_brand'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClothBrand  $clothBrand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {

            $cloth_brand = ClothBrand::find($id);
            $name = $request->input('name');
            $file = $request->file('file');
            if ($file) {
                $fileName = $file->getClientOriginalName();
                // $path = $file->storeAs('BrandImages', $fileName, 'public');
                $path = $file->move('public/images/setting/', $fileName);
                $cloth_brand->update([
                    'name' => $name,
                    'brand_logo' => $fileName,
                ]);
            }else{
                return 'Something went wrong';
            }

            return redirect()->route('admin.clothbrand.index')->with('insert', 'کپڑے کی کمپنی کامیابی کے ساتھ اپ ڈیٹ ہو گئی۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClothBrand  $clothBrand
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $cloth_brand = ClothBrand::find($id)->delete();
            return back()->with('delete', 'ریکارڈ کامیابی سے حذف ہو گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
