<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontCollection;
use App\Models\StorefrontHeroSlide;
use App\Models\StorefrontMenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontMerchandisingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_builds_collection_and_bilingual_menu_from_live_inventory(): void
    {
        [$owner, $storefront, $listing] = $this->shop('merch-shop');

        $this->actingAs($owner)->post(route('admin.storefront.collections.store'), [
            'name_ur' => 'گرمیوں کے کپڑے', 'name_en' => 'Summer fabrics', 'slug' => 'summer',
            'source_type' => 'tag', 'collection_tags' => 'summer, premium', 'sort_mode' => 'newest',
            'online_order_enabled' => '1', 'is_published' => '1',
        ])->assertRedirect();

        $collection = StorefrontCollection::firstOrFail();
        $this->assertSame(['summer', 'premium'], $collection->rules['tags']);

        $this->actingAs($owner)->post(route('admin.storefront.menu-items.store'), [
            'label_ur' => 'گرمی', 'label_en' => 'Summer', 'item_type' => 'collection',
            'storefront_collection_id' => $collection->id, 'location' => 'both', 'is_visible' => '1',
        ])->assertRedirect();

        $this->get(route('storefront.show', $storefront))->assertOk()->assertSeeText('گرمی');
        $this->get(route('public.locale.update', ['locale' => 'en', 'redirect' => route('storefront.show', $storefront, false)]))->assertRedirect();
        $this->get(route('storefront.show', $storefront))->assertOk()->assertSeeText('Summer');
        $this->get(route('storefront.collections.show', [$storefront, $collection]))
            ->assertOk()->assertSeeText('Summer fabrics')->assertSeeText($listing->public_name_en);
    }

    public function test_owner_builds_scheduled_bilingual_hero_slides_with_collection_buttons(): void
    {
        [$owner, $storefront] = $this->shop('hero-shop');
        $collection = $storefront->collections()->create([
            'slug' => 'premium', 'name_ur' => 'پریمیم', 'name_en' => 'Premium',
            'source_type' => 'all', 'is_published' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.storefront.hero-slides.store'), [
            'title_ur' => 'نئی پریمیم کلیکشن', 'title_en' => 'New premium collection',
            'text_ur' => 'اپنی پسند کا کپڑا منتخب کریں۔', 'text_en' => 'Choose the fabric you love.',
            'media_type' => 'color', 'alignment' => 'center', 'overlay_strength' => 60,
            'primary_label_ur' => 'ابھی خریدیں', 'primary_label_en' => 'Shop now',
            'primary_link_type' => 'collection', 'primary_collection_id' => $collection->id,
            'secondary_link_type' => 'none', 'is_active' => '1', 'sort_order' => 2,
        ])->assertRedirect();

        $slide = StorefrontHeroSlide::firstOrFail();
        $this->assertSame($storefront->id, $slide->storefront_id);
        $this->assertSame('center', $slide->alignment);
        $this->assertSame($collection->id, $slide->primary_collection_id);

        $this->withSession(['public_locale' => 'en'])->get(route('storefront.show', $storefront))
            ->assertOk()
            ->assertSeeText('New premium collection')
            ->assertSeeText('Choose the fabric you love.')
            ->assertSeeText('Shop now')
            ->assertSee(route('storefront.collections.show', [$storefront, $collection]), false)
            ->assertSee('data-hero-slide', false);
    }

    public function test_hero_slides_are_tenant_scoped_and_inactive_slides_stay_private(): void
    {
        [$owner, $storefront] = $this->shop('owner-hero-shop');
        [, $otherStorefront] = $this->shop('other-hero-shop');
        $otherSlide = $otherStorefront->heroSlides()->create([
            'title_en' => 'Other tenant secret', 'media_type' => 'color',
            'alignment' => 'start', 'overlay_strength' => 65, 'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('admin.storefront.hero-slides.update', $otherSlide), [
            'title_en' => 'Taken over', 'media_type' => 'color', 'alignment' => 'start',
            'overlay_strength' => 65, 'primary_link_type' => 'none',
            'secondary_link_type' => 'none', 'is_active' => '1',
        ])->assertNotFound();

        $storefront->heroSlides()->create([
            'title_en' => 'Inactive campaign', 'media_type' => 'color',
            'alignment' => 'start', 'overlay_strength' => 65, 'is_active' => false,
        ]);
        $this->withSession(['public_locale' => 'en'])->get(route('storefront.show', $storefront))
            ->assertOk()->assertDontSeeText('Inactive campaign');
    }

    public function test_collections_are_tenant_scoped_and_unpublished_collections_are_private(): void
    {
        [, $storefront] = $this->shop('first-shop');
        [, $otherStorefront] = $this->shop('other-shop');
        $collection = $storefront->collections()->create([
            'slug' => 'private', 'name_en' => 'Private', 'source_type' => 'all', 'is_published' => false,
        ]);

        $this->get(route('storefront.collections.show', [$storefront, $collection]))->assertNotFound();
        $collection->update(['is_published' => true]);
        $this->get('/shops/'.$otherStorefront->slug.'/collections/'.$collection->slug)->assertNotFound();
    }

    public function test_submenu_and_sale_collection_resolve_without_cross_shop_products(): void
    {
        [, $storefront, $listing] = $this->shop('sale-shop');
        [, $otherStorefront, $otherListing] = $this->shop('foreign-shop');
        $listing->cloth->update(['price' => 1800, 'sale_price' => 1400]);
        $otherListing->cloth->update(['price' => 2000, 'sale_price' => 900]);
        $collection = $storefront->collections()->create([
            'slug' => 'sale', 'name_en' => 'Sale', 'source_type' => 'sale', 'sort_mode' => 'price_asc', 'is_published' => true,
        ]);
        $parent = $storefront->menuItems()->create(['label_en' => 'Shop', 'item_type' => 'catalog', 'location' => 'header', 'is_visible' => true]);
        $storefront->menuItems()->create(['parent_id' => $parent->id, 'storefront_collection_id' => $collection->id, 'label_en' => 'Sale', 'item_type' => 'collection', 'location' => 'header', 'is_visible' => true]);

        $this->get(route('storefront.show', $storefront))->assertOk()->assertSeeText('Shop')->assertSeeText('Sale');
        $response = $this->get(route('storefront.collections.show', [$storefront, $collection]))->assertOk();
        $response->assertSeeText($listing->public_name_en)->assertDontSeeText($otherListing->public_name_en);
    }

    public function test_menu_hides_destinations_for_disabled_storefront_modules(): void
    {
        [, $storefront] = $this->shop('tailor-only');
        $storefront->update(['show_clothing' => false, 'show_tailoring' => true]);
        $storefront->menuItems()->create(['label_en' => 'Broken shop link', 'item_type' => 'catalog', 'location' => 'header', 'is_visible' => true]);
        $storefront->menuItems()->create(['label_en' => 'Tailoring', 'item_type' => 'tailoring', 'location' => 'header', 'is_visible' => true]);

        $this->get(route('storefront.show', $storefront))
            ->assertOk()
            ->assertDontSeeText('Broken shop link')
            ->assertSeeText('Tailoring');
    }

    private function shop(string $slug): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true]);
        $owner->assignRole($role);
        $business = Business::create(['name' => $slug, 'owner_user_id' => $owner->id, 'tailoring_enabled' => true, 'clothing_enabled' => true, 'status' => Business::STATUS_ACTIVE, 'approved_at' => now()]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create(['business_id' => $business->id, 'slug' => $slug, 'display_name' => $slug, 'display_name_en' => $slug, 'show_clothing' => true, 'show_tailoring' => true, 'is_published' => true, 'published_at' => now()]);
        $brand = ClothBrand::create(['name' => 'Brand '.$slug, 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Cotton', 'name_en' => 'Cotton', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_brand_id' => $brand->id, 'cloth_type_id' => $type->id, 'user_id' => $owner->id, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'price' => 1500]);
        ClothColor::create(['cloth_id' => $cloth->id, 'user_id' => $owner->id, 'color' => 'Blue', 'length' => 20, 'average_unit_cost' => 900]);
        $listing = StorefrontClothingListing::create(['storefront_id' => $storefront->id, 'cloth_id' => $cloth->id, 'public_name_en' => 'Fabric '.$slug, 'public_name' => 'Fabric '.$slug, 'tags' => ['summer', 'premium'], 'is_published' => true, 'is_available' => true, 'online_order_enabled' => true]);

        return [$owner->fresh(), $storefront, $listing];
    }
}
