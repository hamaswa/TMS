<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Short order-tracking link for printed QR codes, e.g. HTTPS://BUYNSTITCH.COM/T/3203/884CD1263F9F.
 *
 * Laravel's signed URL adds a 64-character signature, which makes the QR so dense that 80 mm thermal
 * printers smear it and phones can't scan it. This link carries a 12-character code derived from the
 * app key instead and forwards to the same signed tracking page. It is written in capitals so the QR
 * can use its compact letters-and-digits mode, giving fewer, bigger squares (web addresses work the same).
 */
class OrderTrackingLink
{
    private const CODE_LENGTH = 12;

    public static function url(int $orderId): string
    {
        return strtoupper(route('orders.track.short', ['order' => $orderId, 'code' => self::code($orderId)]));
    }

    public static function code(int $orderId): string
    {
        return substr(hash_hmac('sha256', 'order-track|'.$orderId, (string) config('app.key')), 0, self::CODE_LENGTH);
    }

    public static function isValid(int $orderId, string $code): bool
    {
        return hash_equals(self::code($orderId), strtolower($code));
    }

    /** The full signed tracking page the short link forwards to. */
    public static function signedUrl(int $orderId): string
    {
        return URL::signedRoute('orders.track', ['order' => $orderId]);
    }
}
