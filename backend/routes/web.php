<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Abbrevio API is running',
        'status' => 'ok',
        'version' => '1.0.0',
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
    ]);
});

Route::get('/test', function () {
    return 'Simple test - no JSON, no database';
});

Route::get('/debug', function () {
    try {
        return response()->json([
            'app_name' => env('APP_NAME'),
            'app_env' => env('APP_ENV'),
            'app_key_set' => !empty(env('APP_KEY')),
            'db_connection' => env('DB_CONNECTION'),
            'database_url_set' => !empty(env('DATABASE_URL')),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
});

// Swagger documentation routes
Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'application/json',
        ]);
    }

    return response()->json(['error' => 'API documentation not found'], 404);
});

Route::get('/api/documentation', function () {
    return view('swagger-ui');
});
