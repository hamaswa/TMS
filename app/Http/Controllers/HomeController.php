<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\ClothColor;
use App\Models\Customers;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\SaleSession;
use App\Models\SaleStock;
use App\Models\User;
use Illuminate\Support\Carbon;
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

        if ($modules === []) {
            $landingRoute = $this->managementLandingRoute($user);
            abort_unless($landingRoute, 403, 'آپ کے اکاؤنٹ کے لیے کوئی کاروباری سہولت فعال نہیں ہے۔');

            return redirect()->route($landingRoute);
        }

        return view('dashboard.select');
    }

    public function switch(string $workspace)
    {
        abort_unless(in_array($workspace, Auth::user()->enabledModules(), true), 403, 'یہ ورک اسپیس آپ کے اکاؤنٹ کے لیے فعال نہیں ہے۔');

        Auth::user()->forceFill(['preferred_workspace' => $workspace])->save();

        return redirect()->route($workspace === User::MODULE_TAILORING
            ? 'admin.dashboard.tailoring'
            : 'admin.dashboard.clothing');
    }

    public function current()
    {
        $workspace = Auth::user()->preferred_workspace;
        $workspace = $workspace === 'shop' ? User::MODULE_CLOTHING : $workspace;

        if ($workspace && Auth::user()->hasModule($workspace)) {
            return $this->switch($workspace);
        }

        return redirect()->route('admin.home');
    }

    public function tailoring()
    {
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

        $dashboardOrders = fn () => Order::where('userId', $ownerId)
            ->whereHas('customers', fn ($query) => $query->where('user_id', $ownerId))
            ->with([
                'customers' => fn ($query) => $query->where('user_id', $ownerId)
                    ->select('id', 'name', 'serial_number'),
                'tailor' => fn ($query) => $query->where('user_id', $ownerId)
                    ->select('id', 'name'),
            ])
            ->orderByRaw('CASE WHEN returnDate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('returnDate')
            ->orderBy('id');
        $operationalOrders = collect();
        $overdueOrders = collect();
        $readyForHandoverOrders = collect();

        if ($canWorkshop || $canOrders) {
            $operationalOrders = $dashboardOrders()
                ->whereDate('returnDate', '>=', today())
                ->whereNotIn('status', ['ready', 'delivered'])
                ->limit(5)
                ->get();
            $overdueOrders = $dashboardOrders()
                ->whereDate('returnDate', '<', today())
                ->whereNotIn('status', ['ready', 'delivered'])
                ->limit(5)
                ->get();
            $readyForHandoverOrders = $dashboardOrders()
                ->where('status', 'ready')
                ->limit(5)
                ->get();
        }

        return view('dashboard.tailoring', compact(
            'tailoring', 'canWorkshop', 'canOrders', 'operationalOrders',
            'overdueOrders', 'readyForHandoverOrders'
        ));
    }

    public function clothing()
    {
        $user = Auth::user();
        $ownerId = $user->businessOwnerId();
        $canInventory = $user->hasBusinessPermission('clothing.inventory');
        $canPurchases = $user->hasBusinessPermission('clothing.purchases');
        $canSales = $user->hasBusinessPermission('clothing.sales');
        $colors = ClothColor::where('user_id', $ownerId);
        $monthSalesQuery = SaleStock::where('user_id', $ownerId)
            ->financiallyActive()
            ->whereBetween('sellDate', [now()->startOfMonth(), now()->endOfMonth()]);
        $clothing = [
            'meters' => $canInventory ? (float) (clone $colors)->sum('length') : null,
            'inventory_value' => $canInventory ? (float) (clone $colors)->sum(DB::raw('length * average_unit_cost')) : null,
            'low_stock' => $canInventory ? (clone $colors)->where('length', '<=', 5)->count() : null,
            'draft_purchases' => $canPurchases ? Purchase::where('user_id', $ownerId)->where('status', 'draft')->count() : null,
            'month_sales' => $canSales ? (float) (clone $monthSalesQuery)->sum(DB::raw('selling_price * length')) : null,
            'today_sales' => $canSales ? (float) SaleStock::where('user_id', $ownerId)
                ->financiallyActive()->whereDate('sellDate', today())->sum(DB::raw('selling_price * length')) : null,
            'new_customers_week' => $canSales ? Customers::where('user_id', $ownerId)->whereNull('parent_id')->where('is_walk_in', false)
                ->whereBetween('first_sale_at', [now()->startOfWeek(), now()->endOfWeek()])->count() : null,
            'new_customers_month' => $canSales ? Customers::where('user_id', $ownerId)->whereNull('parent_id')->where('is_walk_in', false)
                ->whereBetween('first_sale_at', [now()->startOfMonth(), now()->endOfMonth()])->count() : null,
        ];

        $salesTrend = collect();
        $recentSales = collect();
        $liveSaleSessions = collect();
        if ($canSales) {
            $trendStart = today()->subDays(6);
            $salesByDate = SaleStock::where('user_id', $ownerId)->financiallyActive()
                ->whereBetween('sellDate', [$trendStart->copy()->startOfDay(), now()->endOfDay()])
                ->get(['sellDate', 'selling_price', 'length'])
                ->groupBy(fn (SaleStock $sale) => Carbon::parse($sale->sellDate)->toDateString())
                ->map(fn ($sales) => (float) $sales->sum(fn (SaleStock $sale) => (float) $sale->selling_price * (float) $sale->length));
            $salesTrend = collect(range(0, 6))->map(function (int $offset) use ($trendStart, $salesByDate) {
                $date = $trendStart->copy()->addDays($offset);

                return ['date' => $date, 'total' => (float) ($salesByDate[$date->toDateString()] ?? 0)];
            });
            $recentSales = SaleStock::where('user_id', $ownerId)->financiallyActive()
                ->with(['brand', 'type'])->latest('sellDate')->latest('id')->limit(5)->get();
            $liveSaleSessions = SaleSession::where('user_id', $ownerId)
                ->whereIn('status', [
                    SaleSession::STATUS_ACTIVE,
                    SaleSession::STATUS_NEEDS_ATTENTION,
                    SaleSession::STATUS_CLAIMED,
                ])
                ->with(['agent:id,name', 'claimedBy:id,name'])
                ->latest('updated_at')->limit(6)->get();
        }

        $recentPurchases = $canPurchases
            ? Purchase::where('user_id', $ownerId)->with('supplier')->latest('purchase_date')->latest('id')->limit(5)->get()
            : collect();
        $lowStockItems = $canInventory
            ? ClothColor::where('user_id', $ownerId)->where('length', '<=', 5)
                ->with(['cloth.type', 'cloth.brand'])->orderBy('length')->limit(6)->get()
            : collect();

        return view('dashboard.clothing', compact(
            'clothing', 'canInventory', 'canPurchases', 'canSales',
            'salesTrend', 'recentSales', 'liveSaleSessions', 'recentPurchases', 'lowStockItems'
        ));
    }

    private function managementLandingRoute(User $user): ?string
    {
        return match (true) {
            $user->hasBusinessPermission(BusinessRole::FINANCE_VIEW) => 'admin.financial-reports.index',
            $user->hasBusinessPermission(BusinessRole::EXPENSES_MANAGE) => 'admin.expense.index',
            $user->hasBusinessPermission(BusinessRole::TEAM_MANAGE) => 'admin.team.index',
            $user->hasBusinessPermission(BusinessRole::ACTIVITY_VIEW) => 'admin.activity.index',
            $user->hasBusinessPermission(BusinessRole::SETTINGS_MANAGE) => 'admin.setting.index',
            default => null,
        };
    }
}
