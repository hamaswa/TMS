<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_setting_can_be_created_without_a_logo_and_is_active(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner)->post(route('admin.insert-setting'), [
            'title' => 'Noor Tailors',
            'print_paper_size' => Setting::PRINT_PAPER_RECEIPT_80,
            'print_show_qr' => '1',
        ])->assertRedirect('admin/setting');

        $this->assertDatabaseHas('settings', [
            'user_id' => $owner->id,
            'name' => 'Noor Tailors',
            'logo' => '',
            'status' => '1',
        ]);
    }
}
