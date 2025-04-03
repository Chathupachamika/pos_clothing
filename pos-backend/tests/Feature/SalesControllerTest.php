<?php

namespace Tests\Feature;

use App\Models\Sales;
use App\Models\Product;
use App\Models\Customer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $product;
    private $customer;
    private $sales;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->product = Product::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->sales = Sales::factory()->create([
            'customer_id' => $this->customer->id
        ]);
    }

    public function test_can_list_sales()
    {
        $response = $this->getJson('/api/sales');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        '*' => [
                            'id',
                            'customer_id',
                            'amount',
                            'status'
                        ]
                    ]
                ]);
    }

    public function test_can_create_sale()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'discount' => 10,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_can_get_sales_report()
    {
        $response = $this->getJson('/api/sales/report/today');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_suppliers'
                ]);
    }

    public function test_can_get_sales_by_date_range()
    {
        $response = $this->getJson('/api/sales/reports?from=2023-01-01&to=2023-12-31');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'end'
                ]);
    }
}
