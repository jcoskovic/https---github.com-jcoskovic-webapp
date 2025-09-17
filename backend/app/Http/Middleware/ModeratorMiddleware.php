<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModeratorMiddleware
{
    /**
     * Handle an incoming request.
     * Allows access to users with moderator or admin privileges
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Neautorizovani pristup',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->canModerateContent()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nemate dozvolu za pristup ovom resursu',
            ], 403);
        }

        return $next($request);
    }
}
