<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleCustomerController extends Controller
{
    public function shops()
    {
        $shops = Setting::all();
        return view('SaleCustomer.shops', compact('shops'));
    }
    public function saleCustomer($slug)
    {
        $slug = $slug;
        // get the shop name
        $name = explode('_', $slug)[0];

        // Determine the directory path based on the slug
        // this is used because it will dynamically goes to that specfic folder blade files
        $directoryPath = resource_path('views/layouts/' . $name);
        // dd($directoryPath);

        // Check if the directory exists
        if (is_dir($directoryPath)) {
            // Retrieve shop details based on the slug
            $shop = Setting::where('shop_slug', $slug)->first();

            // Retrieve stock for the shop
            $stocks = ClothBrand::where('user_id', $shop->user_id)->get();

            // Return the view from the dynamically created directory with variables
            return view('layouts.' . $name . '.index', compact('stocks', 'shop', 'slug'));
        }
    }


    public function AccountDetails($slug)
    {
        $slug = $slug;
        $shop = Setting::where('shop_slug', $slug)->first();
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);

        if (is_dir($directoryPath)) {
            $user = User::where('id', auth()->user()->businessOwnerId())->first();
            return view('layouts.' . $name . '.account', compact('user', 'slug'));
        }
    }

    public function Stock($slug, $id = null)
    {
        $slug = $slug;
        $shop = Setting::where('shop_slug', $slug)->first();
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);
        // Check if the directory exists
        if (is_dir($directoryPath)) {
            $brand_name = null;
            $Stocks = null;
            $types = ClothType::where('user_id', $shop->user_id)->get();
            $colors = ClothColor::distinct()->pluck('color');

            if ($id) {
                $brand_name = ClothBrand::where('id', $id)->first();
                $Stocks = Cloth::where('user_id', $shop->user_id)->with(['colors', 'images','videos'])->get();
            }


            // Return the view from the created directory with variables
            return view('layouts.' . $name . '.stock', compact('Stocks', 'types', 'brand_name', 'slug', 'colors'));
        }
    }

    public function ShowStock($slug, $brand_id, $type_id,$color)
    {
        $slug = $slug;
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);

        if (is_dir($directoryPath)) {
            $stocks = Cloth::where('cloth_brand_id', $brand_id)
            ->where('cloth_type_id', $type_id)
            ->whereHas('images', function ($query) use ($color) {
                $query->where('image_color', $color);
            })
            ->orWhereHas('videos', function ($query) use ($color) {
                $query->where('video_color', $color);
            })
            ->with(['colors', 'images' => function ($query) use ($color) {
                $query->where('image_color', $color);
            }, 'videos' => function ($query) use ($color) {
                $query->where('video_color', $color);
            }])
            ->first();

            $relatedstocks = Cloth::where('cloth_brand_id', $brand_id)->where('cloth_type_id', $type_id)->with(['colors', 'images'])->get();
            // dd($relatedstocks);

            return view('layouts.' . $name . '.cloth', compact('stocks', 'slug', 'relatedstocks', 'brand_id', 'type_id','color'));
        }
    }

    public function stockSearch($slug, Request $request)
    {
        $slug = $slug;
        $shop = Setting::where('shop_slug', $slug)->first();
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);
        // Check if the directory exists
        if (is_dir($directoryPath)) {
            $brand_name = null;
            $Stocks = null;
            $types = ClothType::where('user_id', $shop->user_id)->get();
            $colors = ClothColor::where('user_id', $shop->user_id)->distinct()->pluck('color');

            $selectedBrandId = request()->input('brand');
            $selectedTypeId = request()->input('type');
            $selectedColor = request()->input('color');

            // Query to filter based on selected filters
            $query = Cloth::where('user_id', $shop->user_id);

            if ($selectedBrandId) {
                $query->where('cloth_brand_id', $selectedBrandId);
                $brand_name = ClothBrand::where('user_id', $shop->user_id)->find($selectedBrandId);
            }

            if ($selectedTypeId) {
                $query->where('cloth_type_id', $selectedTypeId);
            }

            if ($selectedColor) {
                $query->whereHas('colors', function ($query) use ($selectedColor) {
                    $query->where('color', $selectedColor);
                });
            }

            // Execute the query and get results
            $Stocks = $query->with(['colors', 'images'])->get();

            // Return the view from the created directory with variables
            return view('layouts.' . $name . '.stock', compact('Stocks', 'types', 'brand_name', 'slug', 'colors'));
        }
    }


    public function delete(Request $request, $slug, $id)
    {
        try {
            abort_unless((int) Auth::user()->businessOwnerId() === (int) $id, 403);
            $user = Auth::user();
            Auth::logout();
            $user->delete();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json('Account Successfully Deleted');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
