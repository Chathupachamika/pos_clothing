<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_roles()
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
                ->assertJsonCount(2);
    }

    public function test_can_create_role_with_permissions()
    {
        $roleData = [
            'name' => 'editor',
            'permission' => ['edit articles', 'publish articles']
        ];

        $response = $this->postJson('/api/roles', $roleData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'role' => ['id', 'name']
                ]);
    }

    public function test_can_delete_role()
    {
        $role = Role::create(['name' => 'temp']);

        $response = $this->postJson('/api/roles/destroy', [
            'role' => 'temp'
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Role deleted successfully.']);
    }
}
