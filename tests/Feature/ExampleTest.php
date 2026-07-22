<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
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
