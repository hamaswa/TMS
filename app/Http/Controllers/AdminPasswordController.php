<?php

namespace App\Http\Controllers;

use App\Rules\StrongPassword;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Super admin: change their own password. */
class AdminPasswordController extends Controller
{
    public function edit()
    {
        return view('Administrator.password');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                new StrongPassword('Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.'),
                'confirmed',
                function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('The new password must be different from your current password.');
                    }
                },
            ],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.confirmed' => 'The new passwords do not match.',
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => null,
        ])->save();

        // Sign out other browsers that still use the old password.
        Auth::logoutOtherDevices($validated['password']);

        return redirect()->route('administrator.password.edit')->with('success', 'Your password has been changed.');
    }
}
