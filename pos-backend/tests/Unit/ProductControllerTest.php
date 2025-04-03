<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Models\Admin;
use App\Http\Controllers\ProductController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Exception;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private ProductController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProductController();
    }

    public function test_index_returns_all_products()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->controller->index();

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertNotEmpty($response->getData()->data);
    }

    public function test_show_returns_specific_product()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->controller->show($product->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals($product->id, $response->getData()->data->id);
    }

    public function test_store_creates_new_product()
    {
        // Arrange
        $supplier = Supplier::factory()->create();
        $inventory = Inventory::factory()->create();
        $admin = Admin::factory()->create();

        $request = new Request([
            'name' => 'Test Product',
            'supplier_id' => $supplier->id,
            'seller_price' => 100,
            'discount' => 0,
            'price' => 150,
            'brand_name' => 'Test Brand',
            'tax' => 10,
            'size' => 'M',
            'color' => 'Red',
            'description' => 'Test Description',
            'bar_code' => '123456789',
            'inventory_id' => $inventory->id,
            'admin_id' => $admin->id
        ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(201, $response->status());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertEquals('Test Product', $response->getData()->data->name);
    }

    public function test_update_modifies_existing_product()
    {
        // Arrange
        $product = Product::factory()->create();
        $request = new Request([
            'name' => 'Updated Product',
            'price' => 200
        ]);

        // Act
        $response = $this->controller->update($request, $product->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Updated Product', $response->getData()->data->name);
    }

    public function test_destroy_deletes_product()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->controller->destroy($product->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_get_by_inventory_id_returns_product()
    {
        // Arrange
        $inventory = Inventory::factory()->create();
        $product = Product::factory()->create(['inventory_id' => $inventory->id]);

        // Act
        $response = $this->controller->getByInventoryId($inventory->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals($product->id, $response->getData()->id);
    }
}
