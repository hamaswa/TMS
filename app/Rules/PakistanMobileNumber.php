<?php

namespace App\Rules;

use App\Support\PakistanPhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PakistanMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PakistanPhoneNumber::normalize(is_string($value) ? $value : null)) {
            $fail('موبائل نمبر 03xx یا +92 کی درست پاکستانی شکل میں درج کریں۔');
        }
    }
}
