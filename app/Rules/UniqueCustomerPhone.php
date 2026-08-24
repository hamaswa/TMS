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
        private readonly ?string $message = null,
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
            ->whereNull('parent_id')
            ->where('phone_number1_normalized', $normalized)
            ->when($this->ignoreCustomerId, fn ($query) => $query->where('id', '!=', $this->ignoreCustomerId))
            ->exists();

        $legacyConflictExists = Customers::withTrashed()
            ->where('user_id', $this->ownerId)
            ->whereNull('parent_id')
            ->where('phone_normalization_conflict', true)
            ->whereNull('phone_number1_normalized')
            ->when($this->ignoreCustomerId, fn ($query) => $query->where('id', '!=', $this->ignoreCustomerId))
            ->get()
            ->contains(fn (Customers $customer) => PakistanPhoneNumber::normalize($customer->phone_number1) === $normalized);

        if ($exists || $legacyConflictExists) {
            $fail($this->message ?? 'اس موبائل نمبر کے ساتھ گاہک پہلے سے موجود ہے۔');
        }
    }
}
