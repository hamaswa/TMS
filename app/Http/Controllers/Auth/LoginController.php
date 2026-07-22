<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\Business;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        // $this->middleware('auth')->only('logout');
    }

    protected function credentials(Request $request): array
    {
        $login = trim((string) $request->input('email'));

        return [
            filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $login,
            'password' => $request->input('password'),
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->business && ! $user->business->isActive()) {
            $status = $user->business->status;
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = match ($status) {
                Business::STATUS_PENDING => 'آپ کا اکاؤنٹ سپر ایڈمن کی منظوری کا منتظر ہے۔',
                Business::STATUS_REJECTED => 'آپ کے اکاؤنٹ کی درخواست منظور نہیں ہوئی۔ سپر ایڈمن سے رابطہ کریں۔',
                default => 'آپ کا اکاؤنٹ غیر فعال ہے۔ سپر ایڈمن سے رابطہ کریں۔',
            };

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        if ($user->business_id && ! $user->isBusinessOwner() && ! $user->employee_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'آپ کا ملازم اکاؤنٹ غیر فعال ہے۔']);
        }

        if ($user->business_id && ! $user->isBusinessOwner() && ($user->must_change_password || $user->employeePasswordExpired())) {
            return redirect()->route('employee.password.edit');
        }

        if ($user->isBusinessMember()) {
            $request->session()->forget('active_workspace');

            return redirect()->route('admin.home');
        }

        // Check if the authenticated user has the role of 'stock_seller'
        if ($user->hasRole('stock_seller')) {
            // Redirect the user to the appropriate stock-related route
            return redirect()->route('admin.stock.index');
        }

        // Check if the authenticated user has the role of 'stock_seller'
        if ($user->hasRole('user')) {
            // Redirect the user to the appropriate stock-related route
            return redirect()->route('user.shops');
        }
        if ($user->hasRole('administrative')) {
            // Redirect the user to the appropriate stock-related route
            return redirect()->route('administrator.index');
        }

        // For other users, use the default redirect logic
        return redirect()->intended($this->redirectPath());
    }
}
