<?php

namespace App\Support;

final class PaymentMethods
{
    public const LABELS = [
        'cash' => 'نقد',
        'easypaisa' => 'ایزی پیسہ',
        'jazzcash' => 'جاز کیش',
        'bank_transfer' => 'بینک ٹرانسفر',
        'raast' => 'راست',
        'cheque' => 'چیک',
        'other' => 'دیگر',
    ];

    public static function requiresReference(?string $method): bool
    {
        return $method !== null && ! in_array($method, ['cash', 'other'], true);
    }
}
