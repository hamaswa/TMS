<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Setting;
use App\Models\Tailor;
use App\Models\Tailorsalary;
use App\Models\User;
use Database\Seeders\DemoShopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('demo.enabled', true);
        config()->set('demo.email', 'demo@buynstitch.com');
        config()->set('demo.password', 'Demo@2026');
        $this->seed(DemoShopSeeder::class);
    }

    public function test_homepage_links_to_a_prefilled_demo_login(): void
    {
        $demoUrl = route('login', ['demo' => 1]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee($demoUrl)
            ->assertSeeText('ڈیمو دیکھیں');

        $this->get($demoUrl)
            ->assertOk()
            ->assertSeeText('ڈیمو اکاؤنٹ تیار ہے')
            ->assertSee('value="demo@buynstitch.com"', false)
            ->assertSee('value="Demo@2026"', false);

        $this->post(route('login'), [
            'email' => config('demo.email'),
            'password' => config('demo.password'),
        ])
            ->assertRedirect(route('admin.home'));

        $owner = User::where('email', config('demo.email'))->firstOrFail();
        $this->assertAuthenticatedAs($owner);
        $tailorId = Tailor::where('user_id', $owner->id)->value('id');
        $rate = Tailorsalary::where('tailor_id', $tailorId)->firstOrFail();
        $this->assertNotNull($rate->options_id);
        $this->assertDatabaseHas('options', ['id' => $rate->options_id, 'user_id' => $owner->id]);

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSeeText('آپ آج کون سا شعبہ سنبھالنا چاہتے ہیں؟')
            ->assertSeeText('ٹیلرنگ ورک اسپیس')
            ->assertSeeText('دکان اور فروخت ورک اسپیس')
            ->assertDontSee('class="main-content px-3 px-md-4 pt-3" role="status"', false);
    }

    public function test_demo_account_cannot_delete_or_change_protected_settings(): void
    {
        $owner = User::where('email', config('demo.email'))->firstOrFail();
        $setting = Setting::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('admin.delete-setting', $setting->id))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('settings', ['id' => $setting->id]);
    }

    public function test_demo_reset_removes_visitor_changes_and_restores_sample_data(): void
    {
        $owner = User::where('email', config('demo.email'))->firstOrFail();
        Customers::create([
            'name' => 'Temporary Demo Visitor',
            'phone_number1' => '03009999999',
            'user_id' => $owner->id,
        ]);

        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();

        $replacement = User::where('email', config('demo.email'))->firstOrFail();
        $this->assertTrue($replacement->business->isDemo());
        $this->assertDatabaseMissing('customers', ['name' => 'Temporary Demo Visitor']);
        $this->assertDatabaseHas('customers', [
            'user_id' => $replacement->id,
            'name' => 'Muhammad Aslam',
        ]);
    }
}
