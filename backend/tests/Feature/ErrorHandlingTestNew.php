<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorHandlingTestNew extends TestCase
{
    use RefreshDatabase;

    private function authenticateUser($emailVerified = true): array
    {
        $user = User::factory()->create([
            'email_verified_at' => $emailVerified ? now() : null,
        ]);
        $token = auth('api')->login($user);

        return [
            'user' => $user,
            'token' => $token,
            'headers' => ['Authorization' => 'Bearer '.$token],
        ];
    }

    public function test_api_handles_malformed_json()
    {
        // Test with public endpoint that doesn't require auth
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email-format',
            'password' => 'wrong',
        ]);

        // Should handle malformed data gracefully
        $response->assertStatus(422);
    }

    public function test_api_handles_missing_required_fields()
    {
        // Test with login endpoint which requires email and password
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    public function test_api_handles_very_long_input()
    {
        $auth = $this->authenticateUser();

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => str_repeat('A', 1000),
                'meaning' => str_repeat('This is a very long meaning. ', 1000),
                'category' => str_repeat('C', 1000),
                'description' => str_repeat('This is a very long description. ', 1000),
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_empty_strings()
    {
        $auth = $this->authenticateUser();

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => '',
                'meaning' => '',
                'category' => '',
                'description' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_special_characters()
    {
        $auth = $this->authenticateUser();

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => 'ĆčžšđĆČŽŠĐ',
                'meaning' => 'Croatian characters test: čćžšđĆČŽŠĐ',
                'category' => 'Testiranje',
                'description' => 'Test with special characters: @#$%^&*()[]{}|\\:";\'<>?,./',
            ]);

        $response->assertStatus(201);
    }

    public function test_api_handles_unicode_characters()
    {
        $auth = $this->authenticateUser();

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => 'API',
                'meaning' => 'Application Programming Interface 🚀',
                'category' => 'Technology',
                'description' => 'Unicode test: 🔥 💻 📱 🌍',
            ]);

        $response->assertStatus(201);
    }

    public function test_api_handles_sql_injection_attempts()
    {
        $auth = $this->authenticateUser();

        $maliciousInput = "'; DROP TABLE abbreviations; --";

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => $maliciousInput,
                'meaning' => $maliciousInput,
                'category' => $maliciousInput,
                'description' => $maliciousInput,
            ]);

        // Should either validate (422) or create safely (201)
        $this->assertTrue(in_array($response->getStatusCode(), [201, 422]));

        // Verify database is still intact
        $this->assertDatabaseHas('abbreviations', []);
    }

    public function test_api_handles_xss_attempts()
    {
        $auth = $this->authenticateUser();

        $xssInput = '<script>alert("XSS")</script>';

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => $xssInput,
                'meaning' => $xssInput,
                'category' => $xssInput,
                'description' => $xssInput,
            ]);

        // Should either validate (422) or sanitize (201)
        $this->assertTrue(in_array($response->getStatusCode(), [201, 422]));
    }

    public function test_api_handles_duplicate_abbreviations()
    {
        $auth = $this->authenticateUser();

        Abbreviation::factory()->create([
            'abbreviation' => 'API',
            'meaning' => 'Application Programming Interface',
            'category' => 'Technology',
        ]);

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => 'API',
                'meaning' => 'Another Programming Interface',
                'category' => 'Technology',
                'description' => 'Different meaning',
            ]);

        // Should either reject duplicate or allow with different meaning
        $this->assertTrue(in_array($response->getStatusCode(), [201, 422]));
    }

    public function test_api_handles_concurrent_requests()
    {
        $auth = $this->authenticateUser();

        // Simulate concurrent requests by making multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders($auth['headers'])
                ->postJson('/api/abbreviations', [
                    'abbreviation' => "TEST{$i}",
                    'meaning' => "Test meaning {$i}",
                    'category' => 'Testing',
                    'description' => "Description {$i}",
                ]);
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }
    }

    public function test_api_handles_non_existent_resource()
    {
        $response = $this->getJson('/api/abbreviations/999999');

        $response->assertStatus(404);
    }

    public function test_api_handles_invalid_vote_type()
    {
        $auth = $this->authenticateUser();
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $response = $this->withHeaders($auth['headers'])
            ->postJson("/api/abbreviations/{$abbreviation->id}/vote", [
                'type' => 'invalid_vote_type',
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_voting_on_non_existent_abbreviation()
    {
        $auth = $this->authenticateUser();

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations/999999/vote', [
                'type' => 'up',
            ]);

        $response->assertStatus(404);
    }

    public function test_api_handles_empty_comment()
    {
        $auth = $this->authenticateUser();
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $response = $this->withHeaders($auth['headers'])
            ->postJson("/api/abbreviations/{$abbreviation->id}/comments", [
                'content' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_very_long_comment()
    {
        $auth = $this->authenticateUser();
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $response = $this->withHeaders($auth['headers'])
            ->postJson("/api/abbreviations/{$abbreviation->id}/comments", [
                'content' => str_repeat('This is a very long comment. ', 1000),
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_invalid_pagination_params()
    {
        $response = $this->getJson('/api/abbreviations?page=-1&per_page=abc');

        $response->assertStatus(200); // Should handle gracefully with defaults
    }

    public function test_api_handles_invalid_sort_params()
    {
        $response = $this->getJson('/api/abbreviations?sort=invalid_column&order=invalid_order');

        $response->assertStatus(200); // Should handle gracefully with defaults
    }

    public function test_api_handles_invalid_filter_params()
    {
        $response = $this->getJson('/api/abbreviations?category=<script>alert(1)</script>');

        $response->assertStatus(200); // Should handle gracefully
    }

    public function test_unauthorized_access_to_protected_endpoints()
    {
        $response = $this->postJson('/api/abbreviations', [
            'abbreviation' => 'TEST',
            'meaning' => 'Test',
            'category' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_unverified_user_access_to_protected_endpoints()
    {
        $auth = $this->authenticateUser(false); // Unverified user

        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => 'TEST',
                'meaning' => 'Test',
                'category' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_api_handles_database_constraint_violations()
    {
        $auth = $this->authenticateUser();

        // Try to create abbreviation with null required field (should be caught by validation first)
        $response = $this->withHeaders($auth['headers'])
            ->postJson('/api/abbreviations', [
                'abbreviation' => null,
                'meaning' => 'Test meaning',
                'category' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    public function test_api_handles_malformed_multipart_data()
    {
        $auth = $this->authenticateUser();

        // Try to send multipart data to JSON endpoint
        $response = $this->withHeaders($auth['headers'])
            ->post('/api/abbreviations', [
                'abbreviation' => 'TEST',
                'meaning' => 'Test',
                'category' => 'Test',
            ], [
                'Content-Type' => 'multipart/form-data',
            ]);

        // Should handle gracefully - either 201 or 422
        $this->assertTrue(in_array($response->getStatusCode(), [201, 422, 415]));
    }

    public function test_api_rate_limiting_simulation()
    {
        // Simulate rapid requests (note: actual rate limiting may not be enabled in tests)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/abbreviations');
            $this->assertTrue(in_array($response->getStatusCode(), [200, 429]));
        }
    }
}
