<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_is_paginated_by_cloth_set_and_keeps_sets_without_color_stock(): void
    {
        [$owner, $brand, $type] = $this->catalogOwner();

        foreach (range(1, 26) as $index) {
            $cloth = $this->cloth($owner, $brand, $type);
            ClothColor::create([
                'cloth_id' => $cloth->id,
                'color' => 'Color '.$index,
                'length' => $index,
                'average_unit_cost' => 100,
                'user_id' => $owner->id,
            ]);
        }
        $withoutStock = $this->cloth($owner, $brand, $type);

        $response = $this->actingAs($owner)->get(route('admin.cloth.index'));

        $response->assertOk()
            ->assertSeeText('کپڑوں کے سیٹ')
            ->assertSeeText('کل 27 سیٹ')
            ->assertSee('id="clothFilterDrawer"', false)
            ->assertDontSee('class="dropdown-item cloth-qr-modal-trigger"', false)
            ->assertSee('target="_blank" rel="noopener"', false)
            ->assertDontSeeText('سیلز اسکین QR')
            ->assertDontSeeText('تمام سیٹ QR — نیا ٹیب')
            ->assertDontSeeText('انوینٹری کی تفصیل')
            ->assertSeeText($withoutStock->name)
            ->assertDontSeeText($withoutStock->set_code)
            ->assertSeeText('اس سیٹ کا اسٹاک ریکارڈ موجود نہیں۔')
            ->assertViewHas('cloths', fn ($cloths) => $cloths->total() === 27 && $cloths->count() === 25)
            ->assertViewHas('summary', fn ($summary) => $summary['sets'] === 27 && $summary['colors'] === 26);
    }

    public function test_inventory_filters_searches_and_sorting_are_server_side(): void
    {
        [$owner, $brand, $type] = $this->catalogOwner();
        $otherBrand = ClothBrand::create(['name' => 'Other Brand', 'user_id' => $owner->id]);
        $wanted = $this->cloth($owner, $brand, $type, Cloth::SALE_PRICE_PER_SUIT);
        $wanted->update(['name' => 'Dilbar']);
        ClothColor::create(['cloth_id' => $wanted->id, 'color' => 'Navy', 'length' => 7, 'average_unit_cost' => 120, 'user_id' => $owner->id]);
        $other = $this->cloth($owner, $otherBrand, $type);
        $other->update(['name' => 'Waqar']);
        ClothColor::create(['cloth_id' => $other->id, 'color' => 'Black', 'length' => 40, 'average_unit_cost' => 110, 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('admin.cloth.index', [
            'q' => 'Dilbar',
            'brand' => $brand->id,
            'color' => 'Navy',
            'stock' => 'low',
            'basis' => Cloth::SALE_PRICE_PER_SUIT,
            'sort' => 'stock_low',
            'per_page' => 50,
        ]));

        $response->assertOk()
            ->assertSeeText($wanted->name)
            ->assertDontSeeText($wanted->set_code)
            ->assertDontSeeText($other->name)
            ->assertViewHas('cloths', fn ($cloths) => $cloths->total() === 1 && $cloths->first()->is($wanted))
            ->assertViewHas('summary', fn ($summary) => $summary['sets'] === 1 && $summary['meters'] === 7.0);
    }

    public function test_inventory_edit_uses_the_same_sectioned_form_language_as_create(): void
    {
        [$owner, $brand, $type] = $this->catalogOwner();
        $cloth = $this->cloth($owner, $brand, $type);
        $cloth->update(['name' => 'Dilbar', 'default_sale_length' => 4.5]);
        ClothColor::create([
            'cloth_id' => $cloth->id,
            'color' => 'Navy',
            'length' => 24,
            'average_unit_cost' => 100,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.edit-cloths', [
            'id' => $cloth->id,
            'color' => 'Navy',
        ]));

        $response->assertOk()
            ->assertSee('id="clothInventoryForm"', false)
            ->assertSee('class="cloth-form-card"', false)
            ->assertSeeText('سیٹ کی بنیادی معلومات')
            ->assertSeeText('رنگ اور موجودہ اسٹاک')
            ->assertSeeText('ہر رنگ کا الگ اسٹاک')
            ->assertSeeText('فروخت کی دستیابی')
            ->assertSee('"length":"24.00"', false)
            ->assertDontSee('add-more-length', false);
    }

    private function catalogOwner(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);
        $brand = ClothBrand::create(['name' => 'Directory Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Directory Type', 'user_id' => $owner->id]);

        return [$owner, $brand, $type];
    }

    private function cloth(User $owner, ClothBrand $brand, ClothType $type, string $basis = Cloth::SALE_PRICE_PER_METER): Cloth
    {
        return Cloth::create([
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'price' => 100,
            'sale_price' => 160,
            'suit_sale_price' => $basis === Cloth::SALE_PRICE_PER_SUIT ? 720 : null,
            'default_sale_length' => $basis === Cloth::SALE_PRICE_PER_SUIT ? 4.5 : null,
            'sale_price_basis' => $basis,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
            'user_id' => $owner->id,
        ]);
    }
}
