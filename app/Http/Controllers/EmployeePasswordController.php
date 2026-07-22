<?php

namespace App\Http\Controllers;

use Closure;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeePasswordController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->business_id && ! $request->user()->isBusinessOwner(), 403);

        return view('auth.employee-password', [
            'forced' => $request->user()->must_change_password,
            'expired' => $request->user()->employeePasswordExpired(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->business_id && ! $user->isBusinessOwner(), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                new StrongPassword,
                'confirmed',
                function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('نیا پاس ورڈ عارضی یا موجودہ پاس ورڈ سے مختلف ہونا چاہیے۔');
                    }
                },
            ],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => null,
        ])->save();

        return redirect()->route('admin.home')->with('success', 'آپ کا نیا پاس ورڈ محفوظ ہو گیا ہے۔');
    }
}
