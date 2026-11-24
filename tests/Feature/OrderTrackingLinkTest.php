<?php

namespace Tests\Feature;

use App\Support\OrderTrackingLink;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** The short link printed in receipt QR codes. */
class OrderTrackingLinkTest extends TestCase
{
    public function test_short_link_is_short_and_in_capitals(): void
    {
        $url = OrderTrackingLink::url(3203);

        $this->assertSame(strtoupper($url), $url);
        $this->assertStringContainsString('/T/3203/', $url);
        $this->assertLessThan(60, strlen($url));
    }

    public function test_short_link_forwards_to_the_signed_tracking_page(): void
    {
        $code = OrderTrackingLink::code(3203);

        foreach (["/T/3203/$code", '/T/3203/'.strtoupper($code), "/t/3203/$code"] as $path) {
            $target = $this->get($path)->assertRedirect()->headers->get('Location');
            $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($target)), $path);
            $this->assertStringContainsString('/order-status/3203', $target);
        }
    }

    public function test_wrong_or_other_orders_code_is_refused(): void
    {
        $this->get('/T/3203/000000000000')->assertForbidden();
        $this->get('/T/3204/'.OrderTrackingLink::code(3203))->assertForbidden();
    }
}
