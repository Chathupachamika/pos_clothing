<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductImagesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_upload_product_image()
    {
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson('/api/product-images', [
            'product_id' => $product->id,
            'images' => $file
        ]);

        $response->assertStatus(201)
                ->assertJsonPath('status', 'success');

        $this->assertTrue(Storage::disk('public')->exists('products/' . $file->hashName()));
    }

    public function test_can_get_product_images()
    {
        $product = Product::factory()->create();
        ProductImages::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/product-images');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data'
                ]);
    }

    public function test_can_delete_product_image()
    {
        $image = ProductImages::factory()->create();

        $response = $this->deleteJson("/api/product-images/{$image->id}");

        $response->assertStatus(200)
                ->assertJsonPath('status', 'success');
    }
}
