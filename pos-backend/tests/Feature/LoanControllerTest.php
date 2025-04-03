<?php

namespace Tests\Feature;

use App\Models\Loan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_loans()
    {
        Loan::factory()->count(3)->create();

        $response = $this->getJson('/api/loans');

        $response->assertStatus(200)
                ->assertJsonStructure(['*' => ['id', 'borrower_name', 'amount']]);
    }

    public function test_can_create_loan()
    {
        $loanData = [
            'borrower_name' => 'Test Borrower',
            'amount' => 1000,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addMonths(1)->toDateString(),
            'status' => 'pending',
            'description' => 'Test loan'
        ];

        $response = $this->postJson('/api/loans', $loanData);

        $response->assertStatus(201);
    }

    public function test_can_update_loan()
    {
        $loan = Loan::factory()->create();
        
        $updateData = [
            'status' => 'paid',
            'amount' => 2000,
        ];

        $response = $this->putJson("/api/loans/{$loan->id}", $updateData);

        $response->assertStatus(200);
    }
}
