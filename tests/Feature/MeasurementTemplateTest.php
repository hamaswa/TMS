<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\StandardMeasurementProfile;
use App\Models\User;
use App\Services\MeasurementService;
use App\Services\TailoringOptionDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeasurementTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_builtin_shalwar_kameez_is_the_permanent_default(): void
    {
        $owner = $this->owner();
        $builtin = app(TailoringOptionDefaultsService::class)->seedMeasurementTemplateForOwner($owner->id);

        $this->assertTrue($builtin->is_builtin);
        $this->assertTrue($builtin->is_default);
        $this->assertTrue($builtin->is_active);
        $this->assertSame(array_keys(MeasurementService::SYSTEM_FIELDS), $builtin->system_fields);

        $this->actingAs($owner)->post(route('admin.measurement-templates.store'), [
            'name' => 'واسکٹ',
            'system_fields' => ['length', 'necktype', 'jeab'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $template = MeasurementTemplate::where('user_id', $owner->id)->where('name', 'واسکٹ')->firstOrFail();
        $this->assertFalse($template->is_default);

        $this->actingAs($owner)->delete(route('admin.measurement-templates.destroy', $builtin))
            ->assertRedirect()->assertSessionHasErrors('template');

        $this->assertTrue($builtin->fresh()->is_active);
    }

    public function test_template_selector_also_controls_sewing_preference_groups(): void
    {
        $owner = $this->owner();
        app(TailoringOptionDefaultsService::class)->seedForOwner($owner->id);
        $waistcoat = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'واسکٹ',
            'system_fields' => ['length', 'necktype', 'jeab'],
            'custom_field_ids' => [],
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.Customers.create'));

        $response->assertOk()
            ->assertSee('data-template-system-field="necktype"', false)
            ->assertSee('data-template-system-field="jeab"', false)
            ->assertSee('data-template-system-field="sleeve"', false)
            ->assertSee('selectedSystem.indexOf(input.dataset.templateSystemField)', false)
            ->assertSee('value="'.$waistcoat->id.'"', false);
    }

    public function test_directory_stays_compact_and_each_template_has_its_own_builder(): void
    {
        $owner = $this->owner();
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'واسکٹ',
            'system_fields' => ['length', 'necktype', 'jeab'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->get(route('admin.measurement-templates.index'))
            ->assertOk()
            ->assertSeeText('واسکٹ')
            ->assertSeeText('ٹیمپلیٹ کھولیں')
            ->assertDontSee('name="system_fields[]"', false);

        $this->actingAs($owner)->get(route('admin.measurement-templates.edit', $template))
            ->assertOk()
            ->assertSeeText('جسمانی پیمائش')
            ->assertSeeText('سلائی اور ڈیزائن کی پسند')
            ->assertSeeText('گاہک فارم کی ترتیب')
            ->assertSeeText('ایک کالم')
            ->assertSeeText('دو کالم')
            ->assertSee('move-column', false)
            ->assertSee('name="system_fields[]"', false);
    }

    public function test_client_can_manage_only_their_own_template_and_layout(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $field = $this->field($owner, 'گھٹنے کی چوڑائی');
        $template = app(TailoringOptionDefaultsService::class)->seedMeasurementTemplateForOwner($owner->id);

        $this->actingAs($owner)->put(route('admin.measurement-templates.update', $template), [
            'name' => 'نام تبدیل نہ ہو',
            'description' => 'مکمل سوٹ',
            'system_fields' => ['length', 'arms', 'chuta'],
            'custom_field_ids' => [$field->id],
            'layout_columns' => 1,
            'field_layout' => [
                ['source' => 'system.length', 'column' => 'right', 'order' => 10],
                ['source' => 'custom.'.$field->id, 'column' => 'left', 'order' => 20],
            ],
        ])->assertRedirect();

        $template->refresh();
        $this->assertTrue($template->is_default);
        $this->assertTrue($template->is_builtin);
        $this->assertSame('مردانہ شلوار قمیض', $template->name);
        $this->assertSame(array_keys(MeasurementService::SYSTEM_FIELDS), $template->system_fields);
        $this->assertSame([$field->id], $template->custom_field_ids);
        $this->assertSame(1, $template->layout_columns);
        $this->assertSame('left', collect($template->field_layout)->firstWhere('source', 'custom.'.$field->id)['column']);

        $this->actingAs($otherOwner)->put(route('admin.measurement-templates.update', $template), [
            'name' => 'Changed',
            'system_fields' => ['length'],
        ])->assertNotFound();
        $this->assertSame('مردانہ شلوار قمیض', $template->fresh()->name);

        $this->actingAs($owner)->get(route('admin.measurement-templates.edit', $template))
            ->assertOk()
            ->assertSeeText('مردانہ شلوار قمیض')
            ->assertSeeText('گھٹنے کی چوڑائی');
    }

    public function test_selected_template_controls_required_customer_measurements(): void
    {
        $owner = $this->owner();
        $included = $this->field($owner, 'کالر اونچائی', true);
        $excluded = $this->field($owner, 'کف چوڑائی', true);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'قمیض',
            'system_fields' => ['length', 'arms'],
            'custom_field_ids' => [$included->id],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Template Customer',
            'contact' => '03006660000',
            'measurement_template_id' => $template->id,
            'length' => 42,
            'arms' => 24,
            'custom_measurements' => [$included->id => '3.5'],
        ])->assertRedirect(route('admin.Customers.index', ['created' => 1]));

        $customer = Customers::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame($template->id, $customer->measurement_template_id);
        $this->assertDatabaseHas('customer_measurement_values', [
            'customer_id' => $customer->id,
            'measurement_field_id' => $included->id,
            'value' => '3.5',
        ]);
        $this->assertDatabaseMissing('customer_measurement_values', [
            'customer_id' => $customer->id,
            'measurement_field_id' => $excluded->id,
        ]);
        $this->assertDatabaseHas('customer_measurement_histories', [
            'customer_id' => $customer->id,
            'measurement_template_id' => $template->id,
            'source' => 'customer_created',
        ]);
        $history = $customer->measurementHistories()->firstOrFail();
        $this->assertDatabaseHas('customer_measurement_history_values', [
            'customer_measurement_history_id' => $history->id,
            'source_key' => 'system.length',
            'value' => '42',
        ]);

        $this->actingAs($owner)->get(route('admin.Customers.create'))
            ->assertOk()
            ->assertSee('name="measurement_template_id"', false)
            ->assertSeeText('قمیض');
    }

    public function test_history_is_added_only_when_measurements_change(): void
    {
        $owner = $this->owner();
        $field = $this->field($owner, 'کالر اونچائی');
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'تاریخ ٹیمپلیٹ',
            'system_fields' => ['length'],
            'custom_field_ids' => [$field->id],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'History Customer',
            'contact' => '03008880000',
            'measurement_template_id' => $template->id,
            'length' => 42,
            'custom_measurements' => [$field->id => '3.5'],
        ])->assertRedirect();
        $customer = Customers::where('user_id', $owner->id)->firstOrFail();
        $this->assertCount(1, $customer->measurementHistories);

        $this->actingAs($owner)->put(route('admin.Customers.update', $customer), [
            'name' => 'History Customer',
            'contact' => '03008880001',
            'measurement_template_id' => $template->id,
            'length' => 42,
            'custom_measurements' => [$field->id => '3.5'],
        ])->assertRedirect();
        $this->assertSame(1, $customer->measurementHistories()->count());

        $this->actingAs($owner)->put(route('admin.Customers.update', $customer), [
            'name' => 'History Customer',
            'contact' => '03008880001',
            'measurement_template_id' => $template->id,
            'length' => 43,
            'custom_measurements' => [$field->id => '3.5'],
        ])->assertRedirect();
        $this->assertSame(2, $customer->measurementHistories()->count());
        $latest = $customer->measurementHistories()->with('values')->firstOrFail();
        $this->assertSame('customer_update', $latest->source);
        $this->assertSame('43', $latest->values->firstWhere('source_key', 'system.length')->value);

        $this->actingAs($owner)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'measurements']))
            ->assertOk()
            ->assertSeeText('پیمائش کی تاریخ')
            ->assertSeeText('تبدیل شدہ پیمائش')
            ->assertSeeText('43');
    }

    public function test_order_snapshot_contains_only_the_selected_template_fields(): void
    {
        $owner = $this->owner();
        $included = $this->field($owner, 'گھٹنے کی چوڑائی');
        $excluded = $this->field($owner, 'کف چوڑائی');
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'واسکٹ',
            'system_fields' => ['length'],
            'custom_field_ids' => [$included->id],
            'is_default' => false,
            'is_active' => true,
        ]);
        $customer = Customers::create([
            'name' => 'Snapshot Template Customer',
            'phone_number1' => '03007770000',
            'user_id' => $owner->id,
            'measurement_template_id' => $template->id,
            'length' => 42,
            'arms' => 24,
        ]);
        $customer->measurementValues()->createMany([
            ['measurement_field_id' => $included->id, 'value' => '18'],
            ['measurement_field_id' => $excluded->id, 'value' => '10'],
        ]);
        $order = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'measurement_template_id' => $template->id,
            'userId' => $owner->id,
        ]);

        app(MeasurementService::class)->snapshotOrder($order, $customer, $template);

        $this->assertDatabaseHas('order_measurement_values', ['order_id' => $order->id, 'source_key' => 'system.length']);
        $this->assertDatabaseHas('order_measurement_values', ['order_id' => $order->id, 'source_key' => 'custom.'.$included->id]);
        $this->assertDatabaseMissing('order_measurement_values', ['order_id' => $order->id, 'source_key' => 'system.arms']);
        $this->assertDatabaseMissing('order_measurement_values', ['order_id' => $order->id, 'source_key' => 'custom.'.$excluded->id]);

        $this->actingAs($owner)->get(route('admin.order.create', $customer))
            ->assertRedirect(route('admin.counter-orders.create', [
                'customer' => $customer->id,
                'profile' => $customer->id,
            ]));
    }

    public function test_order_form_exposes_missing_template_measurements_before_submit(): void
    {
        $owner = $this->owner();
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'نامکمل واسکٹ',
            'system_fields' => ['length', 'teraa'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);
        $customer = Customers::create([
            'name' => 'Missing Measurement Customer',
            'phone_number1' => '03007770111',
            'user_id' => $owner->id,
            'measurement_template_id' => $template->id,
            'length' => 42,
            'teraa' => null,
        ]);

        $this->actingAs($owner)->get(route('admin.order.create', $customer))
            ->assertRedirect(route('admin.counter-orders.create', [
                'customer' => $customer->id,
                'profile' => $customer->id,
            ]));
    }

    public function test_shop_owner_can_save_arbitrarily_named_standard_measurements_per_template(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $field = $this->field($owner, 'کالر اونچائی', true);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'مردانہ شلوار قمیض',
            'system_fields' => ['length', 'arms'],
            'custom_field_ids' => [$field->id],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.standard-measurement-profiles.store', $template), [
            'name' => 'Medium A',
            'sort_order' => 20,
            'values' => [
                'system' => ['length' => '41.5', 'arms' => '24'],
                'custom' => [$field->id => '3.25'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $profile = StandardMeasurementProfile::firstOrFail();
        $this->assertSame('Medium A', $profile->name);
        $this->assertSame($template->id, $profile->measurement_template_id);
        $this->assertSame(['system.length', 'system.arms', 'custom.'.$field->id], collect($profile->measurement_values)->pluck('source_key')->all());
        $this->actingAs($owner)->get(route('admin.standard-measurement-profiles.index', $template))
            ->assertOk()->assertSeeText('Medium A')->assertSeeText('3 پیمائشیں');

        $this->actingAs($otherOwner)->put(route('admin.standard-measurement-profiles.update', $profile), [
            'name' => 'Changed',
            'values' => ['system' => ['length' => '40', 'arms' => '23']],
        ])->assertNotFound();
        $this->assertSame('Medium A', $profile->fresh()->name);
    }

    public function test_standard_sizes_contain_measurements_not_sewing_preferences(): void
    {
        $owner = $this->owner();
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'واسکٹ',
            'system_fields' => ['length', 'necktype', 'jeab'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->get(route('admin.standard-measurement-profiles.index', $template))
            ->assertOk()
            ->assertSee('name="values[system][length]"', false)
            ->assertDontSee('name="values[system][necktype]"', false)
            ->assertDontSee('name="values[system][jeab]"', false);

        $this->actingAs($owner)->post(route('admin.standard-measurement-profiles.store', $template), [
            'name' => 'Medium',
            'values' => ['system' => ['length' => '26']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(
            ['system.length'],
            collect(StandardMeasurementProfile::firstOrFail()->measurement_values)->pluck('source_key')->all(),
        );
    }

    private function field(User $owner, string $label, bool $required = false): MeasurementField
    {
        return MeasurementField::create([
            'user_id' => $owner->id,
            'label' => $label,
            'key' => 'field_'.uniqid(),
            'field_type' => 'number',
            'unit' => 'inch',
            'is_required' => $required,
            'is_active' => true,
        ]);
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => false]);
        $owner->assignRole($role);

        return $owner;
    }
}
