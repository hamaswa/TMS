<?php

namespace App\Http\Middleware;
use session;
use Closure;
use Illuminate\Http\Request;
class Tailor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->session()->get('tailor-login-success'))
        {
            return $next($request);
        }else{
            return redirect('tailor-login')->with('failed','Please Login First');
        }
    }
}
