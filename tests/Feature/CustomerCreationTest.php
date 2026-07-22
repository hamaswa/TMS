<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailoring_client_can_create_a_customer_with_default_design_options(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => false]);
        $owner->assignRole($role);

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
        ]);
        $this->assertTrue(Hash::check('482913', \App\Models\Customers::firstOrFail()->mobile_pin));
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
