<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_promote_user_to_admin()
    {
        $user = User::factory()->create();

        $response = $this->postJson("/api/admin/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User promoted to admin']);
    }

    public function test_cannot_promote_already_admin_user()
    {
        $user = User::factory()->create();
        Admin::create(['user_id' => $user->id]);

        $response = $this->postJson("/api/admin/{$user->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'User is already an admin']);
    }

    public function test_can_remove_admin_role()
    {
        $user = User::factory()->create();
        Admin::create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/admin/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User removed from admin']);
    }

    public function test_cannot_remove_admin_role_from_non_admin()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/admin/{$user->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'User is not an admin']);
    }
}
