<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /** Pass an English message for English-only screens such as the super admin's. */
    public function __construct(private readonly ?string $message = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if (mb_strlen($password) < 8
            || preg_match('/[a-z]/', $password) !== 1
            || preg_match('/[A-Z]/', $password) !== 1
            || preg_match('/[0-9]/', $password) !== 1
            || preg_match('/[^A-Za-z0-9]/', $password) !== 1) {
            $fail($this->message ?? 'پاس ورڈ کم از کم 8 حروف کا ہو اور اس میں بڑا حرف، چھوٹا حرف، عدد اور علامت شامل ہوں۔');
        }
    }
}
