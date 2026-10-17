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

            $cloth_types = ClothType::where('user_id',auth()->user()->businessOwnerId())->with('parent')->orderBy('id', 'DESC')->get();

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
                'parent_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('cloth_types', 'id')->where('user_id', Auth::user()->businessOwnerId())],
            ]);
            $user_id = Auth::user()->businessOwnerId();
            if (!empty($validated['parent_id']) && ClothType::where('user_id', $user_id)->whereKey($validated['parent_id'])->whereNotNull('parent_id')->exists()) {
                return back()->withErrors(['parent_id' => 'صرف مرکزی قسم کے تحت ذیلی قسم شامل کریں۔'])->withInput();
            }
            $createdType = ClothType::create([
                'name' => $validated['name_ur'],
                'name_ur' => $validated['name_ur'],
                'name_en' => $validated['name_en'],
                'parent_id' => $validated['parent_id'] ?? null,
                'user_id' => $user_id
            ]);
            if ($request->expectsJson()) {
                return response()->json(['id' => $createdType->id, 'name' => $createdType->name, 'parent_id' => $createdType->parent_id], 201);
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
                'parent_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('cloth_types', 'id')->where('user_id', Auth::user()->businessOwnerId())],
            ]);
            $validated['name'] = $validated['name_ur'];
            if ((int) ($validated['parent_id'] ?? 0) === (int) $id) {
                return back()->withErrors(['parent_id' => 'قسم اپنی ذیلی قسم نہیں بن سکتی۔'])->withInput();
            }
            $parent = !empty($validated['parent_id']) ? ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['parent_id']) : null;
            if ($parent && $parent->parent_id) {
                return back()->withErrors(['parent_id' => 'صرف مرکزی قسم کے تحت ذیلی قسم شامل کریں۔'])->withInput();
            }
            if ($parent && ClothType::where('user_id', Auth::user()->businessOwnerId())->where('parent_id', $id)->exists()) {
                return back()->withErrors(['parent_id' => 'ذیلی اقسام والی مرکزی قسم کو دوسری قسم کے تحت نہیں رکھ سکتے۔'])->withInput();
            }
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
