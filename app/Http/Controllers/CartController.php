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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewOnlineOrderNotification;
use App\Notifications\OrderCancelNotification;
use App\Services\InventoryService;

class CartController extends Controller
{
    public function AddCart($slug, Request $request)
    {
        try {
            $validated = $request->validate([
                'Stock' => ['required', 'integer'],
                'length' => ['required', 'numeric', 'gt:0'],
                'color' => ['required', 'string', 'max:100'],
            ]);
            $shop = Setting::where('shop_slug', $slug)->firstOrFail();
            $stock = Cloth::where('user_id', $shop->user_id)->findOrFail($validated['Stock']);

            DB::transaction(function () use ($validated, $shop, $stock) {
                $inventory = app(InventoryService::class);
                $cloth = ClothColor::where('cloth_id', $stock->id)
                    ->where('color', $validated['color'])
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_if((float) $cloth->length < (float) $validated['length'], 422, 'Not enough cloth is available.');
                $cart = Cart::create([
                    'user_id' => Auth::user()->businessOwnerId(),
                    'cloth_id' => $stock->id,
                    'length' => $validated['length'],
                    'price' => $stock->sale_price ?? $stock->price,
                    'color' => $validated['color'],
                    'shop_name' => $shop->name,
                ]);
                $inventory->issue($cloth, (float) $validated['length'], 'cart_reservation', $cart, 'Reserved in customer cart');
            });
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
                $userId = auth()->user()->businessOwnerId();
                $cart_records = Cart::where('user_id', $userId)
                    ->whereHas('cloth', function ($query) use ($shop) {
                        $query->where('user_id', $shop->user_id);
                    })
                    ->get();

                return view('layouts.' . $name . '.cart', compact('cart_records', 'slug'));
                // return view('layouts.' . $name . '.stock', compact('stocks', 'types', 'brand_name', 'color', 'slug'));
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    public function DeleteCart($slug, $id)
    {
        DB::transaction(function () use ($id) {
            $inventory = app(InventoryService::class);
            $cart = Cart::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            $cloth = ClothColor::where('cloth_id', $cart->cloth_id)
                ->where('color', $cart->color)
                ->lockForUpdate()
                ->firstOrFail();
            $inventory->restore($cloth, (float) $cart->length, 'cart_release', $cart, 'Customer removed cart item');
            $cart->delete();
        });

        return redirect()->route('user.cart.show', ['slug' => $slug]);
    }

    public function BuyCart($slug, $id)
    {
        try {
            $shop = Setting::where('shop_slug', $slug)->firstOrFail();
            $order = DB::transaction(function () use ($id, $shop) {
                $cart = Cart::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
                $stock = Cloth::where('user_id', $shop->user_id)->findOrFail($cart->cloth_id);
                $color = ClothColor::where('cloth_id', $stock->id)->where('color', $cart->color)->firstOrFail();
                $unitCost = (float) $color->average_unit_cost ?: (float) $stock->price;

                $order = OnlineOrder::create([
                    'user_id' => Auth::user()->businessOwnerId(),
                    'cloth_id' => $stock->id,
                    'length' => $cart->length,
                    'price' => $stock->sale_price ?? $stock->price,
                    'color' => $cart->color,
                    'status' => 'pending',
                    'admin_user_id' => $shop->user_id,
                    'cost_per_meter' => $unitCost,
                    'cost_total' => round($unitCost * (float) $cart->length, 2),
                ]);
                $cart->delete();

                return $order;
            });

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
            $validated = $request->validate([
                'Stock' => ['required', 'integer'],
                'length' => ['required', 'numeric', 'gt:0'],
                'color' => ['required', 'string', 'max:100'],
            ]);
            $shop = Setting::where('shop_slug', $slug)->firstOrFail();
            $stock = Cloth::where('user_id', $shop->user_id)->findOrFail($validated['Stock']);

            $order = DB::transaction(function () use ($validated, $shop, $stock) {
                $inventory = app(InventoryService::class);
                $cloth = ClothColor::where('cloth_id', $stock->id)
                    ->where('color', $validated['color'])
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_if((float) $cloth->length < (float) $validated['length'], 422, 'Not enough cloth is available.');
                $unitCost = (float) $cloth->average_unit_cost ?: (float) $stock->price;
                $order = OnlineOrder::create([
                    'user_id' => Auth::user()->businessOwnerId(),
                    'cloth_id' => $stock->id,
                    'length' => $validated['length'],
                    'price' => $stock->sale_price ?? $stock->price,
                    'color' => $validated['color'],
                    'status' => 'pending',
                    'admin_user_id' => $shop->user_id,
                    'cost_per_meter' => $unitCost,
                    'cost_total' => round($unitCost * (float) $validated['length'], 2),
                ]);
                $inventory->issue($cloth, (float) $validated['length'], 'online_order', $order, 'Online order placed');
                return $order;
            });


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
            $orders = OnlineOrder::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
            return view('layouts.' . $name . '.history', compact('slug','orders','shop'));
        }
    }

    public function CancelOrder($slug,$id)
    {
        $order = DB::transaction(function () use ($id) {
            $inventory = app(InventoryService::class);
            $order = OnlineOrder::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            abort_unless(strtolower((string) $order->status) === 'pending', 422, 'Only pending orders can be cancelled.');

            $cloth = ClothColor::where('cloth_id', $order->cloth_id)
                ->where('color', $order->color)
                ->lockForUpdate()
                ->firstOrFail();
            $inventory->restore($cloth, (float) $order->length, 'online_cancellation', $order, 'Online order cancelled');
            $order->update([
                'status' => 'Cancelled',
                'cancel_at' => now(),
            ]);

            return $order;
        });
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
        $order = DB::transaction(function () use ($id) {
            $inventory = app(InventoryService::class);
            $order = OnlineOrder::where('user_id', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($id);
            abort_unless(strtolower((string) $order->status) === 'cancelled', 422, 'Only cancelled orders can be placed again.');

            $cloth = ClothColor::where('cloth_id', $order->cloth_id)
                ->where('color', $order->color)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if((float) $cloth->length < (float) $order->length, 422, 'Not enough cloth is available.');
            $inventory->issue($cloth, (float) $order->length, 'online_reorder', $order, 'Cancelled online order placed again');
            $order->update([
                'status' => 'pending',
                'created_at' => now(),
                'cancel_at' => null,
            ]);

            return $order;
        });
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
