<?php

namespace Tests\Feature;

use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InAppConfirmationModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_destructive_actions_render_the_shared_in_app_confirmation_modal(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        Tailor::create([
            'name' => 'Modal QA Tailor',
            'phone_number1' => '03001239876',
            'password' => bcrypt('Modal@2026'),
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.Tailor.index'))
            ->assertOk()
            ->assertSee('id="tmsConfirmModal"', false)
            ->assertSee('data-confirm="کیا آپ واقعی یہ درزی حذف کرنا چاہتے ہیں؟"', false)
            ->assertSee('assets/js/confirm-modal.js', false)
            ->assertDontSee('return confirm(', false);
    }

    public function test_application_views_and_custom_scripts_do_not_use_native_confirm_dialogs(): void
    {
        $files = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->push(new \SplFileInfo(public_path('assets/js/custom.js')))
            ->push(new \SplFileInfo(public_path('assets/js/confirm-modal.js')));

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'confirm(',
                strtolower($contents),
                $file->getPathname().' still uses a browser-native confirmation dialog.'
            );
        }
    }
}
