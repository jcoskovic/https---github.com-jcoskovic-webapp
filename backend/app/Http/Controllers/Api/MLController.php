<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetRecommendationsRequest;
use App\Http\Requests\GetTrendingRequest;
use App\Http\Requests\PersonalizedRecommendationsRequest;
use App\Services\MLServiceClient;
use App\Services\RecommendationService;
use App\Services\TrendingService;
use App\Services\UserDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MLController extends Controller
{
    public function __construct(
        private readonly MLServiceClient $mlServiceClient,
        private readonly RecommendationService $recommendationService,
        private readonly TrendingService $trendingService,
        private readonly UserDataService $userDataService
    ) {}

    /**
     * Get general recommendations from ML service
     */
    public function getRecommendations(GetRecommendationsRequest $request): JsonResponse
    {
        $limit = $request->validated()['limit'] ?? 10;

        try {
            $response = $this->mlServiceClient->getRecommendations($limit);

            if ($response->successful()) {
                $mlResponse = $response->json();
                $recommendations = $mlResponse['recommendations'] ?? [];

                return response()->json([
                    'status' => 'success',
                    'data' => $recommendations,
                    'message' => 'Recommendations retrieved successfully',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get recommendations from ML service',
                'data' => [],
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service unavailable',
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get personalized recommendations for a user
     */
    public function getPersonalizedRecommendations(PersonalizedRecommendationsRequest $request, int $userId): JsonResponse
    {
        $validated = $request->validated();
        $limit = $validated['limit'] ?? 10;

        \Log::info("MLController: getPersonalizedRecommendations called for user $userId with limit $limit");

        try {
            $recommendations = $this->recommendationService->getPersonalizedRecommendations($userId, $limit);
            \Log::info('MLController: RecommendationService returned: '.json_encode($recommendations));

            return response()->json($recommendations);
        } catch (\Exception $e) {
            \Log::error('MLController: Error in getPersonalizedRecommendations: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get personalized recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trending abbreviations
     */
    public function getTrending(GetTrendingRequest $request): JsonResponse
    {
        $limit = $request->validated()['limit'] ?? 10;

        try {
            $trending = $this->trendingService->calculateTrendingAbbreviations($limit);

            return response()->json([
                'status' => 'success',
                'data' => $trending,
                'message' => 'Trending abbreviations retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get trending abbreviations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user data for ML recommendations
     *
     * @param  int  $userId
     */
    public function getUserData($userId): JsonResponse
    {
        try {
            $userData = $this->userDataService->getUserData($userId);

            if (! $userData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $userData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update training data on ML service
     */
    public function updateTrainingData(Request $request): JsonResponse
    {
        try {
            $response = $this->mlServiceClient->updateTrainingData($request->all());

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Training data updated successfully',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update training data',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service unavailable',
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            if ($this->mlServiceClient->isAvailable()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ML service is available',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'ML service is not available',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Train model endpoint (alias for updateTrainingData)
     */
    public function trainModel(Request $request): JsonResponse
    {
        return $this->updateTrainingData($request);
    }
}
