<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClothBrand;
use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothType;
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
            ->assertSee('assets/images/logo.jpg');

        $brand = ClothBrand::where('user_id', $owner->id)->firstOrFail();
        $this->actingAs($owner)->get(route('admin.clothbrand.show', $brand))
            ->assertRedirect(route('admin.clothbrand.edit', $brand));
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

        $this->actingAs($owner->fresh())->post(route('admin.cloth.store'), [
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'colors' => 'نیلا، سرمئی',
            'length' => [12.5, 8],
            'length_colors' => ['نیلا', 'سرمئی'],
            'price' => 1000,
            'sale_price' => 1450,
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame(['نیلا', 'سرمئی'], $cloth->colors()->orderBy('id')->pluck('color')->all());
        $this->assertDatabaseCount('cloth_images', 0);
    }
}
