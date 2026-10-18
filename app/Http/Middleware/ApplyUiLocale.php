<?php

namespace App\Http\Middleware;

use App\Support\UiTranslator;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language (Urdu by default) and, in English mode,
 * translates the Urdu text of HTML and JSON responses.
 */
class ApplyUiLocale
{
    public const SESSION_KEY = 'ui_locale';
    public const COOKIE = 'tms_ui_locale';

    public function __construct(private readonly UiTranslator $translator) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::current($request);
        View::share('uiLocale', $locale);

        $response = $next($request);

        if ($locale !== 'en' || ! $this->translates($request)) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $response->setData($this->translator->translateJsonData($response->getData(true)));

            return $response;
        }

        $type = (string) $response->headers->get('Content-Type');
        $content = $response->getContent();
        if (is_string($content) && $content !== '' && ($type === '' || str_contains($type, 'text/html'))) {
            $response->setContent($this->translator->mirrorToLtr($this->translator->translateHtml($content)));
        }

        return $response;
    }

    public static function current(Request $request): string
    {
        $locale = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        $locale ??= $request->cookie(self::COOKIE);

        return in_array($locale, UiTranslator::LOCALES, true) ? $locale : 'ur';
    }

    private function translates(Request $request): bool
    {
        // Storefront pages have their own ur/en translations; the super admin is already English.
        $middleware = $request->route()?->gatherMiddleware() ?? [];

        return ! in_array('public.locale', $middleware, true)
            && ! $request->is('administrator', 'administrator/*', 'api/*');
    }
}
