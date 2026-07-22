<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailoring_only_client_can_use_tailoring_but_not_clothing(): void
    {
        $client = $this->client(true, false);

        $this->actingAs($client)->get(route('admin.home'))->assertRedirect(route('admin.dashboard.tailoring'));
        $this->actingAs($client)->get(route('admin.dashboard.tailoring'))->assertOk()->assertSeeText('ٹیلرنگ ڈیش بورڈ')->assertDontSeeText('دکان ڈیش بورڈ', false);
        $this->actingAs($client)->get(route('admin.tailor-jobs.index'))->assertOk();
        $this->actingAs($client)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($client)->get(route('admin.financial-reports.index'))->assertOk()->assertDontSeeText('کاؤنٹر کپڑا فروخت');
    }

    public function test_clothing_only_client_can_use_sales_and_purchases_but_not_tailoring(): void
    {
        $client = $this->client(false, true);

        $this->actingAs($client)->get(route('admin.home'))->assertRedirect(route('admin.dashboard.clothing'));
        $this->actingAs($client)->get(route('admin.dashboard.clothing'))->assertOk()->assertSeeText('دکان ڈیش بورڈ')->assertDontSeeText('ٹیلرنگ ڈیش بورڈ');
        $this->actingAs($client)->get(route('admin.purchases.index'))->assertOk();
        $this->actingAs($client)->get(route('admin.tailor-jobs.index'))->assertForbidden();
        $this->actingAs($client)->get(route('admin.financial-reports.index'))->assertOk()->assertDontSeeText('ٹیلرنگ آرڈرز');
    }

    public function test_combined_client_can_use_both_modules(): void
    {
        $client = $this->client(true, true);

        $this->actingAs($client)->get(route('admin.home'))->assertOk()->assertSee('ٹیلرنگ ورک اسپیس')->assertSee('دکان اور فروخت ورک اسپیس');
        $this->actingAs($client)->get(route('admin.workspace.switch', 'tailoring'))
            ->assertRedirect(route('admin.dashboard.tailoring'))->assertSessionHas('active_workspace', 'tailoring');
        $this->actingAs($client)->get(route('admin.dashboard.clothing'))->assertOk()->assertSessionHas('active_workspace', 'clothing');
        $this->actingAs($client)->get(route('admin.tailor-jobs.index'))->assertOk();
        $this->actingAs($client)->get(route('admin.inventory-ledger.index'))->assertOk();
    }

    public function test_super_admin_can_create_and_change_client_module_access(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $admin = User::factory()->create(['tailoring_access' => false, 'clothing_access' => false]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)->post(route('administrator.insert'), [
            'name' => 'Clothing Client', 'email' => 'clothing@example.com',
            'password' => 'secure-password', 'role' => 'shop_owner', 'modules' => ['clothing'],
        ])->assertRedirect(route('administrator.index'));

        $client = User::where('email', 'clothing@example.com')->firstOrFail();
        $this->assertFalse($client->tailoring_access);
        $this->assertTrue($client->clothing_access);
        $this->assertTrue($client->hasRole('shop_owner'));

        $this->actingAs($admin)->post(route('administrator.update', $client), [
            'name' => $client->name, 'email' => $client->email,
            'role' => 'shop_owner', 'modules' => ['tailoring', 'clothing'],
        ])->assertRedirect(route('administrator.index'));

        $client->refresh();
        $this->assertTrue($client->tailoring_access);
        $this->assertTrue($client->clothing_access);
    }

    public function test_shop_owner_client_must_have_at_least_one_module(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $this->actingAs($admin)->from(route('administrator.create'))->post(route('administrator.insert'), [
            'name' => 'No Modules', 'email' => 'none@example.com',
            'password' => 'secure-password', 'role' => 'shop_owner',
        ])->assertRedirect(route('administrator.create'))->assertSessionHasErrors('modules');

        $this->assertDatabaseMissing('users', ['email' => 'none@example.com']);
    }

    private function client(bool $tailoring, bool $clothing): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
