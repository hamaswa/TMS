<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessActivityLog;
use App\Models\BusinessRole;
use App\Models\Order;
use App\Models\Storefront;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessEmployeeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailoring_employee_is_routed_to_tailoring_and_cannot_open_shop_or_finance(): void
    {
        [$owner, $business] = $this->business(true, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Tailoring staff',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_WORKSHOP],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->get(route('admin.home'))->assertRedirect(route('admin.dashboard.tailoring'));
        $this->actingAs($employee)->get(route('admin.dashboard.tailoring'))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('.workspace-hero h1{color:#fff!important}', false);
        $this->actingAs($employee)->get(route('admin.tailor-jobs.index'))->assertOk();
        $this->actingAs($employee)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.financial-reports.index'))
            ->assertForbidden()
            ->assertSeeText('رسائی کی اجازت نہیں')
            ->assertSeeText('اپنے کاروباری منتظم سے رابطہ کریں')
            ->assertSee(route('admin.home'), false);
        $this->actingAs($employee)->get(route('admin.payment-reconciliation.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.team.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.team.employees.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.team.roles.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.setting.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.expense.index'))->assertForbidden();
    }

    public function test_finance_only_employee_is_routed_to_financial_reports(): void
    {
        [$owner, $business] = $this->business(true, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Accountant',
            'permissions' => [
                BusinessRole::FINANCE_VIEW,
                BusinessRole::CUSTOMER_BALANCES,
                BusinessRole::EXPENSES_MANAGE,
            ],
        ]);
        $employee = $this->employee($business, $role);
        Storefront::create([
            'business_id' => $business->id,
            'slug' => 'finance-reconciliation-'.$business->id,
            'display_name' => 'Finance Reconciliation',
            'show_clothing' => true,
        ]);
        Order::create([
            'suitQuantity' => 1,
            'totalPayment' => 1234,
            'userId' => $owner->id,
            'created_at' => now(),
        ]);

        $this->actingAs($employee)->get(route('admin.home'))
            ->assertRedirect(route('admin.financial-reports.index'));
        $this->actingAs($employee)->get(route('admin.financial-reports.index'))
            ->assertOk()
            ->assertSeeText('روپے 1,234.00');
        $this->actingAs($employee)->get(route('admin.payment-reconciliation.index'))->assertOk();
        $this->actingAs($employee)->get(route('admin.dashboard.tailoring'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.dashboard.clothing'))->assertForbidden();
    }

    public function test_clothing_salesperson_can_manage_online_orders_without_storefront_settings_access(): void
    {
        [, $business] = $this->business(false, true);
        Storefront::create([
            'business_id' => $business->id,
            'slug' => 'sales-order-queue-'.$business->id,
            'display_name' => 'Sales Order Queue',
            'show_clothing' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Salesperson',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->get(route('admin.storefront.orders.index'))
            ->assertOk()
            ->assertSeeText('آن لائن آرڈرز')
            ->assertDontSeeText('دکان کی ترتیب');
        $this->actingAs($employee)->get(route('admin.storefront.edit'))->assertForbidden();
    }

    public function test_client_team_management_is_split_into_focused_pages(): void
    {
        [$owner] = $this->business(true, true);

        $this->actingAs($owner)->get(route('admin.team.index'))->assertOk()->assertViewIs('team.index');
        $this->actingAs($owner)->get(route('admin.team.employees.index'))->assertOk()->assertViewIs('team.employees');
        $this->actingAs($owner)->get(route('admin.team.roles.index'))
            ->assertOk()
            ->assertViewIs('team.roles')
            ->assertSeeText('تیار رول منتخب کریں')
            ->assertSeeText('سیلز پرسن')
            ->assertSeeText('ٹیلرنگ')
            ->assertSeeText('کپڑے کی دکان')
            ->assertSee('data-permissions', false);
        $this->actingAs($owner)->get(route('admin.team.security'))->assertOk()->assertViewIs('team.security');
    }

    public function test_role_permissions_only_show_and_accept_enabled_business_modules(): void
    {
        [$owner] = $this->business(false, true);

        $this->actingAs($owner)->get(route('admin.team.roles.index'))
            ->assertOk()
            ->assertDontSee('value="tailoring.orders"', false)
            ->assertSee('value="clothing.sales"', false)
            ->assertSeeText(BusinessRole::ROLE_PRESETS['salesperson']['label'])
            ->assertDontSeeText(BusinessRole::ROLE_PRESETS['tailor']['label'])
            ->assertDontSeeText(BusinessRole::ROLE_PRESETS['order_manager']['label']);

        $this->actingAs($owner)->post(route('admin.team.roles.store'), [
            'name' => 'Invalid tailoring role',
            'permissions' => [BusinessRole::TAILORING_ORDERS],
        ])->assertSessionHasErrors('permissions.0');
    }

    public function test_tailoring_only_role_presets_hide_clothing_jobs(): void
    {
        [$owner] = $this->business(true, false);

        $this->actingAs($owner)->get(route('admin.team.roles.index'))
            ->assertOk()
            ->assertSeeText(BusinessRole::ROLE_PRESETS['tailor']['label'])
            ->assertSeeText(BusinessRole::ROLE_PRESETS['order_manager']['label'])
            ->assertDontSeeText(BusinessRole::ROLE_PRESETS['salesperson']['label'])
            ->assertDontSeeText(BusinessRole::ROLE_PRESETS['stock_keeper']['label']);
    }

    public function test_customer_only_tailoring_employee_cannot_open_orders_workshop_or_tailors(): void
    {
        [, $business] = $this->business(true, false);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Measurements desk',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_CUSTOMERS],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->get(route('admin.dashboard.tailoring'))
            ->assertOk()
            ->assertSeeText('گاہک اور پیمائش')
            ->assertDontSee(route('admin.tailor-jobs.index'), false)
            ->assertDontSee(route('admin.Tailor.index'), false);
        $this->actingAs($employee)->get(route('admin.Customers.index'))->assertOk();
        $this->actingAs($employee)->get(route('admin.tailor-jobs.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.order.total'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.Tailor.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.OptionType.index'))->assertForbidden();
    }

    public function test_shop_employee_sees_owner_tenant_records_not_another_business(): void
    {
        [$owner, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Supplier staff',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SUPPLIERS],
        ]);
        $employee = $this->employee($business, $role);
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Owner Supplier', 'opening_balance' => 100, 'active' => true]);
        [$otherOwner] = $this->business(false, true);
        Supplier::create(['user_id' => $otherOwner->id, 'name' => 'Other Supplier', 'active' => true]);

        $this->actingAs($employee)->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSeeText('Owner Supplier')
            ->assertDontSeeText('Other Supplier');
        $this->actingAs($employee)->post(route('admin.suppliers.payment', $supplier), [
            'amount' => 40,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertRedirect();
        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 40,
        ]);
        $this->actingAs($employee)->get(route('admin.suppliers.edit', $supplier))
            ->assertOk()
            ->assertSeeText('روپے 60.00');
        $this->actingAs($employee)->get(route('admin.inventory-ledger.index'))->assertForbidden();
    }

    public function test_sales_employee_only_sees_sales_tools_and_direct_urls_are_enforced(): void
    {
        [, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Counter sales',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->get(route('admin.dashboard.clothing'))
            ->assertOk()
            ->assertSeeText('نئی فروخت')
            ->assertDontSee(route('admin.stock.index'), false)
            ->assertDontSee(route('admin.purchases.index'), false)
            ->assertDontSee(route('admin.suppliers.index'), false);
        $this->actingAs($employee)->get(route('admin.sellCloth'))->assertOk();
        $this->actingAs($employee)->get(route('admin.stock.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.suppliers.index'))->assertForbidden();
    }

    public function test_owner_can_create_employee_and_other_business_cannot_edit_it(): void
    {
        [$owner, $business] = $this->business(true, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Manager',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::CLOTHING_ACCESS, BusinessRole::FINANCE_VIEW],
        ]);

        $this->actingAs($owner)->post(route('admin.team.employees.store'), [
            'name' => 'Test Employee',
            'username' => 'test.employee',
            'email' => 'employee@example.test',
            'phone' => '0300-1234567',
            'address' => 'Main Bazaar, Lahore',
            'password' => 'Employee@2026',
            'job_title' => 'Manager',
            'business_role_id' => $role->id,
        ])->assertRedirect();

        $employee = User::where('email', 'employee@example.test')->firstOrFail();
        $this->assertSame($business->id, $employee->business_id);
        $this->assertSame($role->id, $employee->business_role_id);
        $this->assertSame('test.employee', $employee->username);
        $this->assertSame('0300-1234567', $employee->phone);
        $this->assertSame('Main Bazaar, Lahore', $employee->address);
        $this->assertTrue($employee->must_change_password);
        $this->assertTrue(Hash::check('Employee@2026', $employee->password));
        $this->assertTrue($employee->hasRole('business_employee'));

        [$otherOwner] = $this->business(true, true);
        $this->actingAs($otherOwner)->get(route('admin.team.employees.edit', $employee))->assertNotFound();
    }

    public function test_employee_can_login_with_client_assigned_username(): void
    {
        [, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Counter sales',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $employee = $this->employee($business, $role);
        $employee->update(['username' => 'counter.ahmad', 'password' => 'Employee@2026']);

        $this->post(route('login'), [
            'email' => 'counter.ahmad',
            'password' => 'Employee@2026',
        ])->assertRedirect(route('admin.home'));

        $this->assertAuthenticatedAs($employee);
    }

    public function test_client_temporary_password_forces_employee_to_choose_a_new_password(): void
    {
        [$owner, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Counter sales',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SALES],
        ]);
        $employee = $this->employee($business, $role);
        $employee->update(['username' => 'forced.employee', 'password' => 'Original@2026']);

        $this->actingAs($owner)->patch(route('admin.team.employees.password', $employee), [
            'temporary_password' => 'Temporary@2026',
            'temporary_password_confirmation' => 'Temporary@2026',
        ])->assertRedirect();

        $employee->refresh();
        $this->assertTrue($employee->must_change_password);
        $this->assertTrue(Hash::check('Temporary@2026', $employee->password));
        $this->assertSame($owner->id, $employee->password_reset_by_user_id);
        $this->assertNotNull($employee->password_reset_at);

        $this->actingAs($employee)->get(route('admin.home'))->assertRedirect(route('employee.password.edit'));
        $this->actingAs($employee)->get(route('employee.password.edit'))->assertOk()->assertSeeText('اپنا نیا پاس ورڈ بنائیں');
        $this->actingAs($employee)->put(route('employee.password.update'), [
            'current_password' => 'Temporary@2026',
            'password' => 'EmployeeNew@2026',
            'password_confirmation' => 'EmployeeNew@2026',
        ])->assertRedirect(route('admin.home'));

        $employee->refresh();
        $this->assertFalse($employee->must_change_password);
        $this->assertTrue(Hash::check('EmployeeNew@2026', $employee->password));
        $this->assertNotNull($employee->password_changed_at);
        $this->assertDatabaseHas('business_activity_logs', [
            'business_id' => $business->id,
            'actor_user_id' => $owner->id,
            'route_name' => 'admin.team.employees.password',
        ]);
        $this->assertDatabaseHas('business_activity_logs', [
            'business_id' => $business->id,
            'actor_user_id' => $employee->id,
            'route_name' => 'employee.password.update',
        ]);
    }

    public function test_password_expiry_policy_gives_existing_staff_grace_then_enforces_change(): void
    {
        [$owner, $business] = $this->business(true, false);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Tailoring employee',
            'permissions' => [BusinessRole::TAILORING_ACCESS],
        ]);
        $employee = $this->employee($business, $role);
        $employee->update([
            'password' => 'Current@2026',
            'password_changed_at' => now()->subDays(120),
        ]);

        $this->actingAs($owner)->put(route('admin.team.password-policy.update'), [
            'password_expiry_days' => 90,
        ])->assertRedirect();

        $business->refresh();
        $employee->refresh();
        $this->assertSame(90, $business->password_expiry_days);
        $this->assertNotNull($business->password_policy_updated_at);
        $this->assertFalse($employee->employeePasswordExpired());

        $business->update(['password_policy_updated_at' => now()->subDays(100)]);
        $employee->unsetRelation('business');
        $this->assertTrue($employee->employeePasswordExpired());
        $this->actingAs($employee)->get(route('admin.home'))->assertRedirect(route('employee.password.edit'));
        $this->actingAs($employee)->get(route('employee.password.edit'))
            ->assertOk()
            ->assertSeeText('آپ کے پاس ورڈ کی مقررہ مدت مکمل ہو گئی ہے')
            ->assertSeeText('بڑا حرف، چھوٹا حرف، عدد اور علامت');

        $this->actingAs($employee)->put(route('employee.password.update'), [
            'current_password' => 'Current@2026',
            'password' => 'FreshPass@2026',
            'password_confirmation' => 'FreshPass@2026',
        ])->assertRedirect(route('admin.home'));

        $employee->refresh()->unsetRelation('business');
        $this->assertFalse($employee->employeePasswordExpired());
        $this->assertTrue(Hash::check('FreshPass@2026', $employee->password));
    }

    public function test_employee_passwords_must_meet_the_displayed_strength_rules(): void
    {
        [$owner, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Sales employee',
            'permissions' => [BusinessRole::CLOTHING_ACCESS],
        ]);

        $this->actingAs($owner)->post(route('admin.team.employees.store'), [
            'name' => 'Weak Password Employee',
            'username' => 'weak.password',
            'email' => 'weak-password@example.test',
            'password' => 'password',
            'business_role_id' => $role->id,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak-password@example.test']);
    }

    public function test_successful_employee_changes_are_audited_without_request_payloads(): void
    {
        [$owner, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Supplier clerk',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_SUPPLIERS],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->post(route('admin.suppliers.store'), [
            'name' => 'Audited Supplier',
            'phone' => '03001234567',
            'active' => 1,
        ])->assertRedirect();

        $log = BusinessActivityLog::firstOrFail();
        $this->assertSame($business->id, $log->business_id);
        $this->assertSame($employee->id, $log->actor_user_id);
        $this->assertSame('admin.suppliers.store', $log->route_name);
        $this->assertSame('POST', $log->method);
        $this->assertNull($log->route_parameters);
        $this->assertSame('نیا سپلائر شامل کیا', $log->actionDescription());

        $this->actingAs($employee)->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.activity.export'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSeeText('نیا سپلائر شامل کیا')
            ->assertSeeText('admin.suppliers.store')
            ->assertSeeText($employee->name);
    }

    public function test_activity_csv_export_is_filtered_tenant_scoped_and_formula_safe(): void
    {
        [$owner, $business] = $this->business(false, true);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Audited employee',
            'permissions' => [BusinessRole::CLOTHING_ACCESS],
        ]);
        $employee = $this->employee($business, $role);
        $employee->update(['name' => '=2+2', 'username' => '@danger']);

        BusinessActivityLog::create([
            'business_id' => $business->id,
            'actor_user_id' => $employee->id,
            'method' => 'POST',
            'route_name' => 'admin.suppliers.store',
            'path' => 'admin/suppliers',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        [, $otherBusiness] = $this->business(false, true);
        BusinessActivityLog::create([
            'business_id' => $otherBusiness->id,
            'actor_user_id' => $otherBusiness->owner_user_id,
            'method' => 'POST',
            'route_name' => 'admin.suppliers.store',
            'path' => 'admin/suppliers',
            'route_parameters' => ['supplier' => 'OTHER-TENANT-SECRET'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get(route('admin.activity.export', [
            'employee' => $employee->id,
            'action' => 'suppliers',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=2+2", $csv);
        $this->assertStringContainsString("'@danger", $csv);
        $this->assertStringContainsString('نیا سپلائر شامل کیا', $csv);
        $this->assertStringNotContainsString('OTHER-TENANT-SECRET', $csv);
    }

    private function business(bool $tailoring, bool $clothing): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
            'is_business_owner' => true,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => $owner->name,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => $tailoring,
            'clothing_enabled' => $clothing,
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$owner, $business];
    }

    private function employee(Business $business, BusinessRole $role): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $role->id,
            'is_business_owner' => false,
            'employee_active' => true,
            'tailoring_access' => false,
            'clothing_access' => false,
        ]);
    }
}
