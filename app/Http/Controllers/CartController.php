<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use App\Models\Cloth;
use App\Models\ClothColor;
use App\Models\Setting;
use App\Models\OnlineOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewOnlineOrderNotification;
use App\Notifications\OrderCancelNotification;

class CartController extends Controller
{
    public function AddCart($slug, Request $request)
    {
        try {
            $slug = $slug;
            $shop = Setting::where('shop_slug', $slug)->first();
            $stockId = $request->input('Stock');
            $length = $request->input('length');
            $price = $request->input('price');
            $color = $request->input('color');
            $userId = auth()->user()->id;
            // return response()->json($color);
            $cloth = ClothColor::where('cloth_id', $stockId)->where('color',$color)->first();
            if (!$cloth) {
                return response()->json('Cloth not found for the selected color', 404);
            }
            $cloth_length = $cloth->length;
            $cloth->update([
                'length' => $cloth_length - $length
            ]);
            Cart::create([
                'user_id' => $userId,
                'cloth_id' => $stockId,
                'length' => $length,
                'price' => $price,
                'color' => $color,
                'shop_name' => $shop->name
            ]);
            return response()->json('Added to Cart Successfully');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function ShowCart($slug)
    {
        try {
            $slug = $slug;
            $shop = Setting::where('shop_slug', $slug)->first();
            // get the shop name
            $name = explode('_', $slug)[0];
            // Determine the directory path based on the slug
            $directoryPath = resource_path('views/layouts/' . $name);

            if (is_dir($directoryPath)) {
                $userId = auth()->user()->id;
                $cart_records = Cart::where('user_id', $userId)->get();

                return view('layouts.' . $name . '.cart', compact('cart_records', 'slug'));
                // return view('layouts.' . $name . '.stock', compact('stocks', 'types', 'brand_name', 'color', 'slug'));
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    public function DeleteCart($slug, $id)
    {
        $slug = $slug;
        $cart = Cart::find($id);
        $cloth_id = $cart->cloth_id;
        $cart_legth = $cart->length;
        $color = $cart->color;
        $cloth = ClothColor::where('cloth_id', $cloth_id)->where('color',$color)->first();
        $cloth_length = $cloth->length;
        $cloth->update([
            'length' => $cloth_length + $cart_legth
        ]);

        $cart->delete();
        return redirect()->route('user.cart.show', ['slug' => $slug]);
    }

    public function BuyCart($slug, $id)
    {
        try {

            $slug = $slug;
            $shop = Setting::where('shop_slug', $slug)->first();
            $cart = Cart::find($id);
            $stockId = $cart->cloth_id;
            $length = $cart->length;
            $price = $cart->price;
            $userId = auth()->user()->id;

            $order = OnlineOrder::create([
                'user_id' => $userId,
                'cloth_id' => $stockId,
                'length' => $length,
                'price' => $price,
                'admin_user_id' => $shop->user_id
            ]);
            $cart->delete();

            $userId = $shop->user_id;

            // Fetch the user by user_id
            $user = User::find($userId);
            // dd($user);
            if ($user) {
                // Define the roles you want to check
                $roles = ['shop_owner', 'stock_seller'];

                // Check if the user has any of the specified roles
                if ($user->hasAnyRole($roles)) {
                    // Send notification to the user
                    Notification::send($user, new NewOnlineOrderNotification($order));
                } else {
                    // Handle case where the user does not have the required roles
                    echo "User does not have the required roles.";
                }
            } else {
                echo "User not found.";
            }
            $name = explode('_', $slug)[0]; //get the shop name
            // Determine the directory path based on the slug
            $directoryPath = resource_path('views/layouts/' . $name);
            if (is_dir($directoryPath)) {
                return view('layouts.' . $name . '.thank_you', compact('slug'));
            }

            // return view('thank_you');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function AddOrder($slug, Request $request)
    {
        try {
            $shop = Setting::where('shop_slug', $slug)->first();
            $stockId = $request->input('Stock');
            $length = $request->input('length');
            $price = $request->input('price');
            $color = $request->input('color');
            $userId = auth()->user()->id;

            $cloth = ClothColor::where('cloth_id', $stockId)->where('color',$color)->first();
            $cloth_length = $cloth->length;
            $cloth->update([
                'length' => $cloth_length - $length
            ]);
            $order = OnlineOrder::create([
                'user_id' => $userId,
                'cloth_id' => $stockId,
                'length' => $length,
                'price' => $price,
                'color' => $color,
                'status' => 'pending',
                'admin_user_id' => $shop->user_id
            ]);


            $userId = $shop->user_id;

            // Fetch the user by user_id
            $user = User::find($userId);
            // dd($user);
            if ($user) {
                // Define the roles you want to check
                $roles = ['shop_owner', 'stock_seller'];

                // Check if the user has any of the specified roles
                if ($user->hasAnyRole($roles)) {
                    // Send notification to the user
                    Notification::send($user, new NewOnlineOrderNotification($order));
                } else {
                    // Handle case where the user does not have the required roles
                    echo "User does not have the required roles.";
                }
            } else {
                echo "User not found.";
            }

            return response()->json('Order Placed Successfully');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function ThankYou($slug)
    {
        $slug = $slug;
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);
        if (is_dir($directoryPath)) {
            return view('layouts.' . $name . '.thank_you', compact('slug'));
        }
    }

    public function ShowOrderHistory($slug)
    {
        $slug = $slug;
        $shop = Setting::where('shop_slug', $slug)->first();
        // get the shop name
        $name = explode('_', $slug)[0];
        // Determine the directory path based on the slug
        $directoryPath = resource_path('views/layouts/' . $name);
        if (is_dir($directoryPath)) {

            // Fetch the authenticated user's orders
            $orders = OnlineOrder::where('user_id', Auth::id())->latest()->get();
            return view('layouts.' . $name . '.history', compact('slug','orders','shop'));
        }
    }

    public function CancelOrder($slug,$id)
    {
        $order = OnlineOrder::where('id',$id)->first();

        $order->update([
            'status' => 'Cancelled',
            'cancel_at' => now()
        ]);
        // dd($order);
        $admin_id = $order->admin_user_id;

        // Fetch the user
        $user = User::find($admin_id);
        if ($user) {
            // Define the roles you want to check
            $roles = ['shop_owner', 'stock_seller'];

            // Check if the user has any of the specified roles
            if ($user->hasAnyRole($roles)) {
                // Send notification to the user
                Notification::send($user, new OrderCancelNotification($order));
            } else {
                // Handle case where the user does not have the required roles
                echo "User does not have the required roles.";
            }
        } else {
            echo "User not found.";
        }

        return back()->with('success','Order has been Cancelled');
    }

    public function AgainOrder($slug,$id)
    {
        $order = OnlineOrder::where('id',$id)->first();

        $order->update([
            'status' => 'pending',
            'created_at' => now()
        ]);
        // dd($order);
        $admin_id = $order->admin_user_id;

        // Fetch the user
        $user = User::find($admin_id);
        if ($user) {
            // Define the roles you want to check
            $roles = ['shop_owner', 'stock_seller'];

            // Check if the user has any of the specified roles
            if ($user->hasAnyRole($roles)) {
                // Send notification to the user
                Notification::send($user, new NewOnlineOrderNotification($order));
            } else {
                // Handle case where the user does not have the required roles
                echo "User does not have the required roles.";
            }
        } else {
            echo "User not found.";
        }

        return back()->with('success','Order Placed agian');

    }
}
