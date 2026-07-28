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

    public function test_tailoring_client_can_create_a_customer_with_default_design_options(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => false]);
        $owner->assignRole($role);

        $this->actingAs($owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSeeText('بنیادی معلومات')
            ->assertSeeText('پیمائش')
            ->assertSeeText('سلائی کی پسند')
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

    public function test_client_cannot_create_same_mobile_in_local_and_international_formats(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        Customers::create([
            'name' => 'Existing Customer',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Duplicate Customer',
            'contact' => '+92 300 1234567',
        ])->assertSessionHasErrors('contact');

        $this->assertDatabaseCount('customers', 1);
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
