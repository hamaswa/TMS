<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Storefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_marketplace_is_the_home_page_and_legacy_url_redirects(): void
    {
        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertViewIs('storefront.public.index')
            ->assertSee(route('login'), false);

        $this->get('/shops')
            ->assertStatus(301)
            ->assertRedirect(route('storefront.index'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('storefront.index'), false);

        [$owner] = $this->business(true, true);

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertViewIs('storefront.public.index');
    }

    public function test_client_can_save_preview_and_publish_a_public_storefront(): void
    {
        [$owner, $business] = $this->business(true, true);

        $this->actingAs($owner)->get(route('admin.storefront.edit'))
            ->assertOk()
            ->assertSeeText('آن لائن دکان');

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'صدیقی ٹیلرز اینڈ فیبرکس',
            'slug' => 'siddiqui-tailors',
            'default_locale' => 'ur',
            'tagline' => 'معیاری کپڑا اور نفیس سلائی',
            'description' => 'راولپنڈی میں کپڑے اور سلائی کی مکمل سہولت۔',
            'public_phone' => '03001112222',
            'public_email' => 'shop@example.test',
            'address' => 'مین بازار، راولپنڈی',
            'city' => 'راولپنڈی',
            'show_clothing' => '1',
            'show_tailoring' => '1',
            'inquiries_enabled' => '1',
            'pickup_enabled' => '1',
        ])->assertRedirect(route('admin.storefront.edit'));

        $storefront = Storefront::where('business_id', $business->id)->firstOrFail();
        $this->assertFalse($storefront->is_published);
        $this->actingAs($owner)->get(route('admin.storefront.preview'))->assertOk()->assertSeeText('یہ صرف پیش منظر ہے');
        $this->get(route('storefront.show', $storefront))->assertNotFound();

        $this->actingAs($owner)->patch(route('admin.storefront.publish'), ['published' => '1'])
            ->assertRedirect(route('admin.storefront.edit'));

        $storefront->refresh();
        $this->assertTrue($storefront->is_published);
        $this->assertNotNull($storefront->published_at);
        $this->get(route('storefront.show', $storefront))
            ->assertOk()
            ->assertSeeText('صدیقی ٹیلرز اینڈ فیبرکس')
            ->assertSeeText('کپڑے کی دکان')
            ->assertSeeText('ٹیلرنگ خدمات');
        $this->get(route('storefront.index'))->assertOk()->assertSeeText('صدیقی ٹیلرز اینڈ فیبرکس');
    }

    public function test_client_cannot_publish_a_module_the_business_does_not_have(): void
    {
        [$owner] = $this->business(true, false);

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'صرف ٹیلرنگ',
            'slug' => 'tailoring-only',
            'default_locale' => 'ur',
            'show_clothing' => '1',
        ])->assertSessionHasErrors('show_clothing');

        $this->assertDatabaseCount('storefronts', 0);
    }

    public function test_public_language_switch_persists_and_rejects_external_redirects(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="ur"', false)
            ->assertSee('dir="rtl"', false);

        $this->get(route('public.locale.update', [
            'locale' => 'en',
            'redirect' => '/',
        ]))->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSeeText('Public shops');

        $this->get(route('public.locale.update', [
            'locale' => 'en',
            'redirect' => 'https://example.com/phishing',
        ]))->assertRedirect('/');

        $this->get('/language/fr')->assertNotFound();
    }

    public function test_storefront_uses_client_default_locale_until_visitor_chooses(): void
    {
        [, $business] = $this->business(true, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'English Default Shop',
            'slug' => 'english-default-shop',
            'default_locale' => 'en',
            'show_clothing' => true,
            'show_tailoring' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('storefront.show', $storefront))
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSeeText('About us');

        $this->get(route('public.locale.update', [
            'locale' => 'ur',
            'redirect' => route('storefront.show', $storefront, false),
        ]))->assertRedirect(route('storefront.show', $storefront, false));

        $this->get(route('storefront.show', $storefront))
            ->assertOk()
            ->assertSee('lang="ur"', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_suspended_business_storefront_is_hidden_without_deleting_it(): void
    {
        [$owner, $business] = $this->business(false, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'فعال کپڑے کی دکان',
            'slug' => 'active-clothing-shop',
            'show_clothing' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('storefront.show', $storefront))->assertOk();
        $business->update(['status' => Business::STATUS_SUSPENDED]);

        $this->get(route('storefront.show', $storefront))->assertNotFound();
        $this->get(route('storefront.index'))->assertDontSeeText('فعال کپڑے کی دکان');
        $this->assertDatabaseHas('storefronts', ['id' => $storefront->id, 'is_published' => true]);
    }

    public function test_employee_needs_explicit_storefront_permission(): void
    {
        [$owner, $business] = $this->business(true, true);
        $employeeRole = Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'آن لائن دکان منیجر',
            'permissions' => [BusinessRole::STOREFRONT_MANAGE],
        ]);
        $employee = User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $role->id,
            'employee_active' => true,
            'must_change_password' => false,
            'is_business_owner' => false,
        ]);
        $employee->assignRole($employeeRole);

        $this->actingAs($employee)->get(route('admin.storefront.edit'))->assertOk();

        $role->update(['permissions' => []]);
        $employee->unsetRelation('businessRole');
        $this->actingAs($employee)->get(route('admin.storefront.edit'))->assertForbidden();
    }

    private function business(bool $tailoring, bool $clothing): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => 'Storefront Test Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => $tailoring,
            'clothing_enabled' => $clothing,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$owner->fresh(), $business];
    }
}
