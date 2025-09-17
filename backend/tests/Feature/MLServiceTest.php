<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use App\Services\MLServiceClient;
use App\Services\RecommendationService;
use App\Services\TrendingService;
use App\Services\UserDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MLServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock HTTP responses to prevent actual API calls
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'data' => [],
            ], 200),
        ]);
    }

    public function test_ml_health_endpoint()
    {
        // Mock MLServiceClient to return true for isAvailable
        $this->mock(MLServiceClient::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        $response = $this->getJson('/api/ml/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ])->assertJson([
                    'status' => 'success',
                ]);
    }

    public function test_ml_health_endpoint_when_service_unavailable()
    {
        // Mock MLServiceClient to return false for isAvailable
        $this->mock(MLServiceClient::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
        });

        $response = $this->getJson('/api/ml/health');

        $response->assertStatus(503)
            ->assertJsonStructure([
                'status',
                'message',
            ])->assertJson([
                    'status' => 'error',
                ]);
    }

    public function test_ml_trending_endpoint()
    {
        // Create some test data
        $user = User::factory()->create();
        Abbreviation::factory()->count(5)->create(['status' => 'approved']);

        // Mock TrendingService
        $mockTrending = [
            ['id' => 1, 'abbreviation' => 'API', 'score' => 95.5],
            ['id' => 2, 'abbreviation' => 'URL', 'score' => 89.2],
        ];

        $this->mock(TrendingService::class, function ($mock) use ($mockTrending) {
            $mock->shouldReceive('calculateTrendingAbbreviations')
                ->with(10)
                ->andReturn($mockTrending);
        });

        $response = $this->getJson('/api/ml/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ])->assertJson([
                    'status' => 'success',
                    'data' => $mockTrending,
                ]);
    }

    public function test_ml_recommendations_for_abbreviation()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        // Mock MLServiceClient with actual Response object
        $this->mock(MLServiceClient::class, function ($mock) {
            $response = new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'status' => 'success',
                    'recommendations' => [1, 2, 3],
                ]))
            );
            $mock->shouldReceive('getRecommendations')
                ->andReturn($response);
        });

        $response = $this->getJson("/api/ml/recommendations/{$abbreviation->id}?user_id={$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ])->assertJson([
                    'status' => 'success',
                ]);
    }

    public function test_ml_personalized_recommendations()
    {
        $user = User::factory()->create();

        // Mock RecommendationService
        $mockRecommendations = [
            'status' => 'success',
            'data' => [1, 2, 3, 4, 5],
        ];

        $this->mock(RecommendationService::class, function ($mock) use ($mockRecommendations) {
            $mock->shouldReceive('getPersonalizedRecommendations')
                ->andReturn($mockRecommendations);
        });

        $response = $this->getJson("/api/ml/recommendations/personalized/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_ml_user_data_endpoint()
    {
        $user = User::factory()->create();

        // Mock UserDataService
        $mockUserData = [
            'user_id' => $user->id,
            'department' => 'IT',
            'interactions' => [],
            'preferences' => [],
        ];

        $this->mock(UserDataService::class, function ($mock) use ($mockUserData) {
            $mock->shouldReceive('getUserData')
                ->andReturn($mockUserData);
        });

        $response = $this->getJson("/api/ml/user-data/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_ml_train_model()
    {
        // Mock MLServiceClient with actual Response object
        $this->mock(MLServiceClient::class, function ($mock) {
            $response = new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'status' => 'success',
                    'message' => 'Training completed',
                ]))
            );
            $mock->shouldReceive('updateTrainingData')
                ->andReturn($response);
        });

        $response = $this->postJson('/api/ml/train');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ])->assertJson([
                    'status' => 'success',
                ]);
    }

    public function test_ml_service_error_handling()
    {
        // Mock MLServiceClient to throw an exception
        $this->mock(MLServiceClient::class, function ($mock) {
            $mock->shouldReceive('getRecommendations')
                ->andThrow(new \Exception('Service unavailable'));
        });

        // Test with a specific abbreviation ID since that's what the route expects
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);
        $response = $this->getJson("/api/ml/recommendations/{$abbreviation->id}?limit=5");

        $response->assertStatus(503)
            ->assertJsonStructure([
                'status',
                'message',
                'error',
            ])->assertJson([
                    'status' => 'error',
                ]);
    }

    public function test_ml_user_data_not_found()
    {
        // Mock UserDataService to return null (user not found)
        $this->mock(UserDataService::class, function ($mock) {
            $mock->shouldReceive('getUserData')
                ->andReturn(null);
        });

        $response = $this->getJson('/api/ml/user-data/999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status',
                'message',
            ])->assertJson([
                    'status' => 'error',
                    'message' => 'User not found',
                ]);
    }
}
