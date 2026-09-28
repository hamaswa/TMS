<?php

namespace App\Http\Middleware;

use App\Models\BusinessRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesAgentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user
            && $user->isBusinessMember()
            && $user->hasAnyRole(['business_employee', 'stock_seller', 'shop_owner'])
            && $user->hasModule(User::MODULE_CLOTHING)
            && $user->hasBusinessPermission(BusinessRole::CLOTHING_SALES)
            && ($user->isBusinessOwner() || $user->employee_active)
            && $user->business?->isActive()
            && ! $user->must_change_password
            && ! $user->employeePasswordExpired(), 403, 'Sales Agent access is no longer available for this account.');

        return $next($request);
    }
}
