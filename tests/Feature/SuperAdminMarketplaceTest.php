<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontOrder;
use App\Models\StorefrontTailoringService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_english_marketplace_metrics_and_client_activity(): void
    {
        $admin = $this->admin();
        [$owner, $storefront] = $this->marketplaceStorefront('siddiqui-market', true);

        $response = $this->actingAs($admin)->get(route('administrator.marketplace.index'));

        $response->assertOk()
            ->assertSeeText('Marketplace oversight')
            ->assertSeeText('Siddiqui Public Store')
            ->assertSeeText($owner->email)
            ->assertSeeText('1 clothes')
            ->assertSeeText('1 services')
            ->assertSeeText('1 inquiries')
            ->assertSeeText('1 orders')
            ->assertSeeText('Rs 1,450.00')
            ->assertSee(route('administrator.clients.show', $owner), false)
            ->assertSee(route('storefront.show', $storefront), false);
    }

    public function test_pause_hides_every_public_surface_without_deleting_client_data_and_resume_restores_it(): void
    {
        $admin = $this->admin();
        [$owner, $storefront] = $this->marketplaceStorefront('moderated-market', true);
        $counts = [
            'customers' => Customers::count(),
            'listings' => StorefrontClothingListing::count(),
            'services' => StorefrontTailoringService::count(),
            'inquiries' => StorefrontInquiry::count(),
            'orders' => StorefrontOrder::count(),
        ];

        $this->actingAs($admin)->patch(route('administrator.marketplace.moderation', $storefront), [
            'moderation_status' => Storefront::MODERATION_PAUSED,
        ])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->patch(route('administrator.marketplace.moderation', $storefront), [
            'moderation_status' => Storefront::MODERATION_PAUSED,
            'reason' => 'Identity information requires verification.',
        ])->assertRedirect(route('administrator.marketplace.index'));

        $storefront->refresh();
        $this->assertSame(Storefront::MODERATION_PAUSED, $storefront->moderation_status);
        $this->assertSame($admin->id, $storefront->moderated_by_user_id);
        $this->get(route('storefront.index'))->assertDontSeeText($storefront->display_name);
        $this->get(route('storefront.show', $storefront))->assertNotFound();
        $this->get(route('storefront.clothing.index', $storefront))->assertNotFound();
        $this->get(route('storefront.tailoring.index', $storefront))->assertNotFound();
        $this->get(route('storefront.cart.show', $storefront))->assertNotFound();
        $this->get(route('storefront.orders.show', [$storefront, 'TMSO-MODERATION']))->assertNotFound();
        $this->actingAs($owner)->get(route('admin.storefront.edit'))
            ->assertOk()
            ->assertSeeText('عوامی دکان عارضی طور پر روکی گئی ہے۔')
            ->assertSeeText('Identity information requires verification.');

        $this->assertSame($counts['customers'], Customers::count());
        $this->assertSame($counts['listings'], StorefrontClothingListing::count());
        $this->assertSame($counts['services'], StorefrontTailoringService::count());
        $this->assertSame($counts['inquiries'], StorefrontInquiry::count());
        $this->assertSame($counts['orders'], StorefrontOrder::count());

        $this->actingAs($admin)->patch(route('administrator.marketplace.moderation', $storefront), [
            'moderation_status' => Storefront::MODERATION_ACTIVE,
        ])->assertRedirect(route('administrator.marketplace.index'));
        $this->get(route('storefront.show', $storefront->fresh()))->assertOk();
        $this->assertNull($storefront->fresh()->moderation_reason);
        $this->assertDatabaseCount('storefront_moderation_histories', 2);
        $this->assertDatabaseHas('storefront_moderation_histories', [
            'storefront_id' => $storefront->id,
            'from_status' => Storefront::MODERATION_ACTIVE,
            'to_status' => Storefront::MODERATION_PAUSED,
            'reason' => 'Identity information requires verification.',
            'changed_by_user_id' => $admin->id,
        ]);
        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertSeeText('Moderation history')
            ->assertSeeText('Identity information requires verification.');
    }

    public function test_marketplace_filters_and_moderation_are_super_admin_only(): void
    {
        $admin = $this->admin();
        [$activeOwner, $active] = $this->marketplaceStorefront('active-market', true);
        [$draftOwner, $draft] = $this->marketplaceStorefront('draft-market', false);
        $draft->forceFill([
            'moderation_status' => Storefront::MODERATION_PAUSED,
            'moderation_reason' => 'Review pending.',
        ])->save();

        $this->actingAs($admin)->get(route('administrator.marketplace.index', [
            'publication' => 'published',
            'moderation' => 'active',
        ]))->assertSeeText($active->display_name)->assertDontSeeText($draft->display_name);
        $this->actingAs($admin)->get(route('administrator.marketplace.index', [
            'search' => $draftOwner->email,
        ]))->assertSeeText($draft->display_name)->assertDontSeeText($active->display_name);

        $this->actingAs($activeOwner)->get(route('administrator.marketplace.index'))->assertForbidden();
        $this->actingAs($activeOwner)->patch(route('administrator.marketplace.moderation', $active), [
            'moderation_status' => Storefront::MODERATION_PAUSED,
            'reason' => 'Unauthorized attempt',
        ])->assertForbidden();
        $this->assertSame(Storefront::MODERATION_ACTIVE, $active->fresh()->moderation_status);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        $admin = User::factory()->create(['name' => 'Marketplace Administrator']);
        $admin->assignRole($role);

        return $admin;
    }

    private function marketplaceStorefront(string $slug, bool $published): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Owner',
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => $slug,
            'display_name' => $slug === 'siddiqui-market' ? 'Siddiqui Public Store' : ucfirst(str_replace('-', ' ', $slug)),
            'show_clothing' => true,
            'show_tailoring' => true,
            'inquiries_enabled' => true,
            'pickup_enabled' => true,
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ]);
        $brand = ClothBrand::create(['name' => 'Market Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Summer Cloth', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $owner->id,
            'price' => 1000,
            'sale_price' => 1450,
        ]);
        StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'Public Cloth',
            'is_published' => true,
        ]);
        $service = StorefrontTailoringService::create([
            'storefront_id' => $storefront->id,
            'name' => 'Premium Stitching',
            'is_published' => true,
        ]);
        StorefrontInquiry::create([
            'storefront_id' => $storefront->id,
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Inquiry Customer',
            'phone' => '03001112222',
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Order Customer',
            'phone_number1' => '03003334444',
        ]);
        StorefrontOrder::create([
            'storefront_id' => $storefront->id,
            'customer_id' => $customer->id,
            'reference' => 'TMSO-'.strtoupper(str_replace('-', '', $slug)),
            'tracking_token_hash' => hash('sha256', $slug),
            'status' => StorefrontOrder::STATUS_PENDING,
            'fulfillment_method' => 'pickup',
            'subtotal' => 1450,
            'paid_amount' => 0,
            'balance_amount' => 1450,
            'placed_at' => now(),
        ]);

        return [$owner->fresh(), $storefront, $business];
    }
}
