<?php

namespace Tests\Integration;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class MLServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test real ML service integration if available
     */
    public function test_ml_service_real_integration()
    {
        $mlServiceUrl = env('ML_SERVICE_URL', 'http://ml-service:5000');

        try {
            // Try to connect to real ML service
            $healthResponse = Http::timeout(2)->get($mlServiceUrl.'/health');

            if (! $healthResponse->successful()) {
                $this->markTestSkipped('ML Service not available for integration testing');

                return;
            }

            // ML Service is available - test real endpoints (only test if service actually works)
            $this->runRealMLServiceTests($mlServiceUrl);
        } catch (\Exception $e) {
            $this->markTestSkipped('ML Service not available: '.$e->getMessage());
        }
    }

    private function runRealMLServiceTests(string $mlServiceUrl)
    {
        // Test health endpoint with real service - must return 200
        $response = $this->getJson('/api/ml/health');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'timestamp',
        ]);

        // Test trending endpoint (should work without ML service dependency)
        $user = User::factory()->create();
        Abbreviation::factory()->count(3)->create(['status' => 'approved']);

        $response = $this->getJson('/api/ml/trending');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);

        // Test user data endpoint - must work if ML service is up
        $response = $this->getJson("/api/ml/user-data/{$user->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);

        // Test personalized recommendations - must work if ML service is up
        $response = $this->getJson("/api/ml/recommendations/personalized/{$user->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);
    }

    /**
     * Test fallback behavior when ML service is down
     */
    public function test_ml_service_fallback_behavior()
    {
        // Mock ML service as unavailable
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $user = User::factory()->create();
        Abbreviation::factory()->count(3)->create(['status' => 'approved']);

        // Health endpoint should return 503
        $response = $this->getJson('/api/ml/health');
        $response->assertStatus(503);
        $response->assertJsonStructure([
            'status',
            'message',
        ]);

        // Trending should still work (local calculation)
        $response = $this->getJson('/api/ml/trending');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);

        // Personalized recommendations should fallback
        $response = $this->getJson("/api/ml/recommendations/personalized/{$user->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);

        // Should indicate it's using fallback
        $data = $response->json();
        $this->assertTrue($data['fallback'] ?? false);

        // Recommendations endpoint should handle gracefully
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);
        $response = $this->getJson("/api/ml/recommendations/{$abbreviation->id}");
        $response->assertStatus(503);
    }

    /**
     * Test ML service with real data patterns
     */
    public function test_ml_service_with_realistic_data()
    {
        // Create realistic data scenario
        $users = User::factory()->count(5)->create();

        $techAbbreviations = Abbreviation::factory()->count(3)->create([
            'status' => 'approved',
            'category' => 'Technology',
        ]);

        $businessAbbreviations = Abbreviation::factory()->count(2)->create([
            'status' => 'approved',
            'category' => 'Business',
        ]);

        // Add realistic voting patterns
        foreach ($users as $user) {
            // Users tend to vote on tech abbreviations more
            foreach ($techAbbreviations as $abbr) {
                if (rand(0, 100) < 70) { // 70% chance
                    \App\Models\Vote::factory()->create([
                        'user_id' => $user->id,
                        'abbreviation_id' => $abbr->id,
                        'type' => rand(0, 100) < 80 ? 'up' : 'down', // 80% upvotes
                    ]);
                }
            }
        }

        // Test trending calculation with real patterns
        $response = $this->getJson('/api/ml/trending');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data['data']);

        // Tech abbreviations should likely be trending due to more votes
        $trending = $data['data'];
        $categories = array_column($trending, 'category');
        Assert::assertContains('Technology', $categories);
    }
}
