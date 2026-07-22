<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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
    protected function authenticated(Request $request, $user)
    {
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
