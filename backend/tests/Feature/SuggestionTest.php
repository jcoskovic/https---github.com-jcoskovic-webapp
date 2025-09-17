<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_generate_suggestions_with_valid_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/generate', [
                'text' => 'API is commonly used',
                'category' => 'Technology',
                'limit' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'abbreviation',
                        'meaning',
                        'category',
                        'confidence_score',
                        'source',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_generate_suggestions_validation_fails()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/generate', [
                'text' => '', // Invalid: empty text
                'limit' => 100, // Invalid: too high limit
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ])
            ->assertJsonStructure([
                'errors',
            ]);
    }

    public function test_generate_suggestions_with_existing_abbreviation()
    {
        // Create existing abbreviation
        Abbreviation::factory()->create([
            'abbreviation' => 'API',
            'meaning' => 'Application Programming Interface',
            'category' => 'Technology',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/generate', [
                'text' => 'API documentation',
                'limit' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('API', $data[0]['abbreviation']);
        $this->assertEquals('database', $data[0]['source']);
        $this->assertEquals(0.95, $data[0]['confidence_score']);
    }

    public function test_generate_suggestions_with_no_matches()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/generate', [
                'text' => 'some random text without abbreviations',
                'limit' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('generated', $data[0]['source']);
    }

    public function test_get_by_category()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/suggestions/category/Technology?limit=10');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    }

    public function test_store_suggestion()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions', [
                'abbreviation' => 'API',
                'meaning' => 'Application Programming Interface',
                'category' => 'Technology',
                'description' => 'Software interface',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'abbreviation',
                    'meaning',
                    'category',
                    'description',
                    'confidence_score',
                    'source',
                    'status',
                    'created_at',
                ],
            ]);
    }

    public function test_store_suggestion_validation_fails()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions', [
                'abbreviation' => '', // Invalid: empty
                'meaning' => str_repeat('a', 201), // Invalid: too long
                'category' => str_repeat('a', 101), // Invalid: too long
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ]);
    }

    public function test_validate_suggestion()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/validate', [
                'abbreviation' => 'API',
                'meaning' => 'Application Programming Interface',
                'category' => 'Technology',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'isValid' => true,
                'issues' => [],
            ]);
    }

    public function test_validate_suggestion_fails()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/suggestions/validate', [
                'abbreviation' => '', // Invalid
                'meaning' => '', // Invalid
                'category' => '', // Invalid
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'isValid' => false,
            ])
            ->assertJsonStructure([
                'issues',
            ]);
    }

    public function test_get_statistics()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/suggestions/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'pending',
                'approved',
                'rejected',
                'byCategory',
            ]);
    }
}
