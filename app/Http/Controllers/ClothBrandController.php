<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $cloth_brands = ClothBrand::where('user_id',auth()->user()->businessOwnerId())->latest()->get();
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
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'file' => ['required', 'image', 'max:2048'],
            ]);
            $name = $validated['name'];
            $file = $request->file('file');
            $fileName = $file->store('BrandImages', 'public');
            $user_id = Auth::user()->businessOwnerId();

            // Check if the brand name already exists
            $existingBrand = ClothBrand::where('user_id', $user_id)->where('name', $name)->first();

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
            $cloth_brand = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
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

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'file' => ['nullable', 'image', 'max:2048'],
            ]);
            $cloth_brand = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
            $name = $validated['name'];
            $file = $request->file('file');
            $attributes = [
                'name' => $name,
                'brand_slug' => Str::slug($name),
            ];
            if ($file) {
                $attributes['brand_logo'] = $file->store('BrandImages', 'public');
            }
            $cloth_brand->update($attributes);

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
            ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id)->delete();
            return back()->with('delete', 'ریکارڈ کامیابی سے حذف ہو گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
