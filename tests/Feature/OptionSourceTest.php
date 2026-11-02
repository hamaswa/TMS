<?php

namespace Tests\Feature;

use App\Models\MeasurementTemplate;
use App\Models\Options;
use App\Models\User;
use Database\Seeders\OptionTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OptionSourceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private MeasurementTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OptionTypesSeeder::class);
        $this->owner = User::factory()->create(['tailoring_access' => true]);
        $this->owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $this->template = MeasurementTemplate::create([
            'user_id' => $this->owner->id,
            'name' => 'مردانہ شلوار قمیض',
            'system_fields' => ['swingtype'],
            'custom_field_ids' => [],
            'is_builtin' => true,
            'is_default' => true,
            'is_active' => true,
        ]);
        foreach (['سیڈ سلائی' => true, 'اپنی سلائی' => false] as $name => $isDefault) {
            Options::create([
                'user_id' => $this->owner->id,
                'measurement_template_id' => $this->template->id,
                'option_id' => 1,
                'slug' => 'choice-'.(int) $isDefault,
                'Name' => $name,
                'is_default' => $isDefault,
            ]);
        }
    }

    public function test_new_accounts_show_only_the_default_list(): void
    {
        $this->assertSame('default', $this->owner->fresh()->option_source);

        $this->actingAs($this->owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSee('سیڈ سلائی')
            ->assertDontSee('اپنی سلائی');
    }

    public function test_choosing_own_shows_only_choices_the_shop_created(): void
    {
        $page = route('admin.OptionType.index', ['template' => $this->template->id]);

        $this->actingAs($this->owner)->from($page)
            ->put(route('admin.option-source.update'), ['option_source' => 'own'])
            ->assertRedirect($page);
        $this->assertSame('own', $this->owner->fresh()->option_source);

        $this->actingAs($this->owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSee('اپنی سلائی')
            ->assertDontSee('سیڈ سلائی');
        $this->actingAs($this->owner)->get($page)
            ->assertOk()
            ->assertSee('اپنی سلائی')
            ->assertDontSee('سیڈ سلائی');
    }

    public function test_invalid_source_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->put(route('admin.option-source.update'), ['option_source' => 'everything'])
            ->assertSessionHasErrors('option_source');
        $this->assertSame('default', $this->owner->fresh()->option_source);
    }

    public function test_own_choice_may_reuse_a_hidden_default_name(): void
    {
        $this->owner->forceFill(['option_source' => 'own'])->save();

        $this->actingAs($this->owner)->post(route('admin.Options.store'), [
            'OptionTypeId' => 1,
            'measurement_template_id' => $this->template->id,
            'Name' => 'سیڈ سلائی',
        ])->assertRedirect();

        $this->assertDatabaseHas('options', [
            'user_id' => $this->owner->id,
            'measurement_template_id' => $this->template->id,
            'Name' => 'سیڈ سلائی',
            'is_default' => false,
        ]);
    }

    public function test_choice_added_on_the_default_list_stays_visible(): void
    {
        $this->actingAs($this->owner)->post(route('admin.Options.store'), [
            'OptionTypeId' => 1,
            'measurement_template_id' => $this->template->id,
            'Name' => 'نئی سلائی',
        ])->assertRedirect();

        $this->actingAs($this->owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSee('نئی سلائی');
    }

    public function test_custom_template_choices_stay_visible_on_the_default_list(): void
    {
        $custom = MeasurementTemplate::create([
            'user_id' => $this->owner->id,
            'name' => 'واسکٹ',
            'system_fields' => ['swingtype'],
            'custom_field_ids' => [],
            'is_active' => true,
        ]);
        Options::create([
            'user_id' => $this->owner->id,
            'measurement_template_id' => $custom->id,
            'option_id' => 1,
            'slug' => 'waistcoat-choice',
            'Name' => 'واسکٹ سلائی',
        ]);

        $names = Options::where('user_id', $this->owner->id)->inOwnerSource($this->owner->id)->pluck('Name');

        $this->assertTrue($names->contains('واسکٹ سلائی'));
        $this->assertFalse($names->contains('اپنی سلائی'));
    }
}
