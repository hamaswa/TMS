<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminEnglishTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_templates_and_controller_messages_do_not_contain_urdu(): void
    {
        $paths = [
            ...glob(resource_path('views/Administrator/*.blade.php')),
            app_path('Http/Controllers/AdministratorController.php'),
            app_path('Http/Controllers/AdministratorSubscriptionController.php'),
            app_path('Http/Controllers/SubscriptionPlanController.php'),
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression(
                '/\p{Arabic}/u',
                $contents,
                'Super-admin source must be English-only: '.$path
            );
        }
    }

    public function test_every_main_super_admin_screen_renders_in_english_only(): void
    {
        $admin = User::factory()->create(['name' => 'QA Super Admin']);
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'administrative',
            'guard_name' => 'web',
        ]));
        Role::firstOrCreate([
            'name' => 'shop_owner',
            'guard_name' => 'web',
        ]);

        $routes = [
            'administrator.index',
            'administrator.create',
            'administrator.subscriptions.index',
            'administrator.subscription-plans.index',
            'administrator.marketplace.index',
            'administrator.roles',
            'administrator.roles-permi',
            'administrator.role.new',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));

            $response->assertOk();
            $this->assertDoesNotMatchRegularExpression(
                '/\p{Arabic}/u',
                $response->getContent(),
                'Super-admin response must be English-only: '.$routeName
            );
        }
    }

    public function test_super_admin_not_found_page_is_english(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'administrative',
            'guard_name' => 'web',
        ]));

        $response = $this->actingAs($admin)->get('/administrator/not-a-real-page');

        $response->assertNotFound()
            ->assertSeeText('Page not found')
            ->assertSee(route('administrator.index'), false);
        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', $response->getContent());
    }

    public function test_super_admin_forbidden_page_is_english(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'administrative',
            'guard_name' => 'web',
        ]));

        $response = $this->actingAs($admin)->get(route('admin.financial-reports.index'));

        $response->assertForbidden()
            ->assertSeeText('Access not permitted')
            ->assertSee(route('administrator.index'), false);
        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', $response->getContent());
    }

    public function test_authenticated_super_admin_is_redirected_from_login_to_super_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'administrative',
            'guard_name' => 'web',
        ]));
        Role::firstOrCreate([
            'name' => 'shop_owner',
            'guard_name' => 'web',
        ]);

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('administrator.index'));
    }

    public function test_client_filters_have_unambiguous_accessible_labels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'administrative',
            'guard_name' => 'web',
        ]));
        Role::firstOrCreate([
            'name' => 'shop_owner',
            'guard_name' => 'web',
        ]);

        $this->actingAs($admin)
            ->get(route('administrator.index'))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('for="client-search"', false)
            ->assertSee('id="client-search"', false)
            ->assertSee('for="client-status"', false)
            ->assertSee('id="client-status"', false)
            ->assertSee('for="client-module"', false)
            ->assertSee('id="client-module"', false);
    }

    public function test_business_member_not_found_page_remains_in_urdu(): void
    {
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
        ]);
        $owner->assignRole(Role::firstOrCreate([
            'name' => 'shop_owner',
            'guard_name' => 'web',
        ]));
        $business = Business::create([
            'name' => 'Urdu Client',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => false,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        $this->actingAs($owner)
            ->get('/admin/not-a-real-page')
            ->assertNotFound()
            ->assertSeeText('صفحہ نہیں ملا');
    }

    public function test_unknown_client_admin_url_is_urdu_even_before_session_middleware_runs(): void
    {
        $this->get('/admin/not-a-real-page')
            ->assertNotFound()
            ->assertSeeText('صفحہ نہیں ملا')
            ->assertSeeText('ڈیش بورڈ پر واپس جائیں');
    }
}
