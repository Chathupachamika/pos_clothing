<?php

namespace Tests\Feature;

use App\Models\ReturnItem;
use App\Models\SalesReturnItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnItemsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $returnItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->returnItem = ReturnItem::factory()->create();
    }

    public function test_can_list_return_items()
    {
        $response = $this->getJson('/api/return-items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'reason',
                        'quantity',
                        'product_id',
                        'sales_id',
                        'returned_at'
                    ]
                ]
            ]);
    }

    public function test_can_show_return_item()
    {
        $response = $this->getJson("/api/return-items/{$this->returnItem->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'reason',
                'quantity',
                'product_id'
            ]);
    }

    public function test_returns_404_for_nonexistent_return_item()
    {
        $response = $this->getJson('/api/return-items/999999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Return item not found']);
    }
}
