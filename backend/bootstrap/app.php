<?php

// PHP 8.1/8.2 Compatibility: Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api([
            \App\Http\Middleware\CorsMiddleware::class,
            \App\Http\Middleware\UniformErrorHandlingMiddleware::class,
        ]);

        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JWTAuthMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'moderator' => \App\Http\Middleware\ModeratorMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'monitor' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
