<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertRedirect('admin/Customers');

        $this->assertDatabaseHas('customers', [
            'name' => 'QA Urdu Customer',
            'user_id' => $owner->id,
            'Daaman' => '0',
        ]);
    }
}
