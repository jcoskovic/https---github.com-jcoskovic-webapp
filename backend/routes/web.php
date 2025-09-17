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
