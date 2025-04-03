<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Customer_contact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_customers()
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'total'
            ]);
    }

    public function test_can_create_customer()
    {
        $customerData = [
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'contact_number' => '1234567890'
        ];

        $response = $this->postJson('/api/customers', $customerData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Saved a new Customer']);
    }

    public function test_can_update_customer()
    {
        $customer = Customer::factory()->create();
        
        $updateData = [
            'name' => 'Updated Customer',
            'email' => 'updated@test.com',
            'contact_number' => '9876543210'
        ];

        $response = $this->putJson("/api/customers/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Updated Customer']);
    }

    public function test_can_delete_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Deleted Customer']);
    }
}
