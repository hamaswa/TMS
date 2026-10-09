<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyCustomerTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_assigns_only_the_owners_default_and_preserves_measurements(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $default = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'Default', 'is_default' => true,
            'is_active' => true, 'system_fields' => ['necktype', 'sleeve'],
        ]);
        $custom = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'Custom', 'is_default' => false, 'is_active' => true,
        ]);
        $legacy = Customers::create(['user_id' => $owner->id, 'name' => 'Legacy', 'phone_number1' => '03001234567', 'necktype' => ' Old collar', 'sleeve' => 'Old cuff']);
        $assigned = Customers::create(['user_id' => $owner->id, 'name' => 'Assigned', 'phone_number1' => '03001234568', 'measurement_template_id' => $custom->id]);
        $other = Customers::create(['user_id' => $otherOwner->id, 'name' => 'Other shop', 'phone_number1' => '03001234569']);

        $migration = require database_path('migrations/2026_10_09_000000_assign_default_measurement_templates_to_existing_customers.php');
        $migration->up();
        $migration->up();

        $this->assertEquals($default->id, $legacy->fresh()->measurement_template_id);
        $this->assertSame(' Old collar', $legacy->fresh()->necktype);
        $this->assertSame('Old cuff', $legacy->fresh()->sleeve);
        $this->assertEquals($custom->id, $assigned->fresh()->measurement_template_id);
        $this->assertNull($other->fresh()->measurement_template_id);
    }

    public function test_edit_defaults_unassigned_customer_without_losing_saved_preferences(): void
    {
        $owner = User::factory()->create(['tailoring_access' => true, 'is_business_owner' => true]);
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $default = MeasurementTemplate::create([
            'user_id' => $owner->id, 'name' => 'Default', 'is_default' => true,
            'is_active' => true, 'system_fields' => ['necktype', 'sleeve'],
        ]);
        $customer = Customers::create(['user_id' => $owner->id, 'name' => 'Legacy', 'phone_number1' => '03001234567', 'necktype' => ' Old collar', 'sleeve' => 'Old cuff']);

        $this->actingAs($owner)->get(route('admin.Customers.edit', $customer))
            ->assertOk()
            ->assertViewHas('customer', fn ($value) => $value->measurement_template_id === $default->id)
            ->assertViewHas('savedMeasurementValues', fn ($values) => $values->get('system.necktype') === ' Old collar' && $values->get('system.sleeve') === 'Old cuff');
    }
}

