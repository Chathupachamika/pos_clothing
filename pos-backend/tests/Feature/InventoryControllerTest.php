<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_inventory()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'quantity',
                    'location',
                    'status',
                    'total_value',
                    'total_profit'
                ]
            ]);
    }

    public function test_can_show_low_stock_items()
    {
        Product::factory()->create(['quantity' => 10]);

        $response = $this->getJson('/api/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'supplier_id',
                    'supplier_name',
                    'product_id',
                    'quantity'
                ]
            ]);
    }

    public function test_can_export_inventory_data()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/inventory/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'summary' => [
                    'total_value',
                    'total_profit'
                ]
            ]);
    }

    public function test_can_update_inventory_status()
    {
        $product = Product::factory()->create(['quantity' => 5]);

        $updateData = [
            'quantity' => 25,
            'location' => 'Warehouse A',
            'status' => 'In Stock',
            'restock_date_time' => now()->toDateString()
        ];

        $response = $this->putJson("/api/inventory/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'quantity',
                'location',
                'status'
            ]);
    }
}
