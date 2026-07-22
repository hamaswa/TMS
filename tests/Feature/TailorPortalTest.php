<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TailorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailor_logs_in_with_phone_and_sees_the_urdu_portal(): void
    {
        $owner = User::factory()->create();
        $tailor = $this->tailor($owner, '03001234567');

        $this->get('/tailor-login')
            ->assertOk()
            ->assertSeeText('درزی لاگ اِن')
            ->assertSee('lang="ur"', false)
            ->assertDontSeeText('Tailor Login');

        $this->post('/tailor-login', [
            'contact' => '03001234567',
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor/tailor-dashboard')
            ->assertSessionHas('tailor_id', $tailor->id);
    }

    public function test_duplicate_tailor_phone_is_rejected_instead_of_opening_the_wrong_business(): void
    {
        $this->tailor(User::factory()->create(), '03009999999');
        $this->tailor(User::factory()->create(), '03009999999');

        $this->post('/tailor-login', [
            'contact' => '03009999999',
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor-login')
            ->assertSessionHas('failed', 'یہ فون نمبر ایک سے زیادہ کاروبار میں موجود ہے۔ دکان کے مالک سے رابطہ کریں۔')
            ->assertSessionMissing('tailor_id');
    }

    public function test_tailor_dashboard_uses_worker_earnings_not_customer_order_total(): void
    {
        $owner = User::factory()->create();
        $tailor = $this->tailor($owner, '03001112222');
        $customer = Customers::create([
            'name' => 'فیصل محمود',
            'phone_number1' => '03005551234',
            'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 2,
            'totalPayment' => 4000,
            'tailorId' => $tailor->id,
            'tailor_price' => 400,
            'tailor_paid_amount' => 300,
            'returnDate' => now()->addDays(3)->toDateString(),
            'userId' => $owner->id,
            'status' => 'assigned',
        ]);
        $order->forceFill(['created_at' => now()->subWeek()])->save();

        $this->withSession([
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ])->get('/tailor/tailor-dashboard')
            ->assertOk()
            ->assertSeeText('روپے 800.00')
            ->assertSeeText('روپے 300.00')
            ->assertDontSeeText('روپے 4,000.00');
    }

    private function tailor(User $owner, string $phone): Tailor
    {
        return Tailor::create([
            'name' => 'رشید محمود',
            'phone_number1' => $phone,
            'password' => bcrypt('Tailor@2026'),
            'user_id' => $owner->id,
        ]);
    }
}
