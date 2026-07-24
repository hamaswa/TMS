<?php

namespace App\Http\Middleware;

use App\Models\Storefront;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public const SUPPORTED = ['ur', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('public_locale');
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $request->cookie('tms_public_locale');
        }
        if (! in_array($locale, self::SUPPORTED, true)) {
            $storefront = $request->route('storefront');
            $locale = $storefront instanceof Storefront ? $storefront->default_locale : null;
        }

        App::setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'ur');

        return $next($request);
    }
}
