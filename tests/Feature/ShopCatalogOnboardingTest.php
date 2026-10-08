<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontClothingListing;
use App\Models\User;
use App\Services\StorefrontCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopCatalogOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_brand_without_uploading_a_logo(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner)->post(route('admin.clothbrand.store'), [
            'name' => 'QA Brand',
        ])->assertRedirect(route('admin.clothbrand.index'));

        $this->assertDatabaseHas('cloth_brands', [
            'user_id' => $owner->id,
            'name' => 'QA Brand',
            'brand_logo' => null,
        ]);

        $this->actingAs($owner)->get(route('admin.clothbrand.index'))
            ->assertOk()
            ->assertSee('assets/images/logo.jpg')
            ->assertSee('id="brandDirectorySearch"', false)
            ->assertSee('class="dropdown brand-actions"', false)
            ->assertDontSeeText('برانڈ QR پرنٹ کریں')
            ->assertSeeText('برانڈ میں ترمیم کریں');

        $brand = ClothBrand::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame(sprintf('BRD-%d-%06d', $owner->id, $brand->id), $brand->bundle_code);
        $this->actingAs($owner)->get(route('admin.clothbrand.show', $brand))
            ->assertRedirect(route('admin.clothbrand.edit', $brand));
        $this->actingAs($owner)->get(route('admin.clothbrand.qr-label', $brand))
            ->assertOk()
            ->assertSeeText($brand->bundle_code)
            ->assertSee('<svg', false);
    }

    public function test_client_can_create_cloth_without_media_using_urdu_commas(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => false,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Catalog Onboarding',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => false,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $brand = ClothBrand::create(['name' => 'صدیقی فیبرکس', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'واش اینڈ ویئر', 'user_id' => $owner->id]);

        $this->actingAs($owner->fresh())->get(route('admin.clothtype.index'))
            ->assertOk()
            ->assertSee('id="clothTypeDirectorySearch"', false)
            ->assertSee('class="dropdown cloth-type-actions"', false)
            ->assertSeeText('قسم میں ترمیم کریں')
            ->assertSeeText('قسم حذف کریں');

        $this->actingAs($owner->fresh())->get(route('admin.cloth.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSeeText('سیٹ کا نام')
            ->assertSee(route('admin.clothtype.index'), false)
            ->assertSee(route('admin.clothbrand.index'), false)
            ->assertSeeText('کپڑے کی قسم بنائیں')
            ->assertSeeText('برانڈ بنائیں')
            ->assertSeeText('رنگ شامل کریں')
            ->assertSee('id="colorRows"', false)
            ->assertSeeText('رنگوں کا مشترکہ اسٹاک')
            ->assertSeeText('ہر رنگ کا الگ اسٹاک')
            ->assertSee('name="online_availability"', false)
            ->assertSee('<h1', false);

        $this->actingAs($owner->fresh())->post(route('admin.cloth.store'), [
            'name' => 'Dilbar',
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
            'colors' => 'نیلا، سرمئی',
            'length' => [12.5, 8],
            'length_colors' => ['نیلا', 'سرمئی'],
            'price' => 1000,
            'sale_price' => 1450,
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('Dilbar', $cloth->name);
        $this->assertSame(['نیلا', 'سرمئی'], $cloth->colors()->orderBy('id')->pluck('color')->all());
        $this->assertDatabaseCount('cloth_images', 0);
        $this->assertDatabaseCount('storefront_clothing_listings', 0);
    }

    public function test_owner_chooses_pos_only_or_online_order_when_adding_inventory(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => false,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Online Inventory Choice',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => false,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => 'inventory-choice-shop',
            'display_name' => 'Inventory Choice Shop',
            'show_clothing' => true,
            'online_ordering_enabled' => true,
            'pickup_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $brand = ClothBrand::create(['name' => 'Choice Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Choice Type', 'user_id' => $owner->id]);

        $this->actingAs($owner->fresh())->get(route('admin.cloth.create'))
            ->assertOk()
            ->assertSeeText('صرف POS / دکان کی فروخت')
            ->assertSeeText('POS اور آن لائن آرڈر')
            ->assertSee('name="online_availability"', false);

        $base = [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_NONE,
            'length' => [10],
            'length_colors' => ['عام'],
            'price' => 900,
            'sale_price' => 1300,
        ];
        $this->actingAs($owner->fresh())->post(route('admin.cloth.store'), [
            ...$base,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));
        $this->assertDatabaseCount('storefront_clothing_listings', 0);

        $this->actingAs($owner->fresh())->post(route('admin.cloth.store'), [
            ...$base,
            'sale_price' => 1400,
            'online_availability' => 'online_order',
        ])->assertRedirect(route('admin.cloth.index'));
        $onlineCloth = Cloth::where('user_id', $owner->id)->latest('id')->firstOrFail();
        $listing = StorefrontClothingListing::where('cloth_id', $onlineCloth->id)->firstOrFail();
        $this->assertTrue($listing->is_published);
        $this->assertTrue($listing->is_available);
        $this->assertTrue($listing->online_order_enabled);
        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('Choice Brand')
            ->assertSeeText('10.00 میٹر دستیاب')
            ->assertDontSeeText('آرڈر کے لیے رنگ منتخب کریں')
            ->assertDontSeeText('عام');

        $aggregateStock = $onlineCloth->colors()->sole();
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $aggregateStock->id,
            'quantity' => 2,
        ])->assertRedirect(route('storefront.cart.show', $storefront));
        $this->assertSame(8.0, $aggregateStock->fresh()->reservableLength());
        $this->get(route('storefront.cart.show', $storefront))
            ->assertOk()
            ->assertDontSeeText('عام');

        $inventoryRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Inventory employee',
            'permissions' => [BusinessRole::CLOTHING_ACCESS, BusinessRole::CLOTHING_INVENTORY],
        ]);
        $employee = User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $inventoryRole->id,
            'employee_active' => true,
            'tailoring_access' => false,
            'clothing_access' => true,
        ]);
        $this->actingAs($employee)->put(route('admin.cloth.update', $onlineCloth), [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'colors' => 'عام',
            'length' => 10,
            'price' => 900,
            'sale_price' => 1400,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));
        $listing->refresh();
        $this->assertTrue($listing->is_published);
        $this->assertTrue($listing->is_available);
        $this->assertTrue($listing->online_order_enabled);

        $this->actingAs($owner->fresh())->put(route('admin.cloth.update', $onlineCloth), [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'colors' => 'عام',
            'length' => 10,
            'price' => 900,
            'sale_price' => 1400,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));
        $listing->refresh();
        $this->assertFalse($listing->is_published);
        $this->assertFalse($listing->is_available);
        $this->assertFalse($listing->online_order_enabled);
        $this->get(route('storefront.clothing.show', [$storefront, $listing]))->assertNotFound();
    }

    public function test_display_only_colors_are_selectable_online_while_sharing_one_stock_balance(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => false,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Shared Colour Stock',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => false,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => 'shared-colour-stock',
            'display_name' => 'Shared Colour Stock',
            'show_clothing' => true,
            'online_ordering_enabled' => true,
            'pickup_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $brand = ClothBrand::create(['name' => 'Shared Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Shared Type', 'user_id' => $owner->id]);

        $this->actingAs($owner->fresh())->post(route('admin.cloth.store'), [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_DISPLAY_ONLY,
            'colors' => 'Navy, Maroon',
            'length' => [10],
            'length_colors' => ['عام'],
            'price' => 900,
            'sale_price' => 1400,
            'online_availability' => 'online_order',
        ])->assertRedirect(route('admin.cloth.index'));

        $this->app['auth']->forgetGuards();
        $cloth = Cloth::where('user_id', $owner->id)->sole();
        $listing = StorefrontClothingListing::where('cloth_id', $cloth->id)->sole();
        $aggregateStock = $cloth->colors()->sole();
        $this->assertSame(Cloth::COLOR_TRACKING_DISPLAY_ONLY, $cloth->color_tracking_mode);
        $this->assertSame(['Navy', 'Maroon'], $cloth->display_colors);
        $this->assertSame('عام', $aggregateStock->color);

        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('Navy')
            ->assertSeeText('Maroon')
            ->assertSeeText('10.00 میٹر دستیاب');

        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $aggregateStock->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('selected_color');

        foreach ([['Navy', 3], ['Maroon', 4]] as [$selectedColor, $quantity]) {
            $this->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $aggregateStock->id,
                'selected_color' => $selectedColor,
                'quantity' => $quantity,
            ])->assertSessionHasNoErrors();
        }

        $this->assertDatabaseHas('storefront_cart_items', [
            'cloth_color_id' => $aggregateStock->id,
            'selected_color' => 'Navy',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('storefront_cart_items', [
            'cloth_color_id' => $aggregateStock->id,
            'selected_color' => 'Maroon',
            'quantity' => 4,
        ]);
        $this->assertSame(3.0, $aggregateStock->fresh()->reservableLength());

        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $aggregateStock->id,
            'selected_color' => 'Navy',
            'quantity' => 7,
        ])->assertSessionHasErrors('quantity');
        $this->assertSame(3.0, $aggregateStock->fresh()->reservableLength());

        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Shared Colour Customer',
            'phone_number1' => '03001112233',
        ]);
        $cart = StorefrontCart::firstOrFail();
        $cart->update(['customer_id' => $customer->id]);
        [$order] = app(StorefrontCheckoutService::class)->checkout($cart, 'pickup', null, null);

        $this->assertSame(['Maroon', 'Navy'], $order->items()->orderBy('color')->pluck('color')->all());
        $this->assertSame(3.0, (float) $aggregateStock->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', [
            'cloth_color_id' => $aggregateStock->id,
            'movement_type' => 'storefront_order',
            'quantity' => -3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'cloth_color_id' => $aggregateStock->id,
            'movement_type' => 'storefront_order',
            'quantity' => -4,
        ]);
    }

    public function test_excel_style_inventory_grid_creates_and_updates_color_rows(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);
        $brand = ClothBrand::create(['name' => 'Grid Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Grid Type', 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'inventory_grid' => 1,
            'name' => 'Grid Set',
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'stock_mode' => 'per_color',
            'color_names' => ['Navy', 'Maroon'],
            'color_hexes' => ['#112244', '#771122'],
            'color_lengths' => [12.5, 8],
            'price' => 900,
            'sale_price' => 1400,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->sole();
        $this->assertSame(Cloth::COLOR_TRACKING_PER_COLOR, $cloth->color_tracking_mode);
        $this->assertSame(['Navy', 'Maroon'], $cloth->colors()->orderBy('id')->pluck('color')->all());
        $this->assertSame(['#112244', '#771122'], $cloth->colors()->orderBy('id')->pluck('color_hex')->all());

        $colors = $cloth->colors()->orderBy('id')->get();
        $this->actingAs($owner)->put(route('admin.cloth.update', $cloth), [
            'inventory_grid' => 1,
            'name' => 'Grid Set Updated',
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'stock_mode' => 'per_color',
            'color_ids' => $colors->pluck('id')->all(),
            'original_color_names' => $colors->pluck('color')->all(),
            'color_names' => ['Navy Blue', 'Maroon'],
            'color_hexes' => ['#102030', '#771122'],
            'color_lengths' => [14, 7],
            'price' => 920,
            'sale_price' => 1450,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth->refresh();
        $this->assertSame('Grid Set Updated', $cloth->name);
        $this->assertSame(['Navy Blue', 'Maroon'], $cloth->colors()->orderBy('id')->pluck('color')->all());
        $this->assertSame([14.0, 7.0], $cloth->colors()->orderBy('id')->get()->map(fn ($color) => (float) $color->length)->all());
    }

    public function test_excel_style_inventory_grid_keeps_available_colors_on_one_shared_balance(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);
        $brand = ClothBrand::create(['name' => 'Shared Grid Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Shared Grid Type', 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'inventory_grid' => 1,
            'name' => 'Shared Grid Set',
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'stock_mode' => 'shared',
            'shared_length' => 40,
            'color_names' => ['Pink', 'Turquoise'],
            'color_hexes' => ['#FF69B4', '#40E0D0'],
            'price' => 800,
            'sale_price' => 1250,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->sole();
        $this->assertSame(Cloth::COLOR_TRACKING_DISPLAY_ONLY, $cloth->color_tracking_mode);
        $this->assertSame(['Pink', 'Turquoise'], $cloth->display_colors);
        $this->assertSame(['Pink' => '#FF69B4', 'Turquoise' => '#40E0D0'], $cloth->display_color_codes);
        $this->assertSame('عام', $cloth->colors()->sole()->color);
        $this->assertSame(40.0, (float) $cloth->colors()->sole()->length);
    }

    public function test_client_can_create_cloth_without_color_and_print_its_qr_label(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'tailoring_access' => false,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $brand = ClothBrand::create(['name' => 'QR Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'QR Type', 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'price' => 800,
            'sale_price' => 1200,
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame(sprintf('CLT-%d-%06d', $owner->id, $cloth->id), $cloth->stock_code);
        $this->assertDatabaseHas('cloth_colors', [
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => 'عام',
            'length' => 0,
        ]);
        $this->assertSame(1, ClothColor::where('cloth_id', $cloth->id)->count());

        $this->actingAs($owner)->get(route('admin.cloth.qr-label', $cloth))
            ->assertOk()
            ->assertSeeText($cloth->set_code)
            ->assertDontSeeText($cloth->stock_code)
            ->assertSee('<svg', false);
    }
}
