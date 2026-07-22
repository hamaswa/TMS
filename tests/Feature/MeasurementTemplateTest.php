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

class MeasurementTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_manage_only_their_own_templates_and_choose_a_default(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $field = $this->field($owner, 'گھٹنے کی چوڑائی');

        $this->actingAs($owner)->post(route('admin.measurement-templates.store'), [
            'name' => 'مردانہ شلوار قمیض',
            'description' => 'مکمل سوٹ',
            'system_fields' => ['length', 'arms', 'chuta'],
            'custom_field_ids' => [$field->id],
            'is_default' => 1,
        ])->assertRedirect();

        $template = MeasurementTemplate::where('user_id', $owner->id)->firstOrFail();
        $this->assertTrue($template->is_default);
        $this->assertSame(['length', 'arms', 'chuta'], $template->system_fields);
        $this->assertSame([$field->id], $template->custom_field_ids);

        $this->actingAs($otherOwner)->put(route('admin.measurement-templates.update', $template), [
            'name' => 'Changed',
            'system_fields' => ['length'],
        ])->assertNotFound();
        $this->assertSame('مردانہ شلوار قمیض', $template->fresh()->name);

        $this->actingAs($owner)->get(route('admin.measurement-templates.index'))
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
        ])->assertRedirect('admin/Customers');

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
            ->assertOk()
            ->assertSee('name="measurement_template_id"', false)
            ->assertSeeText('واسکٹ');
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
