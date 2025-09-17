<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class UniformErrorHandlingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(Throwable $e, Request $request): JsonResponse
    {
        // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Authentication errors
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'AUTHENTICATION_ERROR',
            ], 401);
        }

        // Authorization errors
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'AUTHORIZATION_ERROR',
            ], 403);
        }

        // Model not found
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // 404 errors
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Endpoint not found',
                'error_code' => 'ENDPOINT_NOT_FOUND',
            ], 404);
        }

        // HTTP exceptions
        if ($e instanceof HttpException) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'HTTP Error',
                'error_code' => 'HTTP_ERROR',
            ], $e->getStatusCode());
        }

        // Database errors
        if (str_contains(get_class($e), 'Database')) {
            return response()->json([
                'status' => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Database error occurred',
                'error_code' => 'DATABASE_ERROR',
            ], 500);
        }

        // Generic server errors
        $message = config('app.debug') ? $e->getMessage() : 'Internal server error';

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'error_code' => 'INTERNAL_SERVER_ERROR',
        ], 500);
    }
}
