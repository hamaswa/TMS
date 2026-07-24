<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetPublicLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class PublicLocaleController extends Controller
{
    public function update(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetPublicLocale::SUPPORTED, true), 404);
        $request->session()->put('public_locale', $locale);
        Cookie::queue('tms_public_locale', $locale, 60 * 24 * 365);

        $target = (string) $request->query('redirect', '/');
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = '/';
        }

        return redirect($target);
    }
}
