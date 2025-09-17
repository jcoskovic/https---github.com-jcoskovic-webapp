<?php

namespace App\Services;

use App\Models\Abbreviation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for calculating trending abbreviations based on user activity
 */
class TrendingService
{
    /**
     * Calculate trending abbreviations based on recent activity
     *
     * @param  int  $limit  Maximum number of trending abbreviations to return
     * @return array Array of trending abbreviations with scores
     */
    /**
     * @return list<array<string, mixed>>
     */
    public function calculateTrendingAbbreviations(int $limit): array
    {
        try {
            // First try to get trending from ML service
            $mlServiceUrl = env('ML_SERVICE_URL', 'http://ml-service:5000');
            $response = Http::timeout(10)->get("{$mlServiceUrl}/recommendations/trending", [
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $mlResponse = $response->json();

                if (isset($mlResponse['trending']) && ! empty($mlResponse['trending'])) {
                    // ML service returned valid trending data
                    $trending = [];

                    foreach ($mlResponse['trending'] as $item) {
                        // Get full abbreviation data from database
                        $abbreviation = Abbreviation::with(['user'])
                            ->select('id', 'abbreviation', 'meaning', 'description', 'category', 'user_id', 'created_at')
                            ->where('id', $item['id'])
                            ->where('status', 'approved')
                            ->first();

                        if ($abbreviation) {
                            $trending[] = [
                                'id' => $abbreviation->id,
                                'abbreviation' => $abbreviation->abbreviation,
                                'meaning' => $abbreviation->meaning,
                                'description' => $abbreviation->description,
                                'category' => $abbreviation->category,
                                'score' => $item['score'], // Use ML service score
                                'user' => $abbreviation->user,
                                'created_at' => $abbreviation->created_at,
                                'recommendation_reason' => 'Trending skraćenice na osnovu ML algoritma',
                            ];
                        }
                    }

                    if (! empty($trending)) {
                        return $trending;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('ML service unavailable for trending, using fallback: '.$e->getMessage());
        }

        // Fallback to internal calculation if ML service fails
        return $this->calculateTrendingFallback($limit);
    }

    /**
     * Fallback method to calculate trending when ML service is unavailable
     *
     * @param  int  $limit  Maximum number of trending abbreviations to return
     * @return array Array of trending abbreviations with scores
     */
    /**
     * @return list<array<string, mixed>>
     */
    private function calculateTrendingFallback(int $limit): array
    {
        $sevenDaysAgo = now()->subDays(7)->format('Y-m-d H:i:s');
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d H:i:s');

        $trending = Abbreviation::select(
            'abbreviations.id',
            'abbreviations.abbreviation',
            'abbreviations.meaning',
            'abbreviations.category',
            DB::raw("(
                COALESCE(vote_counts.vote_score, 0) * 0.3 +
                COALESCE(comment_counts.comment_count, 0) * 0.2 +
                CASE 
                    WHEN abbreviations.created_at > '{$sevenDaysAgo}' THEN 5
                    WHEN abbreviations.created_at > '{$thirtyDaysAgo}' THEN 2
                    ELSE 0
                END +
                COALESCE(recent_votes.recent_vote_count, 0) * 0.4 +
                COALESCE(recent_comments.recent_comment_count, 0) * 0.1
            ) AS score")
        )
            ->leftJoin(DB::raw('(
            SELECT 
                abbreviation_id,
                SUM(CASE WHEN type = "up" THEN 1 WHEN type = "down" THEN -1 ELSE 0 END) as vote_score
            FROM votes 
            GROUP BY abbreviation_id
        ) as vote_counts'), 'abbreviations.id', '=', 'vote_counts.abbreviation_id')
            ->leftJoin(DB::raw('(
            SELECT abbreviation_id, COUNT(*) as comment_count
            FROM comments 
            GROUP BY abbreviation_id
        ) as comment_counts'), 'abbreviations.id', '=', 'comment_counts.abbreviation_id')
            ->leftJoin(DB::raw("(
            SELECT 
                abbreviation_id, 
                COUNT(*) as recent_vote_count
            FROM votes 
            WHERE created_at > '{$sevenDaysAgo}'
            GROUP BY abbreviation_id
        ) as recent_votes"), 'abbreviations.id', '=', 'recent_votes.abbreviation_id')
            ->leftJoin(DB::raw("(
            SELECT abbreviation_id, COUNT(*) as recent_comment_count
            FROM comments 
            WHERE created_at > '{$sevenDaysAgo}'
            GROUP BY abbreviation_id
        ) as recent_comments"), 'abbreviations.id', '=', 'recent_comments.abbreviation_id')
            ->where('abbreviations.status', 'approved')
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(function ($abbr) {
                // Normalize the score to 0-1 range (same as ML service)
                $score = (float) ($abbr->getAttribute('score') ?? 0);
                $normalizedScore = min($score / 10.0, 1.0);
                $normalizedScore = round(max($normalizedScore, 0.01), 3); // Minimum 0.01, max 1.0

                return [
                    'id' => $abbr->id,
                    'abbreviation' => $abbr->abbreviation,
                    'meaning' => $abbr->meaning,
                    'category' => $abbr->category,
                    'votes_sum' => (int) ($abbr->getAttribute('vote_score') ?? 0),
                    'similarity_score' => $score, // Keep original score for similarity
                    'score' => $normalizedScore, // Use normalized score for display
                    'recommendation_reason' => 'Trending skraćenice na osnovu glasova i komentara (fallback)',
                ];
            })
            ->toArray();

        return $trending;
    }

    /**
     * Get fallback personal recommendations when ML service is unavailable
     *
     * @param  int  $userId  User ID to get recommendations for
     * @param  int  $limit  Maximum number of recommendations to return
     * @return array Array of recommendations
     */
    /**
     * @return list<array<string, mixed>>
     */
    public function getFallbackPersonalRecommendations(int $userId, int $limit = 10): array
    {
        // Get user's voted abbreviations to exclude them (no longer using user_interactions)
        $votedAbbreviations = DB::table('votes')
            ->where('user_id', $userId)
            ->pluck('abbreviation_id')
            ->toArray();

        // Also exclude abbreviations user commented on
        $commentedAbbreviations = DB::table('comments')
            ->where('user_id', $userId)
            ->pluck('abbreviation_id')
            ->toArray();

        $excludeIds = array_merge($votedAbbreviations, $commentedAbbreviations);

        // Get user's category preferences based on their votes
        $userPreferredCategories = DB::table('votes')
            ->join('abbreviations', 'votes.abbreviation_id', '=', 'abbreviations.id')
            ->where('votes.user_id', $userId)
            ->where('votes.type', 'up') // Only positive votes
            ->groupBy('abbreviations.category')
            ->orderBy(DB::raw('COUNT(*)'), 'desc')
            ->pluck('abbreviations.category')
            ->toArray();

        // Get recommendations with calculated scores
        $recommendations = Abbreviation::select(
            'id',
            'abbreviation',
            'meaning',
            'description',
            'category',
            'created_at',
            DB::raw('COALESCE((SELECT SUM(CASE WHEN type = "up" THEN 1 WHEN type = "down" THEN -1 ELSE 0 END) FROM votes WHERE abbreviation_id = abbreviations.id), 0) as votes_sum'),
            DB::raw('COALESCE((SELECT COUNT(*) FROM comments WHERE abbreviation_id = abbreviations.id), 0) as comments_count')
        )
            ->where('status', 'approved')
            ->when(! empty($excludeIds), function ($query) use ($excludeIds) {
                return $query->whereNotIn('id', $excludeIds);
            })
            ->orderBy('created_at', 'desc') // Recent abbreviations
            ->limit($limit * 2) // Get more to allow for scoring
            ->get()
            ->map(function ($abbr, $index) use ($userPreferredCategories) {
                // Calculate score based on multiple factors
                $baseScore = 0.3; // Base score

                // Recency bonus (newer abbreviations get higher scores)
                $daysSinceCreated = now()->diffInDays($abbr->created_at);
                $recencyScore = max(0, (30 - $daysSinceCreated) / 30) * 0.3;

                // Popularity bonus (based on votes and comments)
                $votesSum = (int) ($abbr->getAttribute('votes_sum') ?? 0);
                $commentsCount = (int) ($abbr->getAttribute('comments_count') ?? 0);
                $popularityScore = (($votesSum * 0.1) + ($commentsCount * 0.05)) / 10;
                $popularityScore = min(0.3, $popularityScore); // Cap at 0.3

                // Category preference bonus
                $categoryScore = 0;
                if (in_array($abbr->category, $userPreferredCategories)) {
                    $categoryIndex = array_search($abbr->category, $userPreferredCategories);
                    $categoryScore = max(0, (count($userPreferredCategories) - $categoryIndex) / count($userPreferredCategories)) * 0.2;
                }

                // Position penalty (later items get slightly lower scores)
                $positionPenalty = ($index * 0.01);

                // Add some randomness to make it more interesting
                $randomBonus = (rand(0, 100) / 1000); // 0 to 0.1

                $finalScore = $baseScore + $recencyScore + $popularityScore + $categoryScore - $positionPenalty + $randomBonus;
                $finalScore = max(0.1, min(1.0, $finalScore)); // Ensure score is between 0.1 and 1.0

                return [
                    'id' => $abbr->id,
                    'abbreviation' => $abbr->abbreviation,
                    'meaning' => $abbr->meaning,
                    'description' => $abbr->description,
                    'category' => $abbr->category,
                    'score' => round($finalScore, 2),
                ];
            })
            ->sortByDesc('score') // Sort by calculated score
            ->take($limit) // Take only the requested number
            ->values()
            ->toArray();

        return $recommendations;
    }
}
