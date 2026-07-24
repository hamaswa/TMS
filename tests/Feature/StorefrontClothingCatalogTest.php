<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontClothingCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_publish_inventory_cloth_and_public_stock_stays_live(): void
    {
        [$owner, $business, $storefront] = $this->business();
        [$cloth, $color] = $this->cloth($owner->id, 'نیلا', 18.5);

        $this->actingAs($owner)->put(route('admin.storefront.clothing.update', $cloth), [
            'public_name' => 'گرمیوں کا نیلا واش اینڈ ویئر',
            'description' => 'نرم، ہلکا اور روزمرہ استعمال کے لیے موزوں۔',
            'is_featured' => '1',
            'is_published' => '1',
            'sort_order' => '2',
        ])->assertRedirect(route('admin.storefront.clothing.index'));

        $listing = StorefrontClothingListing::where('storefront_id', $storefront->id)->firstOrFail();
        $this->get(route('storefront.clothing.index', $storefront))
            ->assertOk()
            ->assertSeeText('گرمیوں کا نیلا واش اینڈ ویئر')
            ->assertSeeText('18.50 میٹر دستیاب')
            ->assertSeeText('1,450.00 روپے فی میٹر');
        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('نرم، ہلکا اور روزمرہ استعمال کے لیے موزوں۔');

        $this->get(route('public.locale.update', [
            'locale' => 'en',
            'redirect' => route('storefront.clothing.index', $storefront, false),
        ]))->assertRedirect(route('storefront.clothing.index', $storefront, false));
        $this->get(route('storefront.clothing.index', $storefront))
            ->assertOk()
            ->assertSeeText('Rs 1,450.00 per metre');

        $color->update(['length' => 7.25]);
        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('7.25 metres');
    }

    public function test_client_cannot_publish_another_business_cloth(): void
    {
        [$owner] = $this->business();
        [$otherOwner] = $this->business('another-shop');
        [$otherCloth] = $this->cloth($otherOwner->id, 'سفید', 10);

        $this->actingAs($owner)->put(route('admin.storefront.clothing.update', $otherCloth), [
            'is_published' => '1',
        ])->assertNotFound();

        $this->assertDatabaseCount('storefront_clothing_listings', 0);
    }

    public function test_unpublished_and_cross_storefront_listings_are_not_public(): void
    {
        [, , $storefront] = $this->business();
        [, , $otherStorefront] = $this->business('second-shop');
        [$cloth] = $this->cloth($storefront->business->owner_user_id, 'سرمئی', 5);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'پرائیویٹ کپڑا',
            'is_published' => false,
        ]);

        $this->get(route('storefront.clothing.index', $storefront))->assertDontSeeText('پرائیویٹ کپڑا');
        $this->get(route('storefront.clothing.show', [$storefront, $listing]))->assertNotFound();

        $listing->update(['is_published' => true]);
        $this->get(route('storefront.clothing.show', [$otherStorefront, $listing]))->assertNotFound();
    }

    private function business(string $slug = 'catalog-shop'): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Catalog Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => $slug.'-'.$business->id,
            'display_name' => 'عوامی کپڑے کی دکان',
            'show_clothing' => true,
            'show_tailoring' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return [$owner->fresh(), $business, $storefront];
    }

    private function cloth(int $ownerId, string $colorName, float $length): array
    {
        $brand = ClothBrand::create(['name' => 'صدیقی فیبرکس', 'user_id' => $ownerId]);
        $type = ClothType::create(['name' => 'واش اینڈ ویئر', 'user_id' => $ownerId]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $ownerId,
            'price' => 1000,
            'sale_price' => 1450,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $ownerId,
            'color' => $colorName,
            'length' => $length,
            'average_unit_cost' => 1000,
        ]);

        return [$cloth, $color];
    }
}
