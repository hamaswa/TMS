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
}
