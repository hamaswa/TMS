<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;

class EnsureBusinessActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $business = $user?->business;

        if ($business && ! $business->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = match ($business->status) {
                Business::STATUS_PENDING => 'آپ کا اکاؤنٹ سپر ایڈمن کی منظوری کا منتظر ہے۔',
                Business::STATUS_REJECTED => 'آپ کے اکاؤنٹ کی درخواست منظور نہیں ہوئی۔ سپر ایڈمن سے رابطہ کریں۔',
                default => 'آپ کا اکاؤنٹ غیر فعال ہے۔ سپر ایڈمن سے رابطہ کریں۔',
            };

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
