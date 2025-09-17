<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for managing user data for ML recommendations
 */
class UserDataService
{
    /**
     * Get user data for ML recommendations
     *
     * @param  int  $userId  User ID to get data for
     * @return array{
     *   user_id: int,
     *   email: string,
     *   department: string,
     *   search_history: list<string>,
     *   viewed_abbreviations: list<int>,
     *   voted_abbreviations: list<int>,
     *   common_categories: list<string>,
     *   interactions: list<array<string, mixed>>
     * }|null User data array or null if user not found
     */
    public function getUserData(int $userId): ?array
    {
        // Get user basic info
        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        // Get user interactions from votes and comments instead of user_interactions table
        $votes = DB::table('votes')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $comments = DB::table('comments')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Combine votes and comments into interactions array
        $interactions = collect();

        foreach ($votes as $vote) {
            $interactions->push([
                'type' => 'vote',
                'abbreviation_id' => $vote->abbreviation_id,
                'created_at' => $vote->created_at,
                'metadata' => ['vote_type' => $vote->type],
            ]);
        }

        foreach ($comments as $comment) {
            $interactions->push([
                'type' => 'comment',
                'abbreviation_id' => $comment->abbreviation_id,
                'created_at' => $comment->created_at,
                'metadata' => ['content_length' => strlen($comment->content)],
            ]);
        }

        // Sort by date descending
        $interactions = $interactions->sortByDesc('created_at')->take(100)->values()->toArray();

        // For now, search history is empty since we don't track it
        $searchHistory = [];

        // Get viewed abbreviations from votes and comments
        $viewedAbbreviations = collect($votes)->pluck('abbreviation_id')
            ->merge(collect($comments)->pluck('abbreviation_id'))
            ->unique()
            ->values()
            ->toArray();

        // Get voted abbreviations
        $votedAbbreviations = DB::table('votes')
            ->where('user_id', $userId)
            ->pluck('abbreviation_id')
            ->unique()
            ->values()
            ->toArray();

        // Get common categories from viewed/voted abbreviations
        $commonCategories = DB::table('abbreviations')
            ->whereIn('id', array_merge($viewedAbbreviations, $votedAbbreviations))
            ->groupBy('category')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        return [
            'user_id' => (int) $userId,
            'email' => $user->email,
            'department' => $user->department ?? '',
            'search_history' => $searchHistory,
            'viewed_abbreviations' => $viewedAbbreviations,
            'voted_abbreviations' => $votedAbbreviations,
            'common_categories' => $commonCategories,
            'interactions' => $interactions,
        ];
    }

    /**
     * Get user data internally for ML service with relationships
     *
     * @param  int  $userId  User ID to get data for
     * @return array{
     *   user_id: int,
     *   email: string,
     *   department: ?string,
     *   votes: list<array{abbreviation_id: int, type: string}>,
     *   comments: list<array{abbreviation_id: int, content: string}>,
     *   search_history: list<string>,
     *   viewed_abbreviations: list<int>,
     *   voted_abbreviations: list<int>,
     *   common_categories: list<string>,
     *   interactions: list<array{interaction_type: string, abbreviation_id: int, created_at: mixed, metadata: array<string, mixed>}>
     * }|null Internal user data array or null if user not found
     */
    public function getUserDataInternal(int $userId): ?array
    {
        try {
            $user = User::with('votes', 'comments')->find($userId);

            if (! $user) {
                return null;
            }

            // Get user's viewed abbreviations from votes and comments
            $viewedFromVotes = $user->votes()
                ->pluck('abbreviation_id')
                ->toArray();

            $viewedFromComments = $user->comments()
                ->pluck('abbreviation_id')
                ->toArray();

            $viewedAbbreviations = array_unique(array_merge($viewedFromVotes, $viewedFromComments));

            // Get user's voted abbreviations
            $votedAbbreviations = $user->votes()
                ->pluck('abbreviation_id')
                ->unique()
                ->toArray();

            // Get common categories based on user votes and comments
            $votedCategories = DB::table('abbreviations')
                ->join('votes', 'abbreviations.id', '=', 'votes.abbreviation_id')
                ->where('votes.user_id', $userId)
                ->select('abbreviations.category')
                ->distinct()
                ->pluck('category')
                ->toArray();

            $commentedCategories = DB::table('abbreviations')
                ->join('comments', 'abbreviations.id', '=', 'comments.abbreviation_id')
                ->where('comments.user_id', $userId)
                ->select('abbreviations.category')
                ->distinct()
                ->pluck('category')
                ->toArray();

            $commonCategories = array_unique(array_merge($votedCategories, $commentedCategories));

            // Get recent interactions from votes and comments combined
            $recentVotes = DB::table('votes')
                ->where('user_id', $userId)
                ->select('abbreviation_id', 'type as interaction_type', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recentComments = DB::table('comments')
                ->where('user_id', $userId)
                ->select('abbreviation_id', DB::raw("'comment' as interaction_type"), 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $interactions = $recentVotes->concat($recentComments)
                ->sortByDesc('created_at')
                ->take(10)
                ->map(function ($interaction) {
                    return [
                        'interaction_type' => $interaction->interaction_type,
                        'abbreviation_id' => $interaction->abbreviation_id,
                        'created_at' => $interaction->created_at,
                        'metadata' => [],
                    ];
                })
                ->values()
                ->toArray();

            // Get user's votes in format expected by ML service
            $votes = $user->votes()
                ->select('abbreviation_id', 'type')
                ->get()
                ->map(function ($vote) {
                    return [
                        'abbreviation_id' => $vote->abbreviation_id,
                        'type' => $vote->type,
                    ];
                })
                ->toArray();

            // Get user's comments in format expected by ML service
            $comments = $user->comments()
                ->select('abbreviation_id', 'content')
                ->get()
                ->map(function ($comment) {
                    return [
                        'abbreviation_id' => $comment->abbreviation_id,
                        'content' => $comment->content,
                    ];
                })
                ->toArray();

            return [
                'user_id' => (int) $user->id,
                'email' => $user->email,
                'department' => $user->department,
                'votes' => $votes,
                'comments' => $comments,
                'search_history' => [], // Would need to implement if tracking searches
                'viewed_abbreviations' => $viewedAbbreviations,
                'voted_abbreviations' => $votedAbbreviations,
                'common_categories' => $commonCategories,
                'interactions' => $interactions,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting user data internally: '.$e->getMessage());

            return null;
        }
    }
}
