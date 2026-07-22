<?php

namespace App\Http\Middleware;
use App\Models\Business;
use App\Models\Tailor as TailorAccount;
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
        if ($request->session()->get('tailor-login-success')) {
            $tailor = TailorAccount::find($request->session()->get('tailor_id'));
            $businessIsActive = $tailor && Business::where('owner_user_id', $tailor->user_id)
                ->where('status', Business::STATUS_ACTIVE)
                ->where('tailoring_enabled', true)
                ->exists();

            if ($businessIsActive) {
                return $next($request);
            }

            $request->session()->forget(['tailor-login-success', 'tailor', 'tailor_id']);
            return redirect('tailor-login')->with('failed', 'دکان کا اکاؤنٹ فعال نہیں ہے۔ دکان کے مالک سے رابطہ کریں۔');
        }

        return redirect('tailor-login')->with('failed','براہ کرم پہلے لاگ اِن کریں۔');
    }
}
