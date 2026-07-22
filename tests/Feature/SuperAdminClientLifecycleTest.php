<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminClientLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_client_is_pending_until_super_admin_approves_it(): void
    {
        [$admin] = $this->actors();

        $response = $this->actingAs($admin)->post(route('administrator.insert'), [
            'name' => 'Rahman Tailors',
            'email' => 'owner@rahman.test',
            'password' => 'secure-password',
            'role' => 'shop_owner',
            'modules' => ['tailoring', 'clothing'],
        ]);

        $owner = User::where('email', 'owner@rahman.test')->firstOrFail();
        $business = $owner->ownedBusiness;
        $response->assertRedirect(route('administrator.clients.show', $owner));
        $this->assertSame(Business::STATUS_PENDING, $business->status);
        $this->assertDatabaseHas('business_status_histories', [
            'business_id' => $business->id,
            'from_status' => null,
            'to_status' => Business::STATUS_PENDING,
            'changed_by_user_id' => $admin->id,
        ]);

        auth()->logout();
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secure-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)->patch(route('administrator.clients.status', $owner), [
            'status' => Business::STATUS_ACTIVE,
        ])->assertRedirect(route('administrator.clients.show', $owner));

        $business->refresh();
        $this->assertTrue($business->isActive());
        $this->assertSame($admin->id, $business->approved_by_user_id);
        $this->assertNotNull($business->approved_at);
    }

    public function test_suspension_blocks_owner_and_employee_without_deleting_data(): void
    {
        [$admin, $owner, $business] = $this->actors(true);
        $employee = User::factory()->create([
            'business_id' => $business->id,
            'employee_active' => true,
            'password' => Hash::make('secure-password'),
        ]);

        $this->actingAs($admin)->patch(route('administrator.clients.status', $owner), [
            'status' => Business::STATUS_SUSPENDED,
            'reason' => 'Subscription payment is overdue.',
        ])->assertRedirect(route('administrator.clients.show', $owner));

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'status' => Business::STATUS_SUSPENDED]);

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->actingAs($employee)->get(route('admin.home'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->actingAs($admin)->patch(route('administrator.clients.status', $owner), [
            'status' => Business::STATUS_ACTIVE,
        ])->assertRedirect(route('administrator.clients.show', $owner));
        $this->assertTrue($business->fresh()->isActive());
    }

    public function test_deactivation_requires_reason_and_permanent_delete_is_disabled(): void
    {
        [$admin, $owner, $business] = $this->actors(true);

        $this->actingAs($admin)->from(route('administrator.clients.show', $owner))
            ->patch(route('administrator.clients.status', $owner), ['status' => Business::STATUS_SUSPENDED])
            ->assertRedirect(route('administrator.clients.show', $owner))
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->delete(route('administrator.delete', $owner))
            ->assertRedirect(route('administrator.index'))
            ->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_super_admin_can_view_client_details_and_filter_by_status(): void
    {
        [$admin, $owner, $business] = $this->actors(true);
        DB::table('orders')->insert(['userId' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sales')->insert(['user_id' => $owner->id, 'customer_name' => 'Walk-in customer', 'created_at' => now(), 'updated_at' => now()]);

        $details = $this->actingAs($admin)->get(route('administrator.clients.show', $owner));
        $details
            ->assertOk()
            ->assertSeeText($owner->name)
            ->assertSeeText($business->shop_code)
            ->assertSeeText('Business data summary')
            ->assertSeeText('Active');
        $this->assertSame(1, $details->viewData('metrics')['orders']);
        $this->assertSame(1, $details->viewData('metrics')['sales']);

        $this->actingAs($admin)->get(route('administrator.index', ['status' => 'active']))
            ->assertOk()->assertSeeText($owner->name);
        $this->actingAs($admin)->get(route('administrator.index', ['status' => 'pending']))
            ->assertOk()->assertDontSeeText($owner->name);
    }

    private function actors(bool $withOwner = false): array
    {
        $adminRole = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        if (! $withOwner) {
            return [$admin];
        }

        $owner = User::factory()->create([
            'tailoring_access' => true,
            'clothing_access' => true,
            'is_business_owner' => true,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => $owner->name,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$admin, $owner, $business];
    }
}
