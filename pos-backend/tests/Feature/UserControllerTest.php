<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $role;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'admin']);
    }

    public function test_can_assign_role()
    {
        $response = $this->postJson('/api/users/assign-role', [
            'user_id' => $this->user->id,
            'role' => 'admin'
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Role assigned successfully.'
                ]);
    }

    public function test_can_create_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'User created successfully.'
                ]);
    }

    public function test_can_check_admin_status()
    {
        $response = $this->getJson('/api/users/is-admin');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'is_admin',
                    'message'
                ]);
    }
}
