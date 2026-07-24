<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontTailoringService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontSeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_has_localized_canonical_social_and_website_metadata(): void
    {
        $response = $this->get(route('storefront.index', ['q' => 'cloth']));

        $response->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<link rel="canonical" href="'.route('storefront.index').'">', false)
            ->assertDontSee('canonical" href="'.route('storefront.index', ['q' => 'cloth']), false);

        $schema = $this->structuredData($response);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('WebSite', $schema['@graph'][0]['@type']);
        $this->assertSame(route('storefront.index'), $schema['@graph'][0]['url']);
        $this->assertSame('ur', $schema['@graph'][0]['inLanguage']);
        $this->assertSame(
            route('storefront.index').'?q={search_term_string}',
            $schema['@graph'][0]['potentialAction']['target']['urlTemplate']
        );
    }

    public function test_shop_product_and_tailoring_service_emit_only_public_structured_data(): void
    {
        [$owner, $storefront] = $this->storefront();
        $brand = ClothBrand::create(['name' => 'Siddiqui Fabrics', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Wash and Wear', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $owner->id,
            'price' => 1300,
            'sale_price' => 1450,
        ]);
        ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => 'Navy',
            'length' => 12,
            'average_unit_cost' => 900,
        ]);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'Premium Navy Wash and Wear',
            'description' => 'Comfortable fabric for daily wear.',
            'is_published' => true,
        ]);
        $service = StorefrontTailoringService::create([
            'storefront_id' => $storefront->id,
            'name' => 'Premium Shalwar Kameez Stitching',
            'description' => 'Cutting, stitching and final fitting.',
            'price_from' => 1800,
            'price_unit' => 'suit',
            'estimated_days' => 5,
            'is_published' => true,
        ]);

        $shopResponse = $this->get(route('storefront.show', $storefront));
        $shopSchema = $this->structuredData($shopResponse)['@graph'][0];
        $shopResponse->assertSee('<link rel="canonical" href="'.route('storefront.show', $storefront).'">', false);
        $this->assertSame('LocalBusiness', $shopSchema['@type']);
        $this->assertSame('03001112222', $shopSchema['telephone']);
        $this->assertSame('PK', $shopSchema['address']['addressCountry']);

        $productResponse = $this->get(route('storefront.clothing.show', [$storefront, $listing]));
        $productSchema = $this->structuredData($productResponse)['@graph'][0];
        $productResponse->assertSee('<meta property="og:type" content="product">', false);
        $this->assertSame('Product', $productSchema['@type']);
        $this->assertSame('PKR', $productSchema['offers']['priceCurrency']);
        $this->assertSame('1450.00', $productSchema['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $productSchema['offers']['availability']);
        $this->assertArrayNotHasKey('average_unit_cost', $productSchema);

        $serviceResponse = $this->get(route('storefront.tailoring.show', [$storefront, $service]));
        $serviceSchema = $this->structuredData($serviceResponse)['@graph'][0];
        $this->assertSame('Service', $serviceSchema['@type']);
        $this->assertSame('PKR', $serviceSchema['offers']['priceCurrency']);
        $this->assertSame('1800.00', $serviceSchema['offers']['price']);
        $this->assertStringNotContainsString('private-customer@example.test', $serviceResponse->getContent());

        $catalogResponse = $this->get(route('storefront.clothing.index', [
            'storefront' => $storefront,
            'min_price' => 1000,
        ]));
        $this->assertSame('CollectionPage', $this->structuredData($catalogResponse)['@graph'][0]['@type']);
        $catalogResponse->assertSee(
            '<link rel="canonical" href="'.route('storefront.clothing.index', $storefront).'">',
            false
        );
        $tailoringResponse = $this->get(route('storefront.tailoring.index', [
            'storefront' => $storefront,
            'service' => $service->id,
        ]));
        $this->assertSame('CollectionPage', $this->structuredData($tailoringResponse)['@graph'][0]['@type']);
    }

    public function test_cart_is_noindex_and_has_no_structured_customer_or_order_data(): void
    {
        [, $storefront] = $this->storefront();

        $response = $this->get(route('storefront.cart.show', $storefront));

        $response->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertSee('<link rel="canonical" href="'.route('storefront.cart.show', $storefront).'">', false)
            ->assertDontSee('application/ld+json', false)
            ->assertDontSee('private-customer@example.test', false);
    }

    public function test_client_preview_is_noindex_and_does_not_publish_business_schema(): void
    {
        [$owner] = $this->storefront();

        $this->actingAs($owner)
            ->get(route('admin.storefront.preview'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('application/ld+json', false);
    }

    private function storefront(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
            'email' => 'owner@example.test',
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Siddiqui Tailors and Fabrics',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => 'siddiqui-seo-shop',
            'display_name' => 'Siddiqui Tailors and Fabrics',
            'tagline' => 'Quality clothing and fine tailoring in Rawalpindi.',
            'description' => 'Public business description for local customers.',
            'public_phone' => '03001112222',
            'public_email' => 'shop@example.test',
            'address' => 'Main Bazaar',
            'city' => 'Rawalpindi',
            'show_clothing' => true,
            'show_tailoring' => true,
            'delivery_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return [$owner->fresh(), $storefront];
    }

    private function structuredData(TestResponse $response): array
    {
        preg_match(
            '/<script type="application\/ld\+json">(.*?)<\/script>/s',
            $response->getContent(),
            $matches
        );
        $this->assertArrayHasKey(1, $matches, 'Expected JSON-LD structured data.');

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
