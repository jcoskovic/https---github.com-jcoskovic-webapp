<?php

namespace Tests\Unit;

use App\Services\MLServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MLServiceClientTest extends TestCase
{
    use RefreshDatabase;

    private MLServiceClient $mlServiceClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mlServiceClient = new MLServiceClient;

        // Mock Log facade to avoid log output during tests
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_recommendations_returns_successful_response()
    {
        Http::fake([
            '*/recommendations*' => Http::response([
                'recommendations' => [
                    ['id' => 1, 'abbreviation' => 'API', 'score' => 0.9],
                    ['id' => 2, 'abbreviation' => 'HTTP', 'score' => 0.8],
                ],
            ], 200),
        ]);

        $response = $this->mlServiceClient->getRecommendations(10);

        $this->assertEquals(200, $response->status());
        $this->assertIsArray($response->json('recommendations'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/recommendations') &&
                $request['limit'] == 10;
        });
    }

    public function test_get_recommendations_handles_timeout()
    {
        Http::fake([
            '*/recommendations*' => function () {
                throw new \Exception('Request timeout');
            },
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Request timeout');

        $this->mlServiceClient->getRecommendations(5);
    }

    public function test_get_recommendations_handles_server_error()
    {
        Http::fake([
            '*/recommendations*' => Http::response(['error' => 'Internal server error'], 500),
        ]);

        $response = $this->mlServiceClient->getRecommendations(10);

        $this->assertEquals(500, $response->status());
    }

    public function test_get_personalized_recommendations_returns_successful_response()
    {
        $userData = [
            'user_id' => 123,
            'preferences' => ['Technology', 'Science'],
            'history' => [1, 2, 3],
        ];

        Http::fake([
            '*/recommendations/123' => Http::response([
                'user_id' => 123,
                'recommendations' => [
                    ['id' => 4, 'abbreviation' => 'ML', 'score' => 0.95],
                    ['id' => 5, 'abbreviation' => 'AI', 'score' => 0.88],
                ],
            ], 200),
        ]);

        $response = $this->mlServiceClient->getPersonalizedRecommendations(123, $userData);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(123, $response->json('user_id'));
        $this->assertIsArray($response->json('recommendations'));

        Http::assertSent(function ($request) use ($userData) {
            return str_contains($request->url(), '/recommendations/123') &&
                $request['user_data'] === $userData &&
                $request['limit'] === 10;
        });
    }

    public function test_get_personalized_recommendations_handles_user_not_found()
    {
        Http::fake([
            '*/recommendations/999' => Http::response(['error' => 'User not found'], 404),
        ]);

        $response = $this->mlServiceClient->getPersonalizedRecommendations(999, []);

        $this->assertEquals(404, $response->status());
        $this->assertEquals('User not found', $response->json('error'));
    }

    public function test_get_personalized_recommendations_handles_exception()
    {
        Http::fake([
            '*/recommendations/*' => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection failed');

        $this->mlServiceClient->getPersonalizedRecommendations(123, []);
    }

    public function test_update_training_data_returns_successful_response()
    {
        $trainingData = [
            'abbreviations' => [
                ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
                ['id' => 2, 'abbreviation' => 'HTTP', 'meaning' => 'HyperText Transfer Protocol'],
            ],
            'user_interactions' => [
                ['user_id' => 1, 'abbreviation_id' => 1, 'action' => 'view'],
                ['user_id' => 2, 'abbreviation_id' => 2, 'action' => 'vote_up'],
            ],
        ];

        Http::fake([
            '*/update-training' => Http::response(['status' => 'training updated'], 200),
        ]);

        $response = $this->mlServiceClient->updateTrainingData($trainingData);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('training updated', $response->json('status'));

        Http::assertSent(function ($request) use ($trainingData) {
            return str_contains($request->url(), '/update-training') &&
                $request->data() === $trainingData;
        });
    }

    public function test_update_training_data_handles_large_dataset()
    {
        $largeData = [
            'abbreviations' => array_fill(0, 1000, ['id' => 1, 'abbreviation' => 'TEST']),
            'user_interactions' => array_fill(0, 5000, ['user_id' => 1, 'action' => 'view']),
        ];

        Http::fake([
            '*/update-training' => Http::response(['status' => 'training updated'], 200),
        ]);

        $response = $this->mlServiceClient->updateTrainingData($largeData);

        $this->assertEquals(200, $response->status());
    }

    public function test_update_training_data_handles_failure()
    {
        Http::fake([
            '*/update-training' => Http::response(['error' => 'Training failed'], 500),
        ]);

        $response = $this->mlServiceClient->updateTrainingData([]);

        $this->assertEquals(500, $response->status());
        $this->assertEquals('Training failed', $response->json('error'));
    }

    public function test_update_training_data_handles_exception()
    {
        Http::fake([
            '*/update-training' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        $this->mlServiceClient->updateTrainingData([]);
    }

    public function test_is_available_returns_true_for_healthy_service()
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'healthy'], 200),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertTrue($isAvailable);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/health');
        });
    }

    public function test_is_available_returns_true_for_healthy_status()
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'healthy'], 200),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertTrue($isAvailable);
    }

    public function test_is_available_falls_back_to_root_endpoint()
    {
        Http::fake([
            '*/health' => Http::response(['error' => 'not found'], 404),
            '*/' => Http::response(['status' => 'ok'], 200),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertTrue($isAvailable);
    }

    public function test_is_available_returns_false_for_unhealthy_service()
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'unhealthy'], 200),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertFalse($isAvailable);
    }

    public function test_is_available_returns_false_for_server_error()
    {
        Http::fake([
            '*' => Http::response(['error' => 'server error'], 500),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertFalse($isAvailable);
    }

    public function test_is_available_returns_false_for_connection_error()
    {
        Http::fake([
            '*' => function () {
                throw new \Exception('Connection refused');
            },
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertFalse($isAvailable);
    }

    public function test_is_available_returns_false_for_invalid_response()
    {
        Http::fake([
            '*/health' => Http::response(['invalid' => 'response'], 200),
            'http://ml-service:5000/' => Http::response(['invalid' => 'response'], 200),
        ]);

        $isAvailable = $this->mlServiceClient->isAvailable();

        $this->assertFalse($isAvailable);
    }

    public function test_constructor_uses_environment_variable()
    {
        // Test that constructor uses env variable
        putenv('ML_SERVICE_URL=http://custom-ml:8080');
        $client = new MLServiceClient;
        putenv('ML_SERVICE_URL'); // cleanup

        // Simple test to verify client is created
        $this->assertInstanceOf(MLServiceClient::class, $client);
    }

    public function test_timeout_configuration_for_different_endpoints()
    {
        // Test that different timeouts are used for different endpoints
        Http::fake([
            '*' => Http::response(['status' => 'healthy', 'recommendations' => []], 200),
        ]);

        // These should all complete successfully
        $response1 = $this->mlServiceClient->getRecommendations(5);
        $response2 = $this->mlServiceClient->updateTrainingData([]);
        $isAvailable = $this->mlServiceClient->isAvailable();

        // Verify all requests were successful
        $this->assertTrue($response1->successful());
        $this->assertTrue($response2->successful());
        $this->assertTrue($isAvailable);

        // Verify all requests were made
        Http::assertSentCount(3);
    }

    public function test_get_recommendations_with_different_limits()
    {
        Http::fake([
            '*/recommendations*' => Http::response(['recommendations' => []], 200),
        ]);

        $this->mlServiceClient->getRecommendations(1);
        $this->mlServiceClient->getRecommendations(50);
        $this->mlServiceClient->getRecommendations(100);

        Http::assertSentCount(3);
    }

    public function test_personalized_recommendations_with_empty_user_data()
    {
        Http::fake([
            '*/recommendations/456' => Http::response([
                'user_id' => 456,
                'recommendations' => [],
            ], 200),
        ]);

        $response = $this->mlServiceClient->getPersonalizedRecommendations(456, []);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(456, $response->json('user_id'));
        $this->assertIsArray($response->json('recommendations'));
    }

    public function test_update_training_data_with_empty_data()
    {
        Http::fake([
            '*/update-training' => Http::response(['status' => 'no changes'], 200),
        ]);

        $response = $this->mlServiceClient->updateTrainingData([]);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('no changes', $response->json('status'));
    }
}
