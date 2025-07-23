<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Feedback;

class FeedbackApiTest extends TestCase
{

    use RefreshDatabase; // Resets the database before each test

    /**
     * Test the POST /feedback endpoint with valid data.
     *
     * @return void
     */
    public function test_can_submit_feedback()
    {
        $data = [
            'name' => 'Nisit P',
            'email' => 'np@np.com',
            'message' => 'This is a test feedback message from Np.',
        ];

        $response = $this->postJson('/api/feedback', $data);

        $response->assertStatus(201)
                 ->assertJson([
                    'message' => 'Feedback Created Successfully.',
                ]);

        // Assert feedback was stored in the database
        $this->assertDatabaseHas('feedback', [
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);
    }

    /**
     * Test the POST /feedback endpoint with invalid data (missing fields).
     *
     * @return void
     */
    public function test_cannot_submit_feedback_with_invalid_data()
    {
        $invalidData = [
            'name' => '', // Missing name
             // Invalid email
            'message' => 'This is a test message here.',
        ];

        $response = $this->postJson('/api/feedback', $invalidData);

        $response->assertStatus(500) 
                  ->assertJson([ // Verify structure
                    'message' => 'An unexpected error occurred.',
                    'error' => 'The name field is required. (and 1 more error)',
                 ]); // Assert validation error for fields.
    }

    public function test_can_get_all_feedback()
    {
        // Create some feedback Entries
        Feedback::factory()->count(3)->create();

        $response = $this->getJson('/api/feedback');

        $response->assertStatus(200)
                 ->assertJson([ // Verify structure
                    'message' => 'All Feedback retrieved successfully!',
                 ])
                 ->assertJsonCount(3, 'data'); // Assert that 3 items are returned in 'data'
    }
}
