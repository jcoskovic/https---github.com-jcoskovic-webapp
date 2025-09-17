<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Abbrevio API is running',
        'status' => 'ok',
        'version' => '1.0.0',
    ]);
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

// Debug/Health check route
Route::get('/debug', function () {
    try {
        $debug = [
            'status' => 'Laravel is running',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'app_key_exists' => env('APP_KEY') ? 'YES' : 'NO',
            'app_key_length' => strlen(env('APP_KEY', '')),
            'db_connection' => env('DB_CONNECTION'),
            'db_host' => env('DB_HOST'),
            'db_database' => env('DB_DATABASE'),
            'db_username' => env('DB_USERNAME'),
            'timestamp' => now()->toDateTimeString()
        ];
        
        return response()->json($debug, 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});
