<?php

namespace Tests\Feature;

use App\Models\Asset;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_assets()
    {
        Asset::factory()->count(3)->create();

        $response = $this->getJson('/api/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'type', 'value', 'location']
            ]);
    }

    public function test_can_create_asset()
    {
        $assetData = [
            'name' => 'Test Asset',
            'type' => 'Equipment',
            'value' => 1000,
            'location' => 'Warehouse A'
        ];

        $response = $this->postJson('/api/assets', $assetData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Asset']);
    }

    public function test_can_update_asset()
    {
        $asset = Asset::factory()->create();
        
        $updateData = [
            'name' => 'Updated Asset',
            'value' => 2000
        ];

        $response = $this->putJson("/api/assets/{$asset->id}", $updateData);

        $response->assertStatus(200);
    }

    public function test_can_delete_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/assets/{$asset->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Asset deleted successfully']);
    }
}
