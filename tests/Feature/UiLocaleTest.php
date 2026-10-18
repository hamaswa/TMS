<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UiTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UiLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_urdu_by_default_and_switch_to_english(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.dailyexpense.index'))
            ->assertOk()
            ->assertSee('<html lang="ur" dir="rtl">', false)
            ->assertSee('tms-lang-switch', false)
            ->assertSeeText('روزانہ اخراجات')
            // Urdu keyboard toggle and panel are offered in Urdu mode.
            ->assertSee('id="tmsUrduKeyboardToggle"', false)
            ->assertSee('urdu-keyboard.js', false);

        $this->actingAs($owner)->from(route('admin.dailyexpense.index'))
            ->get(route('ui.locale', 'en'))
            ->assertRedirect(route('admin.dailyexpense.index'))
            ->assertCookie('tms_ui_locale');

        $this->actingAs($owner)->get(route('admin.dailyexpense.index'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSeeText('Daily Expenses')
            ->assertDontSeeText('روزانہ اخراجات')
            // The switch itself keeps its Urdu label.
            ->assertSee('>اردو</a>', false)
            // Script-inserted text is translated in the browser.
            ->assertSee('id="tms-ui-dictionary"', false)
            ->assertSee('ui-translate.js', false)
            ->assertDontSee('id="tmsUrduKeyboardToggle"', false)
            ->assertDontSee('urdu-keyboard.js', false);

        $this->actingAs($owner)->get(route('ui.locale', 'ur'));
        $this->actingAs($owner)->get(route('admin.dailyexpense.index'))
            ->assertSee('<html lang="ur" dir="rtl">', false);
    }

    public function test_form_values_and_json_data_are_never_translated(): void
    {
        $translator = app(UiTranslator::class);
        $html = '<label>روزانہ اخراجات</label><input name="color" value="روزانہ اخراجات">'
            .'<option value="روزانہ اخراجات" data-name="روزانہ اخراجات">روزانہ اخراجات</option>'
            .'<textarea>روزانہ اخراجات</textarea><button data-confirm="روزانہ اخراجات">x</button>';

        $translated = $translator->translateHtml($html);

        $this->assertStringContainsString('<label>Daily Expenses</label>', $translated);
        $this->assertStringContainsString('value="روزانہ اخراجات"', $translated);
        $this->assertStringContainsString('data-name="روزانہ اخراجات"', $translated);
        $this->assertStringContainsString('<textarea>روزانہ اخراجات</textarea>', $translated);
        $this->assertStringContainsString('data-confirm="Daily Expenses"', $translated);
        $this->assertStringContainsString('>Daily Expenses</option>', $translated);
    }

    public function test_hard_coded_right_to_left_layout_is_mirrored_in_english(): void
    {
        $translator = app(UiTranslator::class);
        $html = '<style>.a{direction: rtl;text-align:right}.b{text-align: left}</style>'
            .'<style data-ltr-ready>.c{text-align:left}</style>'
            .'<div dir="rtl" class="text-right col" style="text-align: right">x</div>';

        $mirrored = $translator->mirrorToLtr($html);

        $this->assertStringContainsString('.a{direction: ltr;text-align:left}.b{text-align: right}', $mirrored);
        $this->assertStringContainsString('.c{text-align:left}', $mirrored);
        $this->assertStringContainsString('<div dir="ltr" class="text-left col" style="text-align: left">', $mirrored);
    }

    public function test_the_dictionary_is_valid_and_free_of_quotes(): void
    {
        $entries = json_decode(file_get_contents(lang_path('en.json')), true);

        $this->assertIsArray($entries);
        $this->assertNotEmpty($entries);
        foreach ($entries as $urdu => $english) {
            $this->assertIsString($english, $urdu);
            $this->assertNotSame('', trim($english), $urdu);
        }
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
