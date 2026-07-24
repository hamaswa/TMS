<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class PakistanCurrency
{
    public const CODE = 'PKR';

    public static function format(mixed $amount, ?string $locale = null): string
    {
        $formatted = number_format((float) $amount, 2, '.', ',');

        return ($locale ?? app()->getLocale()) === 'ur'
            ? $formatted.' روپے'
            : 'Rs '.$formatted;
    }

    public static function html(mixed $amount, ?string $locale = null): HtmlString
    {
        return new HtmlString(
            '<bdi class="money">'.e(self::format($amount, $locale)).'</bdi>'
        );
    }

    public static function isoAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
