<?php

namespace App\Http\Middleware;

use App\Models\BusinessActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordBusinessActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($request->hasSession()
            && in_array('errors', $request->session()->get('_flash.new', []), true)) {
            return $response;
        }

        $user = $request->user();
        if (! $user?->isBusinessMember() || ! $user->business_id) {
            return $response;
        }

        try {
            $parameters = collect($request->route()?->parameters() ?? [])->map(function ($value) {
                if ($value instanceof Model) {
                    return ['type' => class_basename($value), 'id' => $value->getKey()];
                }

                return is_scalar($value) ? (string) $value : null;
            })->filter(fn ($value) => $value !== null)->all();

            BusinessActivityLog::create([
                'business_id' => $user->business_id,
                'actor_user_id' => $user->id,
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'path' => $request->path(),
                'route_parameters' => $parameters ?: null,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
