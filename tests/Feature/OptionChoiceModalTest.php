<?php

namespace Tests\Feature;

use App\Models\MeasurementTemplate;
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
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'مردانہ شلوار قمیض',
            'system_fields' => ['swingtype'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);
        $contextRoute = route('admin.OptionType.index', ['template' => $template->id]);

        $this->actingAs($owner)->post(route('admin.Options.store'), [
            'OptionTypeId' => 1,
            'measurement_template_id' => $template->id,
            'Name' => 'سادہ سلائی',
        ])->assertRedirect($contextRoute)
            ->assertSessionHas('openChoiceModal', 1);

        $choice = Options::where('user_id', $owner->id)
            ->where('measurement_template_id', $template->id)->firstOrFail();
        $this->actingAs($owner)->get($contextRoute)
            ->assertOk()
            ->assertSeeText('مردانہ شلوار قمیض')
            ->assertSeeText('سادہ سلائی')
            ->assertSeeText('1 محفوظ انتخاب');

        $this->actingAs($owner)->put(route('admin.Options.update', $choice), [
            'OptionTypeId' => 1,
            'measurement_template_id' => $template->id,
            'Name' => 'سادہ شلوار قمیض',
        ])->assertRedirect($contextRoute);
        $this->assertSame('سادہ شلوار قمیض', $choice->fresh()->Name);

        $this->actingAs($owner)->delete(route('admin.Options.destroy', $choice))
            ->assertRedirect($contextRoute);
        $this->assertDatabaseMissing('options', ['id' => $choice->id]);
    }
}
