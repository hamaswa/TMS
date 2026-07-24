<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontCartItem;
use App\Models\StorefrontClothingListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontDiscoveryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_combines_search_city_category_and_delivery_filters(): void
    {
        [, , $combined] = $this->storefront(
            'Khan Tailors and Fabrics',
            'Rawalpindi',
            true,
            true,
            true
        );
        $this->storefront('Peshawar Fine Tailors', 'Peshawar', true, false, false);
        $this->storefront('Lahore Cloth House', 'Lahore', false, true, true);

        $response = $this->get(route('storefront.index', [
            'q' => 'Khan',
            'city' => 'Rawalpindi',
            'category' => 'both',
            'delivery' => '1',
        ]));

        $response->assertOk()
            ->assertSeeText($combined->display_name)
            ->assertDontSeeText('Peshawar Fine Tailors')
            ->assertDontSeeText('Lahore Cloth House')
            ->assertSee('name="q"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="delivery"', false);

        $this->get(route('storefront.index', ['category' => 'clothing']))
            ->assertOk()
            ->assertSeeText('Khan Tailors and Fabrics')
            ->assertSeeText('Lahore Cloth House')
            ->assertDontSeeText('Peshawar Fine Tailors');
    }

    public function test_catalog_filters_type_color_price_and_live_reservable_stock(): void
    {
        [$owner, , $storefront] = $this->storefront(
            'Siddiqui Fabrics',
            'Rawalpindi',
            true,
            true,
            true
        );
        $wash = ClothType::create(['name' => 'Wash and Wear', 'user_id' => $owner->id]);
        $cotton = ClothType::create(['name' => 'Cotton', 'user_id' => $owner->id]);
        [$available] = $this->listing($owner, $storefront, $wash, 'Summer Wash', 'Blue', 10, 1500);
        [$empty] = $this->listing($owner, $storefront, $cotton, 'Premium Cotton', 'White', 0, 2500);
        [$reserved, $reservedColor] = $this->listing(
            $owner,
            $storefront,
            $wash,
            'Reserved Wash',
            'Navy',
            4,
            1700
        );
        $cart = StorefrontCart::create([
            'storefront_id' => $storefront->id,
            'token_hash' => hash('sha256', 'filter-reservation'),
            'expires_at' => now()->addHour(),
            'last_activity_at' => now(),
        ]);
        StorefrontCartItem::create([
            'storefront_cart_id' => $cart->id,
            'clothing_listing_id' => $reserved->id,
            'cloth_color_id' => $reservedColor->id,
            'quantity' => 4,
            'unit_price_snapshot' => 1700,
            'reserved_until' => now()->addHour(),
        ]);

        $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'type' => $wash->id,
            'color' => 'Blue',
            'min_price' => 1400,
            'max_price' => 1600,
            'availability' => 'in_stock',
        ]))->assertOk()
            ->assertSeeText($available->display_name)
            ->assertDontSeeText($empty->display_name)
            ->assertDontSeeText($reserved->display_name);

        $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'availability' => 'in_stock',
        ]))->assertSeeText($available->display_name)
            ->assertDontSeeText($empty->display_name)
            ->assertDontSeeText($reserved->display_name);

        $cart->update(['expires_at' => now()->subMinute()]);
        $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'availability' => 'in_stock',
        ]))->assertSeeText($available->display_name)
            ->assertSeeText($reserved->display_name)
            ->assertDontSeeText($empty->display_name);
    }

    public function test_public_discovery_rejects_invalid_or_cross_shop_filter_values(): void
    {
        [$owner, , $storefront] = $this->storefront(
            'Valid Filter Shop',
            'Karachi',
            false,
            true,
            true
        );
        [$otherOwner, , $otherStorefront] = $this->storefront(
            'Other Filter Shop',
            'Lahore',
            false,
            true,
            false
        );
        $ownType = ClothType::create(['name' => 'Khaddar', 'user_id' => $owner->id]);
        $otherType = ClothType::create(['name' => 'Linen', 'user_id' => $otherOwner->id]);
        $this->listing($owner, $storefront, $ownType, 'Khaddar Suiting', 'Brown', 5, 1900);
        $this->listing($otherOwner, $otherStorefront, $otherType, 'Linen Suiting', 'Cream', 5, 2100);

        $this->get(route('storefront.index', ['category' => 'invalid']))
            ->assertSessionHasErrors('category');
        $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'type' => $otherType->id,
        ]))->assertSessionHasErrors('type');
        $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'min_price' => 500,
            'max_price' => 100,
        ]))->assertSessionHasErrors('max_price');
    }

    private function storefront(
        string $name,
        string $city,
        bool $tailoring,
        bool $clothing,
        bool $delivery
    ): array {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => $name,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => $tailoring,
            'clothing_enabled' => $clothing,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => str($name)->slug().'-'.$business->id,
            'display_name' => $name,
            'city' => $city,
            'show_clothing' => $clothing,
            'show_tailoring' => $tailoring,
            'delivery_enabled' => $delivery,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return [$owner->fresh(), $business, $storefront];
    }

    private function listing(
        User $owner,
        Storefront $storefront,
        ClothType $type,
        string $name,
        string $color,
        float $length,
        float $price
    ): array {
        $brand = ClothBrand::firstOrCreate([
            'name' => 'Discovery Brand',
            'user_id' => $owner->id,
        ]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $owner->id,
            'price' => $price,
            'sale_price' => $price,
        ]);
        $clothColor = ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => $color,
            'length' => $length,
            'average_unit_cost' => $price * .7,
        ]);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => $name,
            'is_published' => true,
        ]);

        return [$listing, $clothColor];
    }
}
