<?php

namespace App\Http\Controllers;

use App\Models\ClothCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClothCategoryController extends Controller
{
    public function index()
    {
        $ownerId = Auth::user()->businessOwnerId();
        ClothCategory::ensureDefaultFor($ownerId);

        $categories = ClothCategory::where('user_id', $ownerId)->topLevel()->ordered()
            ->withCount('cloths')
            ->with(['children' => fn ($query) => $query->withCount('cloths')])
            ->get();

        return view('clothcategory.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $this->validated($request, $ownerId);
        $parent = $this->parentFor($ownerId, $validated['parent_id'] ?? null);

        $category = ClothCategory::create([
            'user_id' => $ownerId,
            'parent_id' => $parent?->id,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'],
            // A sub category counts stock like its main category unless the form says otherwise.
            'stock_unit' => $validated['stock_unit'] ?? $parent?->stock_unit ?? ClothCategory::UNIT_METER,
            'slug' => ClothCategory::makeUniqueSlug($ownerId, $validated['name'], $parent),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->fullName(),
            ], 201);
        }

        return redirect()->route('admin.clothcategory.index')->with('insert', 'کیٹیگری کامیابی سے شامل ہو گئی۔');
    }

    public function update(Request $request, $id)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $category = ClothCategory::where('user_id', $ownerId)->findOrFail($id);
        $validated = $this->validated($request, $ownerId, $category);
        $parent = $this->parentFor($ownerId, $validated['parent_id'] ?? null);

        if ($parent && $category->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'جس کیٹیگری میں سب کیٹیگریز ہوں اسے کسی دوسری کیٹیگری کے اندر نہیں رکھا جا سکتا۔',
            ]);
        }

        $unit = $validated['stock_unit'] ?? $category->stock_unit;
        if ($unit !== $category->stock_unit && $category->cloths()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'stock_unit' => 'اس کیٹیگری میں کپڑے موجود ہیں، اس لیے گنتی کا طریقہ نہیں بدلا جا سکتا۔ نئی کیٹیگری بنائیں۔',
            ]);
        }

        DB::transaction(function () use ($category, $validated, $parent, $ownerId, $unit) {
            $category->update([
                'parent_id' => $parent?->id,
                'name' => $validated['name'],
                'name_ur' => $validated['name_ur'],
                'stock_unit' => $unit,
                'slug' => ClothCategory::makeUniqueSlug($ownerId, $validated['name'], $parent, $category->id),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            // Child slugs carry the parent's slug, so they follow a rename.
            foreach ($category->children as $child) {
                $child->update(['slug' => ClothCategory::makeUniqueSlug($ownerId, $child->name, $category, $child->id)]);
            }
        });

        return redirect()->route('admin.clothcategory.index')->with('insert', 'کیٹیگری کامیابی سے اپ ڈیٹ ہو گئی۔');
    }

    public function toggle($id)
    {
        $category = ClothCategory::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with($category->is_active ? 'insert' : 'update', $category->is_active
            ? 'کیٹیگری دوبارہ فعال کر دی گئی۔'
            : 'کیٹیگری غیر فعال کر دی گئی۔ پرانے کپڑے اسی کیٹیگری میں رہیں گے۔');
    }

    public function destroy($id)
    {
        $category = ClothCategory::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        if ($category->children()->exists()) {
            return back()->with('delete', 'اس کیٹیگری میں سب کیٹیگریز موجود ہیں، پہلے انہیں حذف یا منتقل کریں۔');
        }
        if ($category->cloths()->exists()) {
            return back()->with('delete', 'اس کیٹیگری میں کپڑے موجود ہیں، اسے حذف کرنے کے بجائے غیر فعال کریں۔');
        }

        $category->delete();

        return back()->with('delete', 'کیٹیگری کامیابی سے حذف ہو گئی۔');
    }

    private function validated(Request $request, int $ownerId, ?ClothCategory $category = null): array
    {
        $validated = $request->validate([
            'name_ur' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'stock_unit' => ['nullable', Rule::in(ClothCategory::UNITS)],
        ]);

        $duplicate = ClothCategory::where('user_id', $ownerId)
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->where(fn ($query) => $query->where('name', $validated['name'])->orWhere('name_ur', $validated['name_ur']))
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'اس نام کی کیٹیگری یہاں پہلے سے موجود ہے۔']);
        }

        if ($category && (int) ($validated['parent_id'] ?? 0) === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'کیٹیگری اپنی ہی مین کیٹیگری نہیں ہو سکتی۔']);
        }

        return $validated;
    }

    /** The parent must be one of the shop's own top-level categories, which keeps the tree at two levels. */
    private function parentFor(int $ownerId, $parentId): ?ClothCategory
    {
        if (! $parentId) {
            return null;
        }

        $parent = ClothCategory::where('user_id', $ownerId)->topLevel()->find($parentId);
        if (! $parent) {
            throw ValidationException::withMessages(['parent_id' => 'منتخب مین کیٹیگری درست نہیں ہے۔']);
        }

        return $parent;
    }
}
