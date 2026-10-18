<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use App\Services\PrintDocumentService;
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
        // The form lives in a modal on the index page.
        return redirect()->route('admin.clothbrand.index', ['modal' => 'create']);
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
                'file' => ['nullable', 'image', 'max:2048'],
            ]);
            $name = $validated['name'];
            $file = $request->file('file');
            $fileName = $file?->store('BrandImages', 'public');
            $user_id = Auth::user()->businessOwnerId();

            $brand = ClothBrand::firstOrCreate(
                ['user_id' => $user_id, 'name' => $name],
                ['brand_logo' => $fileName, 'brand_slug' => Str::slug($name)]
            );
            if ($request->expectsJson()) {
                return response()->json(['id' => $brand->id, 'name' => $brand->name], $brand->wasRecentlyCreated ? 201 : 200);
            }
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
    public function show($id)
    {
        $clothBrand = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.clothbrand.edit', $clothBrand->id);
    }

    public function qrLabel(int $clothBrand, PrintDocumentService $printDocuments)
    {
        $clothBrand = ClothBrand::where('user_id', Auth::user()->businessOwnerId())
            ->withCount('cloths')
            ->findOrFail($clothBrand);

        return view('clothbrand.qr-label', [
            'clothBrand' => $clothBrand,
            'qrSvg' => $printDocuments->qrSvg($clothBrand->bundle_code, 240),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClothBrand  $clothBrand
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cloth_brand = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        // The form lives in a modal on the index page.
        return redirect()->route('admin.clothbrand.index', ['edit' => $cloth_brand->id]);
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
