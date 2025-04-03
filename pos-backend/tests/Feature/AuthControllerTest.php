<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'user',
                    'token',
                    'is-admin',
                    'message'
                ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'user',
                    'token',
                    'is-admin',
                    'message'
                ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'You are LogOut'
                ]);
    }

    public function test_user_can_request_password_reset()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'email'
                ]);
    }
}
