<?php

namespace Tests\Feature;

use App\Models\Options;
use App\Models\User;
use Database\Seeders\OptionTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OptionChoiceModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_manages_sewing_choices_from_the_category_modal(): void
    {
        $this->seed(OptionTypesSeeder::class);
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner)->post(route('admin.Options.store'), [
            'OptionTypeId' => 1,
            'Name' => 'سادہ سلائی',
        ])->assertRedirect(route('admin.OptionType.index'))
            ->assertSessionHas('openChoiceModal', 1);

        $choice = Options::where('user_id', $owner->id)->firstOrFail();
        $this->actingAs($owner)->get(route('admin.OptionType.index'))
            ->assertOk()
            ->assertSeeText('سادہ سلائی')
            ->assertSeeText('1 محفوظ انتخاب');

        $this->actingAs($owner)->put(route('admin.Options.update', $choice), [
            'OptionTypeId' => 1,
            'Name' => 'سادہ شلوار قمیض',
        ])->assertRedirect(route('admin.OptionType.index'));
        $this->assertSame('سادہ شلوار قمیض', $choice->fresh()->Name);

        $this->actingAs($owner)->delete(route('admin.Options.destroy', $choice))
            ->assertRedirect(route('admin.OptionType.index'));
        $this->assertDatabaseMissing('options', ['id' => $choice->id]);
    }
}
