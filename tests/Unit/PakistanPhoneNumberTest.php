<?php

namespace Tests\Unit;

use App\Support\PakistanPhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PakistanPhoneNumberTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_it_normalizes_supported_pakistan_mobile_formats(string $input): void
    {
        $this->assertSame('+923001234567', PakistanPhoneNumber::normalize($input));
        $this->assertSame('03001234567', PakistanPhoneNumber::local($input));
    }

    public static function validPhones(): array
    {
        return [
            ['03001234567'],
            ['+923001234567'],
            ['92 300 1234567'],
            ['0092-300-1234567'],
            ['۰۳۰۰۱۲۳۴۵۶۷'],
            ['٠٣٠٠١٢٣٤٥٦٧'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_it_rejects_incomplete_or_non_mobile_numbers(?string $input): void
    {
        $this->assertNull(PakistanPhoneNumber::normalize($input));
    }

    public static function invalidPhones(): array
    {
        return [[null], [''], ['0511234567'], ['0300123456'], ['+9230012345678']];
    }
}
