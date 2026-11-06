<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyUiLocale;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NoPlanNoticeTest extends TestCase
{
    use RefreshDatabase;

    private const URDU_BANNER = 'آپ ہمارے کسی پلان پر نہیں ہیں۔';

    private const URDU_POPUP_TITLE = 'آپ ابھی کسی پلان پر نہیں ہیں';

    public function test_owner_without_plan_gets_popup_once_and_banner_on_every_page(): void
    {
        [$owner] = $this->owner();

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertOk()
            ->assertSee(self::URDU_BANNER)
            ->assertSee(self::URDU_POPUP_TITLE)
            ->assertSee('no-plan-modal', false)
            ->assertSee('AudioContext', false);

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertOk()
            ->assertSee(self::URDU_BANNER)
            ->assertDontSee('no-plan-modal', false);
    }

    public function test_notice_is_english_when_owner_uses_english(): void
    {
        [$owner] = $this->owner();

        $this->actingAs($owner)->withSession([ApplyUiLocale::SESSION_KEY => 'en'])->get(route('admin.home'))
            ->assertOk()
            ->assertSee('You are not on any of our plans.')
            ->assertSee('You are not on any plan yet')
            ->assertSee('otherwise your account will be marked as inactive.')
            ->assertSee('View plans')
            ->assertDontSee(self::URDU_BANNER);
    }

    public function test_plans_page_lists_plans_for_owner_without_plan(): void
    {
        [$owner] = $this->owner();

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSee('id="plans"', false)
            ->assertSee('Business Pro')
            ->assertSee('Elite Boutique')
            ->assertDontSee('no-plan-modal', false);
    }

    public function test_owner_on_a_plan_sees_no_notice(): void
    {
        [$owner, $business] = $this->owner();
        $business->subscriptions()->create([
            'plan_name' => 'Business Pro',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'fee' => 2500,
        ]);

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee(self::URDU_BANNER)
            ->assertDontSee('no-plan-modal', false);
    }

    public function test_demo_shop_sees_no_notice(): void
    {
        [$owner, $business] = $this->owner();
        $business->forceFill(['is_demo' => true])->save();

        $this->actingAs($owner->fresh())->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee(self::URDU_BANNER);
    }

    public function test_notice_shows_deadline_and_days_left(): void
    {
        config(['subscriptions.no_plan_deadline' => '2026-12-01']);
        $this->travelTo(now()->setDate(2026, 11, 6)->setTime(10, 0));
        [$owner] = $this->owner();

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertOk()
            ->assertSee('اکاؤنٹ غیر فعال ہونے کی تاریخ:')
            ->assertSee('1 دسمبر 2026')
            ->assertSee('25 دن باقی');

        $this->actingAs($owner)->withSession([ApplyUiLocale::SESSION_KEY => 'en'])->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Account will be marked inactive on:')
            ->assertSee('1 December 2026');
    }

    public function test_shops_without_plan_are_suspended_only_after_deadline(): void
    {
        config(['subscriptions.no_plan_deadline' => '2026-12-01']);
        [$owner, $business] = $this->owner();
        [, $planned] = $this->owner('PLN-0001');
        $planned->subscriptions()->create([
            'plan_name' => 'Business Pro',
            'starts_on' => '2026-11-01',
            'ends_on' => '2027-01-01',
            'fee' => 2500,
        ]);
        [, $demo] = $this->owner('DEM-0001');
        $demo->forceFill(['is_demo' => true])->save();

        $this->travelTo(now()->setDate(2026, 11, 30)->setTime(23, 0));
        $this->artisan('subscriptions:suspend-shops-without-plan')->assertSuccessful();
        $this->assertSame(Business::STATUS_ACTIVE, $business->fresh()->status);

        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(0, 10));
        $this->artisan('subscriptions:suspend-shops-without-plan', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(Business::STATUS_ACTIVE, $business->fresh()->status);

        $this->artisan('subscriptions:suspend-shops-without-plan')->assertSuccessful();
        $business->refresh();
        $this->assertSame(Business::STATUS_SUSPENDED, $business->status);
        $this->assertSame(Business::NO_PLAN_SUSPENSION_REASON, $business->status_reason);
        $this->assertDatabaseHas('business_status_histories', [
            'business_id' => $business->id,
            'from_status' => Business::STATUS_ACTIVE,
            'to_status' => Business::STATUS_SUSPENDED,
        ]);
        $this->assertSame(Business::STATUS_ACTIVE, $planned->fresh()->status);
        $this->assertSame(Business::STATUS_ACTIVE, $demo->fresh()->status);

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'آپ نے مقررہ تاریخ تک کوئی پلان منتخب نہیں کیا، اس لیے آپ کا اکاؤنٹ غیر فعال کر دیا گیا ہے۔ پلان فعال کروانے کے لیے سپر ایڈمن سے رابطہ کریں۔']);
        $this->assertGuest();
    }

    private function owner(string $shopCode = 'NOP-0001'): array
    {
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $business = Business::create([
            'name' => 'No Plan Shop',
            'shop_code' => $shopCode,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$owner->fresh(), $business];
    }
}
