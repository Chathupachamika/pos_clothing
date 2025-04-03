<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Supplier;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_can_list_suppliers()
    {
        Supplier::factory()->count(3)->create();

        $response = $this->getJson('/api/suppliers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'contact'
                    ]
                ]);
    }

    public function test_can_create_supplier()
    {
        $supplierData = [
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'contact' => '1234567890'
        ];

        $response = $this->postJson('/api/suppliers', $supplierData);

        $response->assertStatus(201)
                ->assertJsonStructure(['id', 'name', 'email', 'contact']);
    }

    public function test_can_show_supplier_products()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->getJson("/api/suppliers/{$supplier->id}/products");

        $response->assertStatus(200);
    }
}
