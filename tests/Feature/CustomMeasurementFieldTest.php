<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\MeasurementField;
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

        $this->actingAs($owner)->post(route('admin.measurement-fields.store'), [
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
        ])->assertRedirect('admin/Customers');

        $customer = Customers::where('user_id', $owner->id)->firstOrFail();
        $this->assertDatabaseHas('customer_measurement_values', [
            'customer_id' => $customer->id,
            'measurement_field_id' => $field->id,
            'value' => '3.25',
        ]);
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
