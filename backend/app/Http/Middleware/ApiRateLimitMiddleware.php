<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as Limiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $limits = '60,1'): Response
    {
        $parts = explode(',', $limits);
        $maxAttempts = (int) ($parts[0] ?? 60);
        $decayMinutes = (int) ($parts[1] ?? 1);

        $key = $this->resolveRequestSignature($request);

        if (Limiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Previše zahtjeva. Pokušajte ponovo za '.$this->getTimeUntilReset($key).' sekundi.',
                'retry_after' => Limiter::availableIn($key),
            ], 429);
        }

        Limiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => Limiter::remaining($key, $maxAttempts),
            'X-RateLimit-Reset' => now()->addSeconds(Limiter::availableIn($key))->timestamp,
        ]);

        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        // Rate limit by user if authenticated, otherwise by IP
        if ($request->user()) {
            return 'api_user_'.$request->user()->id;
        }

        return 'api_ip_'.$request->ip();
    }

    protected function getTimeUntilReset(string $key): int
    {
        return Limiter::availableIn($key);
    }
}
