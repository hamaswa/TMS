<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopHubRelay
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('shop_hub.sync_key');
        abort_if($key === '', 503, 'Shop Hub relay is not configured.');

        $timestamp = $request->header('X-Shop-Hub-Timestamp');
        $signature = $request->header('X-Shop-Hub-Signature');
        abort_unless($timestamp && ctype_digit($timestamp) && $signature, 401, 'Invalid Shop Hub signature.');
        abort_if(abs(now()->timestamp - (int) $timestamp) > 300, 401, 'Expired Shop Hub signature.');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $key);
        abort_unless(hash_equals($expected, $signature), 401, 'Invalid Shop Hub signature.');

        return $next($request);
    }
}
