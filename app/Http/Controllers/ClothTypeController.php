<?php

namespace App\Http\Controllers;

use App\Models\ClothType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            $cloth_types = ClothType::where('user_id',auth()->user()->businessOwnerId())->orderBy('id', 'DESC')->get();

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
        // The form lives in a modal on the index page.
        return redirect()->route('admin.clothtype.index', ['modal' => 'create']);
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
                'name_ur' => ['required', 'string', 'max:255'],
                'name_en' => ['required', 'string', 'max:255'],
            ]);
            $user_id = Auth::user()->businessOwnerId();
            $type = ClothType::firstOrCreate(
                ['user_id' => $user_id, 'name_ur' => $validated['name_ur']],
                ['name' => $validated['name_ur'], 'name_en' => $validated['name_en']]
            );
            if ($request->expectsJson()) {
                return response()->json([
                    'id' => $type->id,
                    'name' => $type->localizedName(),
                ], $type->wasRecentlyCreated ? 201 : 200);
            }
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
    public function show($id)
    {
        ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.clothtype.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClothType  $clothType
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cloth_type = ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        // The form lives in a modal on the index page.
        return redirect()->route('admin.clothtype.index', ['edit' => $cloth_type->id]);
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

            $validated = $request->validate([
                'name_ur' => ['required', 'string', 'max:255'],
                'name_en' => ['required', 'string', 'max:255'],
            ]);
            $validated['name'] = $validated['name_ur'];
            ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id)->update($validated);
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
            $cloth_type=ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
            $cloth_type->delete();
            return back()->with('delete','ریکارڈ کامیابی سے حذف ہو گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
