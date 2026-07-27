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

    public function test_first_time_storefront_disables_module_links_until_basic_details_are_saved(): void
    {
        [$owner] = $this->business(true, true);

        $response = $this->actingAs($owner)->get(route('admin.storefront.edit'));

        $response->assertOk()
            ->assertSeeText('پہلے بنیادی معلومات محفوظ کریں')
            ->assertSeeText('دکان مکمل کرنے کے مراحل')
            ->assertSeeText('0 از 4 مراحل مکمل')
            ->assertDontSee(route('admin.storefront.module-settings.edit', 'tailoring'), false)
            ->assertDontSee(route('admin.storefront.module-settings.edit', 'clothing'), false);
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

    public function test_client_can_choose_catalogue_only_or_configure_online_order_methods(): void
    {
        [$owner, $business] = $this->business(false, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Commerce Controls Shop',
            'slug' => 'commerce-controls-shop',
            'show_clothing' => true,
        ]);

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'Commerce Controls Shop',
            'slug' => 'commerce-controls-shop',
            'default_locale' => 'ur',
            'show_clothing' => '1',
            'commerce_settings_present' => '1',
            'pickup_enabled' => '1',
        ])->assertRedirect(route('admin.storefront.edit'));
        $this->assertFalse($storefront->fresh()->online_ordering_enabled);

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'Commerce Controls Shop',
            'slug' => 'commerce-controls-shop',
            'default_locale' => 'ur',
            'show_clothing' => '1',
            'commerce_settings_present' => '1',
            'online_ordering_enabled' => '1',
            'unpaid_orders_enabled' => '1',
            'pickup_enabled' => '1',
        ])->assertRedirect(route('admin.storefront.edit'));

        $storefront->refresh();
        $this->assertTrue($storefront->online_ordering_enabled);
        $this->assertTrue($storefront->unpaid_orders_enabled);
        $this->assertFalse($storefront->cod_enabled);
        $this->assertFalse($storefront->easypaisa_enabled);
    }

    public function test_storefront_only_shows_receiving_fields_for_selected_payment_methods(): void
    {
        [$owner, $business] = $this->business(true, false);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Tailoring Payments UI',
            'slug' => 'tailoring-payments-ui',
            'show_tailoring' => true,
            'unpaid_orders_enabled' => true,
            'easypaisa_enabled' => false,
            'jazzcash_enabled' => false,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.storefront.edit'));
        $response->assertOk()
            ->assertSeeText('نہیں — ابھی ادائیگی قبول نہیں کریں گے')
            ->assertSeeText('ہاں — ادائیگی کے طریقے منتخب کریں')
            ->assertSeeText('ادائیگی کے تمام طریقے چھپا دیے گئے ہیں۔');
        $this->assertMatchesRegularExpression(
            '/id="payment-method-options"[^>]*hidden/',
            $response->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/data-payment-details-for="easypaisa" hidden/',
            $response->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/id="easypaisa_account_number"[^>]*disabled/',
            $response->getContent(),
        );

        $storefront->update([
            'unpaid_orders_enabled' => false,
            'easypaisa_enabled' => true,
            'easypaisa_account_title' => 'Tailoring Payments UI',
            'easypaisa_account_number' => '03001234567',
        ]);
        $response = $this->actingAs($owner)->get(route('admin.storefront.edit'));
        $response->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/id="payment-method-options"[^>]*hidden/',
            $response->getContent(),
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-payment-details-for="easypaisa" hidden/',
            $response->getContent(),
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="easypaisa_account_number"[^>]*disabled/',
            $response->getContent(),
        );
        $this->assertMatchesRegularExpression(
            '/data-payment-details-for="jazzcash" hidden/',
            $response->getContent(),
        );
    }

    public function test_unselected_payment_details_cannot_be_changed_by_the_request(): void
    {
        [$owner, $business] = $this->business(true, false);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Protected Payment Details',
            'slug' => 'protected-payment-details',
            'show_tailoring' => true,
            'unpaid_orders_enabled' => true,
            'easypaisa_enabled' => false,
            'easypaisa_account_title' => 'Saved Account',
            'easypaisa_account_number' => '03001111111',
        ]);

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'Protected Payment Details',
            'slug' => 'protected-payment-details',
            'default_locale' => 'ur',
            'show_tailoring' => '1',
            'commerce_settings_present' => '1',
            'payment_collection_mode' => 'none',
            'easypaisa_enabled' => '1',
            'easypaisa_account_title' => 'Injected Account',
            'easypaisa_account_number' => '03009999999',
        ])->assertRedirect(route('admin.storefront.edit'));

        $storefront->refresh();
        $this->assertTrue($storefront->unpaid_orders_enabled);
        $this->assertFalse($storefront->easypaisa_enabled);
        $this->assertSame('Saved Account', $storefront->easypaisa_account_title);
        $this->assertSame('03001111111', $storefront->easypaisa_account_number);
    }

    public function test_selected_easypaisa_requires_receiving_details(): void
    {
        [$owner, $business] = $this->business(true, false);
        Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Incomplete Easypaisa',
            'slug' => 'incomplete-easypaisa',
            'show_tailoring' => true,
        ]);

        $this->actingAs($owner)
            ->from(route('admin.storefront.edit'))
            ->put(route('admin.storefront.update'), [
                'display_name' => 'Incomplete Easypaisa',
                'slug' => 'incomplete-easypaisa',
                'default_locale' => 'ur',
                'show_tailoring' => '1',
                'commerce_settings_present' => '1',
                'payment_collection_mode' => 'methods',
                'easypaisa_enabled' => '1',
            ])
            ->assertRedirect(route('admin.storefront.edit'))
            ->assertSessionHasErrors('easypaisa_account_number');
    }

    public function test_client_can_configure_pakistan_manual_payment_receiving_details(): void
    {
        [$owner, $business] = $this->business(true, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Pakistan Payments Shop',
            'slug' => 'pakistan-payments-shop',
            'show_clothing' => true,
            'show_tailoring' => true,
        ]);

        $this->actingAs($owner)->put(route('admin.storefront.update'), [
            'display_name' => 'Pakistan Payments Shop',
            'slug' => 'pakistan-payments-shop',
            'default_locale' => 'ur',
            'show_clothing' => '1',
            'show_tailoring' => '1',
            'commerce_settings_present' => '1',
            'online_ordering_enabled' => '1',
            'pickup_enabled' => '1',
            'jazzcash_enabled' => '1',
            'jazzcash_account_title' => 'Pakistan Payments Shop',
            'jazzcash_account_number' => '03001234567',
            'bank_transfer_enabled' => '1',
            'bank_name' => 'Meezan Bank',
            'bank_account_title' => 'Pakistan Payments Shop',
            'bank_iban' => 'PK36MEZN0001234567890123',
            'raast_enabled' => '1',
            'raast_account_title' => 'Pakistan Payments Shop',
            'raast_id' => '03001234567',
        ])->assertRedirect(route('admin.storefront.edit'));

        $storefront->refresh();
        $this->assertTrue($storefront->jazzcash_enabled);
        $this->assertTrue($storefront->bank_transfer_enabled);
        $this->assertTrue($storefront->raast_enabled);
        $this->assertSame('03001234567', $storefront->jazzcash_account_number);
        $this->assertSame('PK36MEZN0001234567890123', $storefront->bank_iban);
        $this->assertSame('03001234567', $storefront->raast_id);
    }

    public function test_manual_payment_methods_require_safe_public_receiving_details(): void
    {
        [$owner, $business] = $this->business(true, true);
        Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Incomplete Payments Shop',
            'slug' => 'incomplete-payments-shop',
            'show_clothing' => true,
            'show_tailoring' => true,
        ]);

        $this->actingAs($owner)->from(route('admin.storefront.edit'))->put(route('admin.storefront.update'), [
            'display_name' => 'Incomplete Payments Shop',
            'slug' => 'incomplete-payments-shop',
            'default_locale' => 'ur',
            'show_clothing' => '1',
            'show_tailoring' => '1',
            'commerce_settings_present' => '1',
            'jazzcash_enabled' => '1',
        ])->assertRedirect(route('admin.storefront.edit'))
            ->assertSessionHasErrors('jazzcash_account_number');
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
