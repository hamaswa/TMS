<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * Dates for shop pages as "18 اکتوبر 2026". The span keeps day, month and year in order inside
 * right-to-left pages; English mode translates the month and flips the span to left-to-right.
 */
class DisplayDate
{
    public const MONTHS = ['جنوری', 'فروری', 'مارچ', 'اپریل', 'مئی', 'جون', 'جولائی', 'اگست', 'ستمبر', 'اکتوبر', 'نومبر', 'دسمبر'];

    public static function long(CarbonInterface|string|null $date): HtmlString
    {
        if (blank($date)) {
            return new HtmlString('');
        }
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return new HtmlString('<span dir="rtl">'.$date->day.' '.self::MONTHS[$date->month - 1].' '.$date->year.'</span>');
    }
}
