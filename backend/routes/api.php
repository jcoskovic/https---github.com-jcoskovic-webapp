<?php

use App\Http\Controllers\Api\AbbreviationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MLController;
use App\Http\Controllers\Api\SuggestionController;
use Illuminate\Support\Facades\Route;

// Health check routes (no rate limiting for monitoring)
require __DIR__.'/health.php';

// Apply global rate limiting and monitoring to all API routes
Route::middleware(['rate.limit:120,1', 'monitor'])->group(function () {

    // Authentication routes with stricter rate limiting
    Route::middleware(['rate.limit:20,1'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    });

    // Less restricted auth routes
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Public routes
    Route::get('/abbreviations', [AbbreviationController::class, 'index']);
    Route::get('/abbreviations/suggestions', [AbbreviationController::class, 'getSuggestions']);
    Route::get('/abbreviations/{abbreviation}', [AbbreviationController::class, 'show']);
    Route::get('/abbreviations/{abbreviation}/comments', [AbbreviationController::class, 'getComments']);
    Route::get('/stats', [AbbreviationController::class, 'getStats']);
    Route::get('/categories', [AbbreviationController::class, 'getCategories']);

    // Suggestion routes
    Route::post('/suggestions/generate', [SuggestionController::class, 'generate']);
    Route::get('/suggestions/category/{category}', [SuggestionController::class, 'getByCategory']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    Route::get('/suggestions/pending', [SuggestionController::class, 'getPending']);
    Route::post('/suggestions/{suggestionId}/approve', [SuggestionController::class, 'approve']);
    Route::post('/suggestions/{suggestionId}/reject', [SuggestionController::class, 'reject']);
    Route::delete('/suggestions/{suggestionId}', [SuggestionController::class, 'destroy']);
    Route::post('/suggestions/similar', [SuggestionController::class, 'getSimilar']);
    Route::post('/suggestions/validate', [SuggestionController::class, 'validateSuggestion']);
    Route::get('/suggestions/statistics', [SuggestionController::class, 'getStatistics']);

    // ML/Recommendation routes
    Route::get('/ml/health', [MLController::class, 'health']);
    Route::get('/ml/trending', [MLController::class, 'getTrending']);
    Route::get('/ml/recommendations/{abbreviationId}', [MLController::class, 'getRecommendations']);
    Route::get('/ml/recommendations/personalized/{userId}', [MLController::class, 'getPersonalizedRecommendations']);
    Route::get('/ml/user-data/{userId}', [MLController::class, 'getUserData']);
    Route::post('/ml/train', [MLController::class, 'trainModel']);

    // Protected auth routes
    Route::middleware(['jwt.auth'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Routes that require email verification
    Route::middleware(['jwt.auth', 'verified'])->group(function () {
        // Protected abbreviation routes - require authentication and verified email
        Route::post('/abbreviations', [AbbreviationController::class, 'store']);
        Route::put('/abbreviations/{abbreviation}', [AbbreviationController::class, 'update']);
        Route::delete('/abbreviations/{abbreviation}', [AbbreviationController::class, 'destroy']);

        // Voting and commenting
        Route::post('/abbreviations/{abbreviation}/vote', [AbbreviationController::class, 'vote']);
        Route::post('/abbreviations/{abbreviation}/comments', [AbbreviationController::class, 'addComment']);
        Route::delete('/comments/{comment}', [AbbreviationController::class, 'deleteComment']);

        // Export routes
        Route::get('/export/pdf', [ExportController::class, 'exportPdf']);
        Route::get('/export/test', [ExportController::class, 'testPdf']);

        // Moderator routes - content moderation and statistics
        Route::middleware(['jwt.auth', 'verified', 'moderator'])->prefix('moderator')->group(function () {
            Route::get('/statistics', [App\Http\Controllers\Api\AdminController::class, 'getStatistics']);
            Route::get('/abbreviations', [App\Http\Controllers\Api\AdminController::class, 'getAbbreviations']);
            Route::get('/abbreviations/pending', [App\Http\Controllers\Api\AdminController::class, 'getPendingAbbreviations']);

            // Abbreviation moderation
            Route::post('/abbreviations/{abbreviation}/approve', [App\Http\Controllers\Api\AdminController::class, 'approveAbbreviation']);
            Route::post('/abbreviations/{abbreviation}/reject', [App\Http\Controllers\Api\AdminController::class, 'rejectAbbreviation']);
        });

        // Admin routes - includes all moderator functions + user management
        Route::middleware(['jwt.auth', 'verified', 'admin'])->prefix('admin')->group(function () {
            // Content moderation (same as moderator)
            Route::get('/statistics', [App\Http\Controllers\Api\AdminController::class, 'getStatistics']);
            Route::get('/abbreviations', [App\Http\Controllers\Api\AdminController::class, 'getAbbreviations']);
            Route::get('/abbreviations/pending', [App\Http\Controllers\Api\AdminController::class, 'getPendingAbbreviations']);
            Route::post('/abbreviations/{abbreviation}/approve', [App\Http\Controllers\Api\AdminController::class, 'approveAbbreviation']);
            Route::post('/abbreviations/{abbreviation}/reject', [App\Http\Controllers\Api\AdminController::class, 'rejectAbbreviation']);

            // User management (admin only)
            Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'getUsers']);
            Route::post('/users/{user}/promote', [App\Http\Controllers\Api\AdminController::class, 'promoteUser']);
            Route::post('/users/{user}/demote', [App\Http\Controllers\Api\AdminController::class, 'demoteUser']);
            Route::delete('/users/{user}', [App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        });
    });
});
