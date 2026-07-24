<?php

namespace App\Support;

final class PakistanPhoneNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $value);

        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^03\d{9}$/', $digits)) {
            $digits = '92'.substr($digits, 1);
        }

        return preg_match('/^923\d{9}$/', $digits) ? '+'.$digits : null;
    }

    public static function local(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return $normalized ? '0'.substr($normalized, 3) : null;
    }
}
