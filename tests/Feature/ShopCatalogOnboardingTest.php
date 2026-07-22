<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopCatalogOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_brand_without_uploading_a_logo(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner)->post(route('admin.clothbrand.store'), [
            'name' => 'QA Brand',
        ])->assertRedirect(route('admin.clothbrand.index'));

        $this->assertDatabaseHas('cloth_brands', [
            'user_id' => $owner->id,
            'name' => 'QA Brand',
            'brand_logo' => null,
        ]);

        $this->actingAs($owner)->get(route('admin.clothbrand.index'))
            ->assertOk()
            ->assertSee('assets/images/logo.jpg');
    }
}
