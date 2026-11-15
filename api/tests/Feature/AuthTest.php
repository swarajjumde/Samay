<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_gets_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Riya',
            'email' => 'riya@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'riya@example.com')
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonStructure(['token']);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'role' => 'admin',
        ])->assertCreated();

        $this->assertSame('customer', User::where('email', 'sneaky@example.com')->value('role'));
    }

    public function test_login_works_with_right_password_only(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-pass'])
            ->assertStatus(422);

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secret-pass-1'])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_private_routes_need_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/bookings')->assertUnauthorized();
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }
}
