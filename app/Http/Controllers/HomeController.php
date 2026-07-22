<?php

namespace App\Http\Controllers;

use App\Models\ClothColor;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SaleStock;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        session()->forget(['tailor-login-success', 'tailor', 'tailor_id']);
        $user = Auth::user();
        $modules = $user->enabledModules();

        if ($modules === [User::MODULE_TAILORING]) {
            return redirect()->route('admin.dashboard.tailoring');
        }

        if ($modules === [User::MODULE_CLOTHING]) {
            return redirect()->route('admin.dashboard.clothing');
        }

        abort_if($modules === [], 403, 'آپ کے اکاؤنٹ کے لیے کوئی کاروباری سہولت فعال نہیں ہے۔');

        session()->forget('active_workspace');

        return view('dashboard.select');
    }

    public function switch(string $workspace)
    {
        abort_unless(in_array($workspace, Auth::user()->enabledModules(), true), 403, 'یہ ورک اسپیس آپ کے اکاؤنٹ کے لیے فعال نہیں ہے۔');

        session(['active_workspace' => $workspace]);
        Auth::user()->forceFill(['preferred_workspace' => $workspace])->save();

        return redirect()->route($workspace === User::MODULE_TAILORING
            ? 'admin.dashboard.tailoring'
            : 'admin.dashboard.clothing');
    }

    public function current()
    {
        $workspace = session('active_workspace');

        if ($workspace && Auth::user()->hasModule($workspace)) {
            return $this->switch($workspace);
        }

        return redirect()->route('admin.home');
    }

    public function tailoring()
    {
        session(['active_workspace' => User::MODULE_TAILORING]);
        $user = Auth::user();
        $ownerId = $user->businessOwnerId();
        $canWorkshop = $user->hasBusinessPermission('tailoring.workshop');
        $canOrders = $user->hasBusinessPermission('tailoring.orders');
        $orders = Order::where('userId', $ownerId);
        $tailoring = [
            'active' => $canWorkshop ? (clone $orders)->where('status', '!=', 'delivered')->count() : null,
            'due_today' => $canWorkshop ? (clone $orders)->whereDate('returnDate', today())->where('status', '!=', 'delivered')->count() : null,
            'ready' => $canWorkshop ? (clone $orders)->where('status', 'ready')->count() : null,
            'month_suits' => $canOrders ? (int) (clone $orders)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('suitQuantity') : null,
        ];

        return view('dashboard.tailoring', compact('tailoring', 'canWorkshop', 'canOrders'));
    }

    public function clothing()
    {
        session(['active_workspace' => User::MODULE_CLOTHING]);
        $user = Auth::user();
        $ownerId = $user->businessOwnerId();
        $canInventory = $user->hasBusinessPermission('clothing.inventory');
        $canPurchases = $user->hasBusinessPermission('clothing.purchases');
        $canSales = $user->hasBusinessPermission('clothing.sales');
        $colors = ClothColor::where('user_id', $ownerId);
        $clothing = [
            'meters' => $canInventory ? (float) (clone $colors)->sum('length') : null,
            'inventory_value' => $canInventory ? (float) (clone $colors)->sum(DB::raw('length * average_unit_cost')) : null,
            'low_stock' => $canInventory ? (clone $colors)->where('length', '<=', 5)->count() : null,
            'draft_purchases' => $canPurchases ? Purchase::where('user_id', $ownerId)->where('status', 'draft')->count() : null,
            'month_sales' => $canSales ? (float) SaleStock::where('user_id', $ownerId)
                ->whereBetween('sellDate', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum(DB::raw('selling_price * length')) : null,
        ];

        return view('dashboard.clothing', compact('clothing', 'canInventory', 'canPurchases', 'canSales'));
    }
}
