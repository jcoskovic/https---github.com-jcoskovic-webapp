<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbbreviationRequest;
use App\Http\Requests\UpdateAbbreviationRequest;
use App\Http\Resources\AbbreviationResource;
use App\Http\Resources\CommentResource;
use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\Vote;
use App\Services\AbbreviationSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbbreviationController extends Controller
{
    private AbbreviationSuggestionService $suggestionService;

    public function __construct(AbbreviationSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * Check if the current user is an admin
     */
    private function isAdmin(): bool
    {
        $user = Auth::user();

        return $user && $user->role === UserRole::ADMIN;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Abbreviation::with(['user', 'votes', 'comments.user'])
            ->where('status', 'approved'); // Only show approved abbreviations

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('abbreviation', 'LIKE', "%{$search}%")
                    ->orWhere('meaning', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        // Handle sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = [
            'created_at',
            'abbreviation', 
            'meaning',
            'votes_sum',
            'comments_count'
        ];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        
        // Apply sorting
        if ($sortBy === 'votes_sum') {
            // Sort by net score (upvotes - downvotes) using AbbreviationResource calculation
            $abbreviations = $query->withCount(['votes', 'comments'])
                ->with(['votes'])
                ->get();
            
            // Calculate net scores and sort appropriately
            if ($sortOrder === 'desc') {
                // Highest net score first (most liked)
                $abbreviations = $abbreviations->sortByDesc(function ($abbr) {
                    $upvotes = $abbr->votes->where('type', 'up')->count();
                    $downvotes = $abbr->votes->where('type', 'down')->count();
                    return $upvotes - $downvotes;
                });
            } else {
                // Lowest net score first (least liked/most disliked)
                $abbreviations = $abbreviations->sortBy(function ($abbr) {
                    $upvotes = $abbr->votes->where('type', 'up')->count();
                    $downvotes = $abbr->votes->where('type', 'down')->count();
                    return $upvotes - $downvotes;
                });
            }
            
            $abbreviations = $abbreviations->values();
            
            // Manual pagination since we sorted in PHP
            $perPage = 10;
            $currentPage = $request->get('page', 1);
            $total = $abbreviations->count();
            $offset = ($currentPage - 1) * $perPage;
            $items = $abbreviations->slice($offset, $perPage);
            
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $items, $total, $perPage, $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );
            
            $abbreviations = $paginated;
        } elseif ($sortBy === 'comments_count') {
            $abbreviations = $query->withCount(['comments'])->orderBy('comments_count', $sortOrder)->paginate(10);
        } else {
            $abbreviations = $query->orderBy($sortBy, $sortOrder)->paginate(10);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $abbreviations->currentPage(),
                'data' => AbbreviationResource::collection($abbreviations->items()),
                'first_page_url' => $abbreviations->url(1),
                'from' => $abbreviations->firstItem(),
                'last_page' => $abbreviations->lastPage(),
                'last_page_url' => $abbreviations->url($abbreviations->lastPage()),
                'links' => $abbreviations->linkCollection()->toArray(),
                'next_page_url' => $abbreviations->nextPageUrl(),
                'path' => $abbreviations->path(),
                'per_page' => $abbreviations->perPage(),
                'prev_page_url' => $abbreviations->previousPageUrl(),
                'to' => $abbreviations->lastItem(),
                'total' => $abbreviations->total(),
            ],
        ]);
    }

    public function store(StoreAbbreviationRequest $request): JsonResponse
    {
        $abbreviation = Abbreviation::create([
            'user_id' => Auth::id(),
            'abbreviation' => $request->abbreviation,
            'meaning' => $request->meaning,
            'description' => $request->description,
            'category' => $request->category,
        ]);

        $abbreviation->load(['user', 'votes', 'comments.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'skraćenica je uspješno dodana',
            'data' => new AbbreviationResource($abbreviation),
        ], 201);
    }

    public function show(Abbreviation $abbreviation): JsonResponse
    {
        $abbreviation->load(['user', 'votes', 'comments.user']);

        return response()->json([
            'status' => 'success',
            'data' => new AbbreviationResource($abbreviation),
        ]);
    }

    public function update(UpdateAbbreviationRequest $request, Abbreviation $abbreviation): JsonResponse
    {
        if (Auth::id() !== $abbreviation->user_id && ! $this->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nemate dozvolu za ažuriranje ove skraćenice',
            ], 403);
        }

        $abbreviation->update($request->validated());
        $abbreviation->load(['user', 'votes', 'comments.user']);

        return response()->json([
            'status' => 'success',
            'message' => 'skraćenica je uspješno ažurirana',
            'data' => new AbbreviationResource($abbreviation),
        ]);
    }

    public function destroy(Abbreviation $abbreviation): JsonResponse
    {
        if (Auth::id() !== $abbreviation->user_id && ! $this->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nemate dozvolu za brisanje ove skraćenice',
            ], 403);
        }

        $abbreviation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'skraćenica je uspješno obrisana',
        ]);
    }

    public function vote(Request $request, Abbreviation $abbreviation): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:up,down',
        ]);

        $userId = Auth::id();
        $voteType = $request->type;

        $existingVote = $abbreviation->votes()->where('user_id', $userId)->first();

        if ($existingVote) {
            if ($existingVote->type === $voteType) {
                $existingVote->delete();
                $message = 'Glas je uklonjen';
            } else {
                $existingVote->update(['type' => $voteType]);
                $message = 'Glas je promijenjen';
            }
        } else {
            $abbreviation->votes()->create([
                'user_id' => $userId,
                'type' => $voteType,
            ]);
            $message = 'Glas je dodan';
        }

        $abbreviation->load('votes');

        $upvotes = $abbreviation->votes->where('type', 'up')->count();
        $downvotes = $abbreviation->votes->where('type', 'down')->count();
        $userVote = $abbreviation->votes->where('user_id', $userId)->first();

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'upvotes_count' => $upvotes,
                'downvotes_count' => $downvotes,
                'votes_sum' => $upvotes - $downvotes,  // Add net score
                'user_vote' => $userVote?->type,
            ],
        ]);
    }

    public function addComment(Request $request, Abbreviation $abbreviation): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ], [
            'content.required' => 'Sadržaj komentara je obavezan',
            'content.max' => 'Komentar može imati maksimalno 1000 znakova',
        ]);

        $comment = $abbreviation->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar je uspješno dodan',
            'data' => new CommentResource($comment),
        ], 201);
    }

    /**
     * Get comments for abbreviation
     */
    public function getComments(Abbreviation $abbreviation): JsonResponse
    {
        $comments = $abbreviation->comments()->with('user')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => CommentResource::collection($comments),
        ]);
    }

    /**
     * Delete comment
     */
    public function deleteComment(Comment $comment): JsonResponse
    {
        $user = Auth::user();

        // Check if user owns the comment or is admin
        if ($comment->user_id !== $user->id && $user->role !== UserRole::ADMIN) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nemate dozvolu za brisanje ovog komentara',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar je uspješno obrisan',
        ]);
    }

    /**
     * Dobij kategorije
     */
    public function getCategories(): JsonResponse
    {
        $categories = Abbreviation::distinct()->pluck('category')->filter()->sort()->values();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Dobij statistike
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total_abbreviations' => Abbreviation::count(),
            'total_votes' => Vote::count(),
            'total_comments' => Comment::count(),
            'total_categories' => Abbreviation::distinct('category')->count('category'),
            'recent_abbreviations' => Abbreviation::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Dobij prijedloge za skraćenicu
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'abbreviation' => 'required|string|max:50',
        ]);

        $abbreviation = $request->abbreviation;

        $existing = Abbreviation::where('abbreviation', $abbreviation)->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'existing' => $existing,
                    'suggestions' => [],
                ],
            ]);
        }

        // Get suggestions from external APIs
        $suggestions = $this->suggestionService->getSuggestions($abbreviation);

        return response()->json([
            'status' => 'success',
            'data' => [
                'existing' => null,
                'suggestions' => $suggestions,
            ],
        ]);
    }
}
