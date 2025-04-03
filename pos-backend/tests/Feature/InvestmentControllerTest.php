<?php

namespace Tests\Feature;

use App\Models\Investment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvestmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_investments()
    {
        Investment::factory()->count(3)->create();

        $response = $this->getJson('/api/investments');

        $response->assertStatus(200)
                ->assertJsonStructure(['*' => ['id', 'investor_name', 'amount']]);
    }

    public function test_can_create_investment()
    {
        $investmentData = [
            'investor_name' => 'Test Investor',
            'amount' => 10000,
            'investment_date' => now()->toDateString(),
            'description' => 'Test investment'
        ];

        $response = $this->postJson('/api/investments', $investmentData);

        $response->assertStatus(201);
    }

    public function test_can_delete_investment()
    {
        $investment = Investment::factory()->create();

        $response = $this->deleteJson("/api/investments/{$investment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('investments', ['id' => $investment->id]);
    }
}
