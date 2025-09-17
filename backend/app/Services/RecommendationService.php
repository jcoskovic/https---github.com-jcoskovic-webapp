<?php

namespace App\Services;

use App\Models\Abbreviation;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for handling recommendation logic and data processing
 */
class RecommendationService
{
    public function __construct(
        private readonly MLServiceClient $mlServiceClient,
        private readonly TrendingService $trendingService,
        private readonly UserDataService $userDataService
    ) {}

    /**
     * Get personalized recommendations for a user
     *
     * @param  int  $userId  User ID to get recommendations for
     * @param  int  $limit  Maximum number of recommendations to return
     * @return array{
     *   status: 'success'|'error',
     *   message?: string,
     *   data?: list<array<string, mixed>>,
     *   source?: 'ml_service',
     *   fallback?: bool,
     *   error?: string
     * }
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 10): array
    {
        Log::info('getPersonalizedRecommendations called for user: '.$userId);

        try {
            // Try to get user data
            $userData = $this->userDataService->getUserDataInternal($userId);

            if (! $userData) {
                return [
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => [],
                ];
            }

            // Try ML service first
            try {
                $response = $this->mlServiceClient->getPersonalizedRecommendations($userId, $userData);

                if ($response->successful()) {
                    $mlResponse = $response->json();
                    Log::info('ML service response: '.json_encode($mlResponse));

                    if (isset($mlResponse['recommendations']) && ! empty($mlResponse['recommendations'])) {
                        // ML service returns array of objects with id and score, not just IDs
                        $recommendations = $mlResponse['recommendations'];
                        Log::info('Extracted recommendation objects: '.json_encode($recommendations));

                        if (count($recommendations) > 0) {
                            // Extract IDs for database lookup
                            $abbreviationIds = array_column($recommendations, 'id');
                            Log::info('Looking for abbreviation IDs: '.json_encode($abbreviationIds));

                            $existingIds = Abbreviation::whereIn('id', $abbreviationIds)->pluck('id')->toArray();
                            Log::info('Existing abbreviation IDs in database: '.json_encode($existingIds));

                            $approvedIds = Abbreviation::whereIn('id', $abbreviationIds)->where('status', 'approved')->pluck('id')->toArray();
                            Log::info('Approved abbreviation IDs: '.json_encode($approvedIds));

                            $fullRecommendations = Abbreviation::select('id', 'abbreviation', 'meaning', 'description', 'category')
                                ->whereIn('id', $approvedIds)
                                ->get()
                                ->map(function ($abbr) use ($recommendations) {
                                    // Find matching score from ML response
                                    $score = 1.0; // Default score

                                    foreach ($recommendations as $mlRec) {
                                        if (isset($mlRec['id']) && $mlRec['id'] == $abbr->id && isset($mlRec['score'])) {
                                            $score = $mlRec['score'];
                                            break;
                                        }
                                    }

                                    return [
                                        'id' => $abbr->id,
                                        'abbreviation' => $abbr->abbreviation,
                                        'meaning' => $abbr->meaning,
                                        'description' => $abbr->description,
                                        'category' => $abbr->category,
                                        'score' => $score,
                                    ];
                                })
                                ->toArray();

                            Log::info('Found '.count($fullRecommendations).' approved recommendations');

                            if (! empty($fullRecommendations)) {
                                return [
                                    'status' => 'success',
                                    'data' => $fullRecommendations,
                                    'message' => 'Personal recommendations retrieved successfully',
                                    'source' => 'ml_service',
                                ];
                            } else {
                                Log::info('No approved recommendations found, using fallback');
                            }
                        }
                    }
                }
            } catch (\Exception $mlException) {
                Log::warning('ML service failed: '.$mlException->getMessage());
            }

            // Fallback to trending recommendations
            Log::warning('Using fallback recommendations for user: '.$userId);
            $fallbackRecommendations = $this->trendingService->getFallbackPersonalRecommendations($userId, $limit);

            return [
                'status' => 'success',
                'data' => $fallbackRecommendations,
                'message' => 'Personal recommendations retrieved successfully (fallback)',
                'fallback' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Error in getPersonalizedRecommendations: '.$e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Failed to get personalized recommendations',
                'error' => $e->getMessage(),
            ];
        }
    }
}
