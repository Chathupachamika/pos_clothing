<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $supplier;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = Admin::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create([
            'supplier_id' => $this->supplier->id,
            'admin_id' => $this->admin->id
        ]);
    }

    public function test_can_list_products()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'quantity',
                            'status'
                        ]
                    ]
                ]);
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'quantity' => 10,
            'supplier_id' => $this->supplier->id,
            'admin_id' => $this->admin->id
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Test Product',
                    'price' => 99.99
                ]);
    }

    public function test_can_update_product()
    {
        $updateData = [
            'name' => 'Updated Product',
            'price' => 149.99
        ];

        $response = $this->putJson("/api/products/{$this->product->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name' => 'Updated Product',
                    'price' => 149.99
                ]);
    }

    public function test_can_delete_product()
    {
        $response = $this->deleteJson("/api/products/{$this->product->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
    }
}
