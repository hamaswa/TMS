<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ApplyUiLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class UiLocaleController extends Controller
{
    /**
     * Switches the interface between Urdu and English and returns to the same page.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put(ApplyUiLocale::SESSION_KEY, $locale);
        Cookie::queue(Cookie::forever(ApplyUiLocale::COOKIE, $locale));

        return redirect()->back(fallback: url('/'));
    }
}
