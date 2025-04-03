<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Customer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::factory()->create();
    }

    public function test_can_list_feedback()
    {
        Feedback::factory()->count(3)->create();

        $response = $this->getJson('/api/feedback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'total'
            ]);
    }

    public function test_can_create_feedback()
    {
        $feedbackData = [
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'comment' => 'Great service!'
        ];

        $response = $this->postJson('/api/feedback', $feedbackData);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Feedback submitted successfully']);
    }

    public function test_can_get_average_rating()
    {
        Feedback::factory()->count(3)->create(['rating' => 4]);

        $response = $this->getJson('/api/feedback/average-rating');

        $response->assertStatus(200)
            ->assertJsonStructure(['average_rating']);
    }

    public function test_can_get_positive_feedback()
    {
        Feedback::factory()->create(['rating' => 5]);

        $response = $this->getJson('/api/feedback/positive');

        $response->assertStatus(200);
    }
}
