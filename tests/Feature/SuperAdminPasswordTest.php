<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_changes_own_password(): void
    {
        $admin = User::factory()->create(['password' => Hash::make('Old@Pass123')]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']));

        $this->actingAs($admin)->get(route('administrator.subscriptions.index'))
            ->assertSee(route('administrator.password.edit'), false);
        $this->actingAs($admin)->get(route('administrator.password.edit'))
            ->assertOk()
            ->assertSee('Change password');

        $this->actingAs($admin)->put(route('administrator.password.update'), [
            'current_password' => 'wrong',
            'password' => 'New@Pass456',
            'password_confirmation' => 'New@Pass456',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->put(route('administrator.password.update'), [
            'current_password' => 'Old@Pass123',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertSessionHasErrors(['password' => 'Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.']);

        $this->actingAs($admin)->put(route('administrator.password.update'), [
            'current_password' => 'Old@Pass123',
            'password' => 'New@Pass456',
            'password_confirmation' => 'New@Pass456',
        ])->assertRedirect(route('administrator.password.edit'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('New@Pass456', $admin->fresh()->password));
    }

    public function test_shop_owner_cannot_use_the_super_admin_password_page(): void
    {
        $owner = User::factory()->create(['is_business_owner' => true]);

        $this->actingAs($owner)->get(route('administrator.password.edit'))->assertForbidden();
    }
}
