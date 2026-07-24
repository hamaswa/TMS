<?php

namespace App\Rules;

use App\Models\Customers;
use App\Support\PakistanPhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueCustomerPhone implements ValidationRule
{
    public function __construct(
        private readonly int $ownerId,
        private readonly ?int $ignoreCustomerId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = PakistanPhoneNumber::normalize(is_string($value) ? $value : null);
        if (! $normalized) {
            return;
        }

        if ($this->ignoreCustomerId) {
            $current = Customers::withTrashed()->find($this->ignoreCustomerId);
            if ($current && PakistanPhoneNumber::normalize($current->phone_number1) === $normalized) {
                return;
            }
        }

        $exists = Customers::withTrashed()
            ->where('user_id', $this->ownerId)
            ->where('phone_number1_normalized', $normalized)
            ->when($this->ignoreCustomerId, fn ($query) => $query->where('id', '!=', $this->ignoreCustomerId))
            ->exists();

        if ($exists) {
            $fail('اس موبائل نمبر کے ساتھ گاہک پہلے سے موجود ہے۔');
        }
    }
}
