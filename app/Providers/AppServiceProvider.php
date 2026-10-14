<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Models\BusinessRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer('customer.partials.action-menu', function ($view) {
            $user = Auth::user();

            $view->with([
                'canViewBalances' => $user
                    ? $user->hasBusinessPermission(BusinessRole::CUSTOMER_BALANCES)
                    : false,

                'canManageMeasurements' => $user
                    ? $user->hasModule(User::MODULE_TAILORING)
                    && $user->hasBusinessPermission(BusinessRole::TAILORING_CUSTOMERS)
                    : false,

                'canViewTailoringOrders' => $user
                    ? $user->hasModule(User::MODULE_TAILORING)
                    && $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS)
                    : false,

                'canCreateTailoringOrder' => $user
                    ? $user->hasBusinessPermission(BusinessRole::TAILORING_ORDERS)
                    || $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES)
                    : false,
            ]);
        });
    }
}
