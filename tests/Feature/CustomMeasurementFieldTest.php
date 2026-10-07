<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\User;
use App\Services\MeasurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomMeasurementFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_manage_only_their_own_custom_measurement_fields(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $template = app(\App\Services\TailoringOptionDefaultsService::class)->seedMeasurementTemplateForOwner($owner->id);

        $this->actingAs($owner)->post(route('admin.measurement-fields.store'), [
            'measurement_template_id' => $template->id,
            'label' => 'گھٹنے کی چوڑائی',
            'field_type' => 'number',
            'unit' => 'inch',
            'is_required' => '1',
            'is_active' => '1',
            'sort_order' => 3,
        ])->assertRedirect();

        $field = MeasurementField::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('گھٹنے کی چوڑائی', $field->label);
        $this->assertTrue($field->is_required);

        $this->actingAs($otherOwner)->put(route('admin.measurement-fields.update', $field), [
            'measurement_template_id' => $template->id,
            'label' => 'Changed',
            'field_type' => 'text',
            'unit' => 'none',
            'is_active' => '1',
        ])->assertNotFound();

        $this->assertSame('گھٹنے کی چوڑائی', $field->fresh()->label);
    }

    public function test_required_custom_measurement_is_validated_and_saved_for_customer(): void
    {
        $owner = $this->owner();
        $field = MeasurementField::create([
            'user_id' => $owner->id,
            'label' => 'کالر اونچائی',
            'key' => 'collar_height',
            'field_type' => 'number',
            'unit' => 'inch',
            'is_required' => true,
            'is_active' => true,
        ]);
        $payload = ['name' => 'Custom Customer', 'contact' => '03001112222'];

        $this->actingAs($owner)->post(route('admin.Customers.store'), $payload)
            ->assertSessionHasErrors('custom_measurements.'.$field->id);

        $this->actingAs($owner)->post(route('admin.Customers.store'), $payload + [
            'custom_measurements' => [$field->id => '3.25'],
        ])->assertRedirect(route('admin.Customers.index', ['created' => 1]));

        $customer = Customers::where('user_id', $owner->id)->firstOrFail();
        $this->assertDatabaseHas('customer_measurement_values', [
            'customer_id' => $customer->id,
            'measurement_field_id' => $field->id,
            'value' => '3.25',
        ]);

        $this->actingAs($owner)->get(route('admin.Customers.edit', $customer))
            ->assertOk()
            ->assertSeeText('کالر اونچائی')
            ->assertSee('data-measurement-field="custom.'.$field->id.'"', false)
            ->assertDontSee('<h2>اضافی پیمائش</h2>', false);
    }

    public function test_urdu_commas_create_individual_select_options(): void
    {
        $owner = $this->owner();
        $template = app(\App\Services\TailoringOptionDefaultsService::class)->seedMeasurementTemplateForOwner($owner->id);

        $this->actingAs($owner)->post(route('admin.measurement-fields.store'), [
            'measurement_template_id' => $template->id,
            'label' => 'فٹنگ انداز',
            'field_type' => 'select',
            'unit' => 'none',
            'options_text' => 'تنگ، درمیانہ، کھلا',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(
            ['تنگ', 'درمیانہ', 'کھلا'],
            MeasurementField::where('user_id', $owner->id)->firstOrFail()->options,
        );
        $this->assertNull(MeasurementField::where('user_id', $owner->id)->firstOrFail()->unit);

        $this->actingAs($owner)->get(route('admin.measurement-templates.edit', $template))
            ->assertOk()
            ->assertSee('data-option-editor', false)
            ->assertSee('template-option-add', false)
            ->assertSeeText('ہر انتخاب الگ شامل کریں')
            ->assertDontSeeText('اردو کوما (،) سے الگ کریں');
    }

    public function test_new_custom_field_belongs_only_to_its_template(): void
    {
        $owner = $this->owner();
        $waistcoat = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'واسکٹ', 'system_fields' => ['length'],
            'custom_field_ids' => [], 'is_default' => true, 'is_active' => true,
        ]);
        $suit = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'ڈریس سوٹ', 'system_fields' => ['length'],
            'custom_field_ids' => [], 'is_default' => false, 'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.measurement-fields.store'), [
            'label' => 'کالر اونچائی',
            'measurement_template_id' => $waistcoat->id,
            'field_type' => 'number',
            'unit' => 'inch',
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $field = MeasurementField::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame($waistcoat->id, $field->measurement_template_id);
        $this->assertSame([$field->id], $waistcoat->fresh()->custom_field_ids);
        $this->assertSame([], $suit->fresh()->custom_field_ids);
    }

    public function test_order_snapshot_remains_unchanged_after_customer_and_field_are_edited(): void
    {
        $owner = $this->owner();
        $field = MeasurementField::create([
            'user_id' => $owner->id,
            'label' => 'گھٹنے کی چوڑائی',
            'key' => 'knee_width',
            'field_type' => 'number',
            'unit' => 'inch',
            'is_active' => true,
        ]);
        $customer = Customers::create([
            'name' => 'Snapshot Customer', 'phone_number1' => '03003334444',
            'user_id' => $owner->id, 'length' => '42',
        ]);
        $customer->measurementValues()->create(['measurement_field_id' => $field->id, 'value' => '18.5']);
        $order = Order::create(['customerId' => $customer->id, 'sub_customer' => $customer->id, 'userId' => $owner->id]);

        app(MeasurementService::class)->snapshotOrder($order, $customer);
        $customer->update(['length' => '44']);
        $customer->measurementValues()->where('measurement_field_id', $field->id)->update(['value' => '19']);
        $field->update(['label' => 'نیا نام']);

        $this->assertDatabaseHas('order_measurement_values', [
            'order_id' => $order->id, 'source_key' => 'system.length', 'label' => 'لمبائی', 'value' => '42',
        ]);
        $this->assertDatabaseHas('order_measurement_values', [
            'order_id' => $order->id, 'source_key' => 'custom.'.$field->id,
            'label' => 'گھٹنے کی چوڑائی', 'value' => '18.5',
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
