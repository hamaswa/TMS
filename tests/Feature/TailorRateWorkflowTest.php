<?php

namespace Tests\Feature;

use App\Models\Options;
use App\Models\Tailor;
use App\Models\User;
use Database\Seeders\OptionTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailorRateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_rate_for_their_own_sewing_option(): void
    {
        $this->seed(OptionTypesSeeder::class);
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'QA Tailor', 'phone_number1' => '03001230000',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $option = Options::create([
            'user_id' => $owner->id, 'option_id' => 1,
            'Name' => 'سادہ سلائی', 'slug' => 'simple',
        ]);

        $this->actingAs($owner)->post(route('admin.tailor-rates.store', $tailor), [
            'options_id' => $option->id,
            'price' => 500,
        ])->assertRedirect();

        $this->assertDatabaseHas('tailorsalaries', [
            'tailor_id' => $tailor->id,
            'options_id' => $option->id,
            'price' => 500,
        ]);
    }

    public function test_client_cannot_use_another_clients_sewing_option(): void
    {
        $this->seed(OptionTypesSeeder::class);
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $other = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $other->assignRole($role);
        $tailor = Tailor::create([
            'name' => 'Own Tailor', 'phone_number1' => '03001230001',
            'password' => bcrypt('QaTailor@2026'), 'user_id' => $owner->id,
        ]);
        $otherOption = Options::create([
            'user_id' => $other->id, 'option_id' => 1,
            'Name' => 'Other Sewing', 'slug' => 'other-sewing',
        ]);

        $this->actingAs($owner)->post(route('admin.tailor-rates.store', $tailor), [
            'options_id' => $otherOption->id,
            'price' => 500,
        ])->assertNotFound();

        $this->assertDatabaseMissing('tailorsalaries', ['tailor_id' => $tailor->id]);
    }
}
