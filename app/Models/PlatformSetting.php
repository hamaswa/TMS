<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value settings that belong to the platform rather than one shop,
 * such as the Easypaisa account that shops pay their subscription into.
 */
class PlatformSetting extends Model
{
    public const EASYPAISA_ACCOUNT_NAME = 'easypaisa_account_name';

    public const EASYPAISA_NUMBER = 'easypaisa_number';

    public const EASYPAISA_QR_PATH = 'easypaisa_qr_path';

    private const CACHE_KEY = 'platform-settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /** The Easypaisa details shown to shops, or null until a number is saved. */
    public static function easypaisa(): ?array
    {
        $number = self::get(self::EASYPAISA_NUMBER);
        if (blank($number)) {
            return null;
        }

        return [
            'account_name' => self::get(self::EASYPAISA_ACCOUNT_NAME),
            'number' => $number,
            'qr_path' => self::get(self::EASYPAISA_QR_PATH),
        ];
    }
}
