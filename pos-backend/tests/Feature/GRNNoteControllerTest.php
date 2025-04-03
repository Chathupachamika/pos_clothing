<?php

namespace Tests\Feature;

use App\Models\GRNNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Admin;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GRNNoteControllerTest extends TestCase
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
        $this->product = Product::factory()->create();
    }

    public function test_can_list_grn_notes()
    {
        $response = $this->getJson('/api/grn-notes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data'
            ]);
    }

    public function test_can_create_grn_note()
    {
        $grnData = [
            'grn_number' => 'GRN-2023-00001',
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'admin_id' => $this->admin->id,
            'price' => 100.00,
            'product_details' => [
                'name' => 'Test Product',
                'description' => 'Test Description'
            ],
            'received_date' => now(),
            'previous_quantity' => 10,
            'new_quantity' => 20,
            'adjusted_quantity' => 10,
            'adjustment_type' => 'addition'
        ];

        $response = $this->postJson('/api/grn-notes', $grnData);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');
    }

    public function test_can_show_grn_note()
    {
        $grnNote = GRNNote::factory()->create();

        $response = $this->getJson("/api/grn-notes/{$grnNote->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data'
            ]);
    }
}
