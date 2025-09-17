<?php

namespace Tests\Unit;

use App\Models\Abbreviation;
use App\Models\User;
use App\Services\MLServiceClient;
use App\Services\RecommendationService;
use App\Services\TrendingService;
use App\Services\UserDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $recommendationService;

    /** @var MLServiceClient&MockInterface */
    private $mlServiceClient;

    /** @var TrendingService&MockInterface */
    private $trendingService;

    /** @var UserDataService&MockInterface */
    private $userDataService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mlServiceClient = Mockery::mock(MLServiceClient::class);
        $this->trendingService = Mockery::mock(TrendingService::class);
        $this->userDataService = Mockery::mock(UserDataService::class);

        $this->recommendationService = new RecommendationService(
            $this->mlServiceClient,
            $this->trendingService,
            $this->userDataService
        );

        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_personalized_recommendations_with_user_not_found()
    {
        $userId = 999;

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($userId)
            ->once()
            ->andReturn(null);

        $result = $this->recommendationService->getPersonalizedRecommendations($userId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
        $this->assertEmpty($result['data']);
    }

    public function test_get_personalized_recommendations_with_successful_ml_service()
    {
        $user = User::factory()->create();
        $abbreviations = Abbreviation::factory()->count(3)->create(['status' => 'approved']);

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $mlResponse = [
            'recommendations' => [
                ['id' => $abbreviations[0]->id, 'score' => 0.9],
                ['id' => $abbreviations[1]->id, 'score' => 0.8],
                ['id' => $abbreviations[2]->id, 'score' => 0.7],
            ],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('ml_service', $result['source']);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('Personal recommendations retrieved successfully', $result['message']);

        // Verify the recommendations have all required fields
        foreach ($result['data'] as $recommendation) {
            $this->assertArrayHasKey('id', $recommendation);
            $this->assertArrayHasKey('abbreviation', $recommendation);
            $this->assertArrayHasKey('meaning', $recommendation);
            $this->assertArrayHasKey('score', $recommendation);
        }
    }

    public function test_get_personalized_recommendations_with_empty_ml_response()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $mlResponse = [
            'recommendations' => [],
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
            ['id' => 2, 'abbreviation' => 'HTTP', 'meaning' => 'HyperText Transfer Protocol'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('Personal recommendations retrieved successfully (fallback)', $result['message']);
    }

    public function test_get_personalized_recommendations_with_ml_service_failure()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andThrow(new \Exception('ML service connection failed'));

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_personalized_recommendations_with_unsuccessful_ml_response()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(false);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_personalized_recommendations_with_non_existent_abbreviations()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        // ML service returns IDs that don't exist in the database
        $mlResponse = [
            'recommendations' => [
                ['id' => 999, 'score' => 0.9],
                ['id' => 1000, 'score' => 0.8],
            ],
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_personalized_recommendations_with_pending_abbreviations()
    {
        $user = User::factory()->create();
        $abbreviations = Abbreviation::factory()->count(2)->create(['status' => 'pending']);

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $mlResponse = [
            'recommendations' => [
                ['id' => $abbreviations[0]->id, 'score' => 0.9],
                ['id' => $abbreviations[1]->id, 'score' => 0.8],
            ],
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        // Should fallback because pending abbreviations are filtered out
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_personalized_recommendations_with_mixed_status_abbreviations()
    {
        $user = User::factory()->create();
        $approvedAbbr = Abbreviation::factory()->create(['status' => 'approved']);
        $pendingAbbr = Abbreviation::factory()->create(['status' => 'pending']);
        $rejectedAbbr = Abbreviation::factory()->create(['status' => 'rejected']);

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $mlResponse = [
            'recommendations' => [
                ['id' => $approvedAbbr->id, 'score' => 0.9],
                ['id' => $pendingAbbr->id, 'score' => 0.8],
                ['id' => $rejectedAbbr->id, 'score' => 0.7],
            ],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        // Should only return approved abbreviations
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('ml_service', $result['source']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($approvedAbbr->id, $result['data'][0]['id']);
        $this->assertEquals(0.9, $result['data'][0]['score']);
    }

    public function test_get_personalized_recommendations_with_custom_limit()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andThrow(new \Exception('ML service failed'));

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 5)
            ->once()
            ->andReturn([]);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id, 5);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
    }

    public function test_get_personalized_recommendations_handles_general_exception()
    {
        $userId = 123;

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($userId)
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        $result = $this->recommendationService->getPersonalizedRecommendations($userId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Failed to get personalized recommendations', $result['message']);
        $this->assertEquals('Database connection failed', $result['error']);
    }

    public function test_get_personalized_recommendations_with_malformed_ml_response()
    {
        $user = User::factory()->create();

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        // Malformed ML response - missing 'recommendations' key
        $mlResponse = [
            'data' => 'some other data',
        ];

        $fallbackRecommendations = [
            ['id' => 1, 'abbreviation' => 'API', 'meaning' => 'Application Programming Interface'],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $this->trendingService
            ->shouldReceive('getFallbackPersonalRecommendations')
            ->with($user->id, 10)
            ->once()
            ->andReturn($fallbackRecommendations);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['fallback']);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_personalized_recommendations_with_recommendations_missing_scores()
    {
        $user = User::factory()->create();
        $abbreviation = Abbreviation::factory()->create(['status' => 'approved']);

        $userData = [
            'user_id' => $user->id,
            'preferences' => ['Technology'],
            'interactions' => [],
        ];

        // ML response with missing score
        $mlResponse = [
            'recommendations' => [
                ['id' => $abbreviation->id], // No score provided
            ],
        ];

        $this->userDataService
            ->shouldReceive('getUserDataInternal')
            ->with($user->id)
            ->once()
            ->andReturn($userData);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->once()->andReturn(true);
        $response->shouldReceive('json')->once()->andReturn($mlResponse);

        $this->mlServiceClient
            ->shouldReceive('getPersonalizedRecommendations')
            ->with($user->id, $userData)
            ->once()
            ->andReturn($response);

        $result = $this->recommendationService->getPersonalizedRecommendations($user->id);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('ml_service', $result['source']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(1.0, $result['data'][0]['score']); // Default score
    }
}
