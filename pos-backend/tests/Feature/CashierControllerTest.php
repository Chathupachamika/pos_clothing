<?php

namespace Tests\Feature;

use App\Models\Cashier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_list_cashiers()
    {
        Cashier::factory()->count(3)->create();

        $response = $this->getJson('/api/cashiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'image_url'
                    ]
                ]
            ]);
    }

    public function test_can_create_cashier()
    {
        $image = UploadedFile::fake()->image('avatar.jpg');
        
        $cashierData = [
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => 'password123',
            'image' => $image
        ];

        $response = $this->postJson('/api/cashiers', $cashierData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'image_url'
                ]
            ]);
    }

    public function test_can_update_cashier_password()
    {
        $cashier = Cashier::factory()->create([
            'password' => bcrypt('oldpassword')
        ]);

        $updateData = [
            'id' => $cashier->id,
            'email' => $cashier->email,
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123'
        ];

        $response = $this->postJson('/api/cashiers/update-password', $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Password updated successfully']);
    }

    public function test_can_delete_cashier()
    {
        $cashier = Cashier::factory()->create();

        $response = $this->deleteJson("/api/cashiers/{$cashier->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Deleted the Cashier']);
    }
}
