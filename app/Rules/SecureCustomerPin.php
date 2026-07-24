<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureCustomerPin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pin = is_string($value) ? $value : '';
        $weakPins = [
            '000000', '111111', '222222', '333333', '444444',
            '555555', '666666', '777777', '888888', '999999',
            '012345', '123456', '234567', '345678', '456789',
            '987654', '876543', '765432', '654321', '543210',
        ];

        if (in_array($pin, $weakPins, true)) {
            $fail(__('storefront.messages.insecure_pin'));
        }
    }
}
