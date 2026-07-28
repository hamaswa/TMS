<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_public_marketplace_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('storefront.public.index')
            ->assertSee(route('login'), false);
    }

    public function test_login_page_uses_the_branded_tms_experience_and_compiled_assets(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('ہر سلائی۔')
            ->assertSee('لاگ اِن کریں')
            ->assertSee('/build/assets/app-', false)
            ->assertDontSee('public/css/app.css', false);
    }

    public function test_login_and_public_marketplace_expose_accessible_mobile_controls(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('.auth-link{min-height:44px', false)
            ->assertSee('.market-home{min-height:44px', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('.nav-link{min-height:44px', false)
            ->assertSee('.locale-switch a{min-width:44px;min-height:44px', false);
    }

    public function test_password_reset_request_is_clear_and_urdu_for_client_users(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSeeText('پاس ورڈ دوبارہ بنائیں')
            ->assertSeeText('اگر دکان کے لیے ای میل سروس فعال ہے')
            ->assertSeeText('پاس ورڈ تبدیل کرنے کا لنک بھیجیں')
            ->assertDontSeeText('Reset Password')
            ->assertDontSeeText('E-Mail Address');
    }

    public function test_core_legacy_workspaces_have_one_semantic_page_heading(): void
    {
        foreach ([
            'sale/create.blade.php',
            'sale/edit.blade.php',
            'sale/list.blade.php',
            'sale/show.blade.php',
            'tailor/add.blade.php',
            'tailor/edit.blade.php',
            'tailor/list.blade.php',
            'Expenses/index.blade.php',
            'Expenses/create.blade.php',
            'Expenses/expense_edit.blade.php',
            'DailyExpenses/index.blade.php',
            'DailyExpenses/create.blade.php',
            'DailyExpenses/edit.blade.php',
        ] as $view) {
            $contents = file_get_contents(resource_path('views/'.$view));

            $this->assertSame(1, preg_match_all('/<h1\b/i', $contents), $view);
        }
    }

    public function test_client_layout_renders_page_specific_style_stacks(): void
    {
        $header = file_get_contents(resource_path('views/inc/header.blade.php'));

        $this->assertStringContainsString("@stack('styles')", $header);
        $this->assertStringContainsString("@stack('scripts')", file_get_contents(resource_path('views/inc/footer.blade.php')));
    }
}
