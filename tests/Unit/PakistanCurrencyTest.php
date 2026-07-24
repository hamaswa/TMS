<?php

namespace Tests\Unit;

use App\Support\PakistanCurrency;
use Tests\TestCase;

class PakistanCurrencyTest extends TestCase
{
    public function test_it_formats_pakistan_rupees_for_english_and_urdu(): void
    {
        $this->assertSame('Rs 1,450.00', PakistanCurrency::format(1450, 'en'));
        $this->assertSame('1,450.00 روپے', PakistanCurrency::format(1450, 'ur'));
        $this->assertSame('Rs 0.00', PakistanCurrency::format(null, 'en'));
    }

    public function test_it_provides_stable_iso_amounts_for_pkr_metadata(): void
    {
        $this->assertSame('1450.00', PakistanCurrency::isoAmount(1450));
        $this->assertSame('-50.25', PakistanCurrency::isoAmount(-50.25));
        $this->assertSame(
            '<bdi class="money">1,450.00 روپے</bdi>',
            PakistanCurrency::html(1450, 'ur')->toHtml()
        );
    }
}
