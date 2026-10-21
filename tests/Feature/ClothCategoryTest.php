<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClothCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => false, 'clothing_access' => true]);
        $owner->assignRole($role);

        return $owner;
    }

    private function category(User $owner, string $name, ?ClothCategory $parent = null): ClothCategory
    {
        return ClothCategory::create([
            'user_id' => $owner->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'name_ur' => $name.' ur',
            'slug' => ClothCategory::makeSlug($name, $parent),
        ]);
    }

    public function test_index_creates_the_default_men_category_and_lists_the_tree(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.clothcategory.index'))
            ->assertOk()
            ->assertSeeText('مردانہ')
            ->assertSeeText('نئی کیٹیگری شامل کریں');

        $this->assertDatabaseHas('cloth_categories', ['user_id' => $owner->id, 'slug' => 'men', 'parent_id' => null]);

        // Visiting again does not add a second default.
        $this->actingAs($owner)->get(route('admin.clothcategory.index'))->assertOk();
        $this->assertSame(1, ClothCategory::where('user_id', $owner->id)->count());
    }

    public function test_owner_can_create_a_parent_and_a_child_category(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'زنانہ', 'name' => 'Women', 'sort_order' => 2,
        ])->assertRedirect(route('admin.clothcategory.index'));
        $women = ClothCategory::where('user_id', $owner->id)->where('slug', 'women')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'عبایا', 'name' => 'Abayas', 'parent_id' => $women->id,
        ])->assertRedirect(route('admin.clothcategory.index'));

        $abayas = ClothCategory::where('user_id', $owner->id)->where('parent_id', $women->id)->firstOrFail();
        $this->assertSame('women-abayas', $abayas->slug);
        $this->assertSame('Women › Abayas', $abayas->fullName('en'));
        $this->assertSame('زنانہ › عبایا', $abayas->fullName('ur'));

        $this->actingAs($owner)->get(route('admin.clothcategory.index'))
            ->assertOk()->assertSeeText('عبایا')->assertSeeText('Women › Abayas');
    }

    public function test_duplicate_name_under_the_same_parent_is_rejected(): void
    {
        $owner = $this->owner();
        $women = $this->category($owner, 'Women');
        $this->category($owner, 'Abayas', $women);

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'نیا', 'name' => 'Abayas', 'parent_id' => $women->id,
        ])->assertSessionHasErrors('name');

        // The same name under a different parent is fine.
        $men = $this->category($owner, 'Men');
        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'نیا', 'name' => 'Abayas', 'parent_id' => $men->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_tree_is_limited_to_two_levels(): void
    {
        $owner = $this->owner();
        $women = $this->category($owner, 'Women');
        $abayas = $this->category($owner, 'Abayas', $women);

        // A child can't be a parent.
        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'فارمل', 'name' => 'Formal', 'parent_id' => $abayas->id,
        ])->assertSessionHasErrors('parent_id');

        // A parent with children can't move under another category.
        $men = $this->category($owner, 'Men');
        $this->actingAs($owner)->put(route('admin.clothcategory.update', $women), [
            'name_ur' => 'زنانہ', 'name' => 'Women', 'parent_id' => $men->id,
        ])->assertSessionHasErrors('parent_id');

        // A category can't be its own parent.
        $this->actingAs($owner)->put(route('admin.clothcategory.update', $men), [
            'name_ur' => 'مردانہ', 'name' => 'Men', 'parent_id' => $men->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($women->fresh()->parent_id);
    }

    public function test_another_shops_categories_cannot_be_used_or_changed(): void
    {
        $owner = $this->owner();
        $otherShopWomen = $this->category($this->owner(), 'Women');

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'عبایا', 'name' => 'Abayas', 'parent_id' => $otherShopWomen->id,
        ])->assertSessionHasErrors('parent_id');

        $this->actingAs($owner)->put(route('admin.clothcategory.update', $otherShopWomen), [
            'name_ur' => 'x', 'name' => 'Hacked',
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('admin.clothcategory.destroy', $otherShopWomen))->assertNotFound();
        $this->actingAs($owner)->patch(route('admin.clothcategory.toggle', $otherShopWomen))->assertNotFound();

        $this->assertSame('Women', $otherShopWomen->fresh()->name);
    }

    public function test_renaming_a_parent_updates_child_slugs(): void
    {
        $owner = $this->owner();
        $women = $this->category($owner, 'Women');
        $abayas = $this->category($owner, 'Abayas', $women);

        $this->actingAs($owner)->put(route('admin.clothcategory.update', $women), [
            'name_ur' => 'خواتین', 'name' => 'Ladies',
        ])->assertRedirect(route('admin.clothcategory.index'));

        $this->assertSame('ladies', $women->fresh()->slug);
        $this->assertSame('ladies-abayas', $abayas->fresh()->slug);
    }

    public function test_delete_is_blocked_while_children_or_cloths_exist_and_deactivate_works(): void
    {
        $owner = $this->owner();
        $women = $this->category($owner, 'Women');
        $abayas = $this->category($owner, 'Abayas', $women);
        Cloth::create(['user_id' => $owner->id, 'cloth_category_id' => $abayas->id, 'price' => 100]);

        $this->actingAs($owner)->delete(route('admin.clothcategory.destroy', $women))->assertSessionHas('delete');
        $this->actingAs($owner)->delete(route('admin.clothcategory.destroy', $abayas))->assertSessionHas('delete');
        $this->assertNotNull($women->fresh());
        $this->assertNotNull($abayas->fresh());

        $this->actingAs($owner)->patch(route('admin.clothcategory.toggle', $abayas));
        $this->assertFalse($abayas->fresh()->is_active);
        $this->assertSame($abayas->id, Cloth::where('user_id', $owner->id)->value('cloth_category_id'));

        $empty = $this->category($owner, 'Shawls', $women);
        $this->actingAs($owner)->delete(route('admin.clothcategory.destroy', $empty));
        $this->assertNull($empty->fresh());
    }

    public function test_in_category_scope_includes_child_categories(): void
    {
        $owner = $this->owner();
        $women = $this->category($owner, 'Women');
        $abayas = $this->category($owner, 'Abayas', $women);
        $men = $this->category($owner, 'Men');
        $abaya = Cloth::create(['user_id' => $owner->id, 'cloth_category_id' => $abayas->id, 'price' => 100]);
        $mens = Cloth::create(['user_id' => $owner->id, 'cloth_category_id' => $men->id, 'price' => 100]);

        $this->assertSame([$abaya->id], Cloth::inCategory($women->id)->pluck('id')->all());
        $this->assertSame([$abaya->id], Cloth::inCategory($abayas->id)->pluck('id')->all());
        $this->assertSame([$mens->id], Cloth::inCategory($men->id)->pluck('id')->all());
    }
}
