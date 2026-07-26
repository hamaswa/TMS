<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Business;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailor_logs_in_with_phone_and_sees_the_urdu_portal(): void
    {
        $owner = User::factory()->create();
        $tailor = $this->tailor($owner, '03001234567');
        $shopCode = $owner->fresh()->ownedBusiness->shop_code;

        $this->get('/tailor-login')
            ->assertOk()
            ->assertSeeText('درزی لاگ اِن')
            ->assertSeeText('دکان کا کوڈ')
            ->assertSee('lang="ur"', false)
            ->assertDontSeeText('Tailor Login');

        $this->post('/tailor-login', [
            'shop_code' => strtolower($shopCode),
            'contact' => '03001234567',
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor/tailor-dashboard')
            ->assertSessionHas('tailor_id', $tailor->id);
    }

    public function test_shop_code_selects_the_correct_business_when_tailor_phones_match(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();
        $first = $this->tailor($firstOwner, '03009999999');
        $second = $this->tailor($secondOwner, '03009999999');

        $this->post('/tailor-login', [
            'shop_code' => $secondOwner->fresh()->ownedBusiness->shop_code,
            'contact' => '03009999999',
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor/tailor-dashboard')
            ->assertSessionHas('tailor_id', $second->id)
            ->assertSessionMissing('failed');
        $this->assertNotSame($first->id, $second->id);

        $this->post('/tailor-login', [
            'shop_code' => 'TMS-999999',
            'contact' => '03009999999',
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor-login')
            ->assertSessionHas('failed', 'دکان کا کوڈ، فون نمبر یا پاس ورڈ درست نہیں ہے۔')
            ->assertSessionMissing('tailor_id');
    }

    public function test_client_can_see_the_shop_code_and_suspended_business_cannot_use_tailor_portal(): void
    {
        $owner = User::factory()->create();
        $tailor = $this->tailor($owner, '03007770000');
        $business = $owner->fresh()->ownedBusiness;

        $this->actingAs($owner)->get(route('admin.setting.index'))
            ->assertOk()->assertSeeText($business->shop_code)->assertSeeText('درزی پورٹل کا دکان کوڈ');

        $business->update(['status' => Business::STATUS_SUSPENDED]);
        $this->post('/tailor-login', [
            'shop_code' => $business->shop_code,
            'contact' => $tailor->phone_number1,
            'password' => 'Tailor@2026',
        ])->assertRedirect('/tailor-login')->assertSessionMissing('tailor_id');

        $this->withSession([
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ])->get('/tailor/tailor-dashboard')
            ->assertRedirect('/tailor-login')
            ->assertSessionHas('failed', 'دکان کا اکاؤنٹ فعال نہیں ہے۔ دکان کے مالک سے رابطہ کریں۔');
    }

    public function test_tailor_dashboard_shows_current_month_worker_earnings_and_outstanding_amount(): void
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
        $order->forceFill(['created_at' => now()])->save();

        $this->withSession([
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ])->get('/tailor/tailor-dashboard')
            ->assertOk()
            ->assertSeeText('ماہِ رواں')
            ->assertSeeText('روپے 800.00')
            ->assertSeeText('روپے 300.00')
            ->assertSeeText('روپے 500.00')
            ->assertSeeText('1 آرڈرز')
            ->assertDontSeeText('روپے 4,000.00');
    }

    private function tailor(User $owner, string $phone): Tailor
    {
        $owner->forceFill([
            'tailoring_access' => true,
            'is_business_owner' => true,
        ])->save();
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $business = Business::firstOrCreate(
            ['owner_user_id' => $owner->id],
            [
                'name' => $owner->name,
                'tailoring_enabled' => true,
                'clothing_enabled' => false,
                'status' => Business::STATUS_ACTIVE,
            ],
        );
        $owner->forceFill(['business_id' => $business->id])->save();

        return Tailor::create([
            'name' => 'رشید محمود',
            'phone_number1' => $phone,
            'password' => bcrypt('Tailor@2026'),
            'user_id' => $owner->id,
        ]);
    }
}
