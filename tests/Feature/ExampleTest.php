<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La raiz exige autenticacion, sin usuario responde con redireccion a login.
     */
    public function test_the_application_redirects_unauthenticated_users_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertStatus(200);
    }
}
