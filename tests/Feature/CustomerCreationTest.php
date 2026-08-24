<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_list_exposes_a_clear_create_customer_action(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'is_business_owner' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'محمد اسلم',
            'phone_number1' => '03001234567',
        ]);

        $this->actingAs($owner)->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSee(route('admin.Customers.create'), false)
            ->assertSeeText('نیا گاہک شامل کریں')
            ->assertSee('aria-label="'.$customer->name.' کی ادائیگی درج کریں"', false);
    }

    public function test_customer_directory_uses_a_limited_initial_list_and_ajax_server_search(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'is_business_owner' => true]);
        $owner->assignRole($role);

        foreach (range(1, 26) as $number) {
            Customers::create([
                'user_id' => $owner->id,
                'name' => sprintf('Directory Customer %02d', $number),
                'phone_number1' => '0300'.str_pad((string) $number, 7, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($owner)->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSeeText('Directory Customer 26')
            ->assertDontSeeText('Directory Customer 01')
            ->assertSeeText('26');

        $response = $this->actingAs($owner)->getJson(route('admin.customers.search', [
            'search' => 'Directory Customer 01',
        ]))->assertOk()->assertJsonPath('count', 1);

        $this->assertStringContainsString('Directory Customer 01', $response->json('html'));
        $this->assertStringNotContainsString('Directory Customer 02', $response->json('html'));
    }

    public function test_tailoring_client_can_create_a_customer_with_default_design_options(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => false]);
        $owner->assignRole($role);

        $this->actingAs($owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSeeText('بنیادی معلومات')
            ->assertSeeText('پیمائش اور سلائی کی پسند')
            ->assertSee('class="customer-details-grid"', false)
            ->assertSee('class="combined-panel measurement-panel"', false)
            ->assertSee('class="combined-panel preference-panel"', false)
            ->assertSee('class="form-control js-no-wheel-number"', false)
            ->assertDontSee('data-step="3"', false)
            ->assertSeeText('مشترکہ گاہک اکاؤنٹ');

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'QA Urdu Customer',
            'contact' => '03001234567',
            'length' => 42,
            'arms' => 24,
            'teraa' => 18,
            'senaChorai' => 22,
            'damanchorai' => 24,
            'shalwar' => 40,
            'pancha' => 8,
            'shalwarGheer' => 26,
            'monda' => 10,
            'chuta' => 15,
            'note' => 'اردو ٹیسٹ ریکارڈ',
            'mobile_pin' => '482913',
        ])->assertRedirect('admin/Customers')
            ->assertSessionHas('customer_pin', '482913');

        $this->assertDatabaseHas('customers', [
            'name' => 'QA Urdu Customer',
            'user_id' => $owner->id,
            'Daaman' => '0',
            'phone_number1_normalized' => '+923001234567',
        ]);
        $this->assertTrue(Hash::check('482913', Customers::firstOrFail()->mobile_pin));
        $customer = Customers::firstOrFail();
        $this->actingAs($owner)->get(route('admin.Customers.edit', $customer))
            ->assertOk()
            ->assertSeeInOrder(['name="chuta"', 'value="15"'], false);
    }

    public function test_duplicate_mobile_prompts_the_owner_to_use_the_existing_customer_or_add_a_profile(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $existing = Customers::create([
            'name' => 'Existing Customer',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Duplicate Customer',
            'contact' => '+92 300 1234567',
        ])->assertSessionHas('duplicate_customer', fn (array $customer) => $customer['id'] === $existing->id)
            ->assertSessionHasInput('name', 'Duplicate Customer');

        $this->assertDatabaseCount('customers', 1);

        $expectedUrl = url('admin/Customers').'?'.http_build_query([
            'customer' => $existing->id,
            'search' => $existing->phone_number1,
        ]).'#orderDetail';

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Duplicate Customer',
            'contact' => '+92 300 1234567',
            'duplicate_action' => 'use_existing',
        ])->assertRedirect($expectedUrl);

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_owner_can_add_a_secondary_measurement_profile_for_an_existing_phone(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $existing = Customers::create([
            'name' => 'Main Customer',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Second Measurement Profile',
            'contact' => '+92 300 1234567',
            'duplicate_action' => 'create_profile',
            'length' => 44,
            'arms' => 25,
        ])->assertSessionHas('insert');

        $profile = Customers::where('parent_id', $existing->id)->firstOrFail();
        $this->assertSame('Second Measurement Profile', $profile->name);
        $this->assertSame('03001234567', $existing->fresh()->phone_number1);
        $this->assertNull($profile->phone_number1_normalized);
        $this->assertTrue($profile->phone_normalization_conflict);
        $this->assertSame($existing->id, Customers::findByPhoneForOwner($owner->id, '+92 300 1234567')?->id);
    }

    public function test_client_can_reset_customer_pin_and_existing_mobile_sessions_are_revoked(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'PIN Reset Customer',
            'phone_number1' => '03007654321',
            'user_id' => $owner->id,
            'mobile_pin' => Hash::make('123456'),
        ]);
        $customer->createToken('old-device');

        $this->actingAs($owner)->put(route('admin.Customers.update', $customer), [
            'name' => $customer->name,
            'contact' => $customer->phone_number1,
            'mobile_pin' => '654321',
            'add_daaman_type' => '0 - 0',
            'plate_type' => '0 - 0',
            'add_neck_type' => '0 - 0',
            'add_pocket_type' => '0 - 0',
            'add_button_type' => '0 - 0',
            'add_sewing_type' => '0 - 0',
            'add_shirt_button_type' => '0 - 0',
            'add_sleeve_opening_type' => '0 - 0',
        ])->assertRedirect('admin/Customers')
            ->assertSessionHas('customer_pin', '654321');

        $this->assertTrue(Hash::check('654321', $customer->fresh()->mobile_pin));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => Customers::class,
            'tokenable_id' => $customer->id,
        ]);
    }
}
