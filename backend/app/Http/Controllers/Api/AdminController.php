<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_abbreviations' => Abbreviation::count(),
                'total_votes' => Vote::count(),
                'total_comments' => Comment::count(),
                'pending_abbreviations' => Abbreviation::where('status', 'pending')->count(),
                'active_users_today' => User::where('updated_at', '>=', Carbon::today())->count(),
                'top_categories' => Abbreviation::select('category', DB::raw('count(*) as count'))
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        /** @var object{category:string,count:int} $item */
                        return [
                            'name' => $item->category,
                            'count' => (int) $item->count,
                        ];
                    }),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri učitavanju statistika',
            ], 500);
        }
    }

    /**
     * Get all users with additional information
     */
    public function getUsers(): JsonResponse
    {
        try {
            $users = User::withCount(['abbreviations', 'votes', 'comments'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri učitavanju korisnika',
            ], 500);
        }
    }

    /**
     * Get all abbreviations for admin review
     */
    public function getAbbreviations(): JsonResponse
    {
        try {
            $abbreviations = Abbreviation::with(['user:id,name,email'])
                ->withCount([
                    'votes as votes_sum' => function ($query) {
                        $query->selectRaw('SUM(CASE WHEN type = "up" THEN 1 WHEN type = "down" THEN -1 ELSE 0 END)');
                    },
                ])
                ->withCount('comments')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $abbreviations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri učitavanju skraćenica',
            ], 500);
        }
    }

    /**
     * Get pending abbreviations for moderation
     */
    public function getPendingAbbreviations(): JsonResponse
    {
        try {
            $abbreviations = Abbreviation::with(['user:id,name,email'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $abbreviations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri učitavanju skraćenica na čekanju',
            ], 500);
        }
    }

    /**
     * Promote user (assign moderator or admin role)
     */
    public function promoteUser(User $user): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if (! $currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nemate dozvolu za ovu akciju',
                ], 403);
            }

            if ($user->role === UserRole::USER) {
                $user->update(['role' => UserRole::MODERATOR->value]);
                $message = 'Korisnik uspješno unapređen u moderatora';
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Korisnik već ima veću ulogu',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri unapređivanju korisnika',
            ], 500);
        }
    }

    /**
     * Demote user (remove moderator role)
     */
    public function demoteUser(User $user): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if (! $currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nemate dozvolu za ovu akciju',
                ], 403);
            }

            if ($user->role === UserRole::MODERATOR) {
                $user->update(['role' => UserRole::USER->value]);
                $message = 'Korisnik uspješno snižen u običnog korisnika';
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ne možete sniziti ovog korisnika',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri snižavanju korisnika',
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if (! $currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nemate dozvolu za ovu akciju',
                ], 403);
            }

            if ($user->id === $currentUser->id || $user->role === UserRole::ADMIN) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ne možete obrisati ovog korisnika',
                ], 400);
            }

            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Korisnik uspješno obrisan',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri brisanju korisnika',
            ], 500);
        }
    }

    /**
     * Approve abbreviation
     */
    public function approveAbbreviation(Abbreviation $abbreviation): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if (! $currentUser->canModerateContent()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nemate dozvolu za ovu akciju',
                ], 403);
            }

            $abbreviation->update(['status' => 'approved']);

            return response()->json([
                'status' => 'success',
                'message' => 'skraćenica uspješno odobrena',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri odobravanju skraćenice',
            ], 500);
        }
    }

    /**
     * Reject abbreviation
     */
    public function rejectAbbreviation(Abbreviation $abbreviation): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if (! $currentUser->canModerateContent()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nemate dozvolu za ovu akciju',
                ], 403);
            }

            $abbreviation->update(['status' => 'rejected']);

            return response()->json([
                'status' => 'success',
                'message' => 'skraćenica uspješno odbijena',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Greška pri odbijanju skraćenice',
            ], 500);
        }
    }
}
