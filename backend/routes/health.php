<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        
        // Check if tables exist
        $tables = DB::select("SHOW TABLES");
        
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'tables_count' => count($tables),
            'timestamp' => now()->toISOString(),
            'app_version' => config('app.version', '1.0.0'),
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString(),
        ], 503);
    }
});

Route::get('/health/detailed', function () {
    try {
        $checks = [];
        
        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Cache check  
        try {
            Cache::put('health_check', 'ok', 60);
            $cached = Cache::get('health_check');
            $checks['cache'] = ['status' => $cached === 'ok' ? 'ok' : 'error'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // Storage check
        try {
            $storageWritable = is_writable(storage_path());
            $checks['storage'] = [
                'status' => $storageWritable ? 'ok' : 'error',
                'writable' => $storageWritable
            ];
        } catch (\Exception $e) {
            $checks['storage'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');
        
        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString(),
        ], 503);
    }
});