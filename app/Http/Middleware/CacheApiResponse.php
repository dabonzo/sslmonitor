<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ttl = '300'): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Don't cache if user is not authenticated
        if (! $request->user()) {
            return $next($request);
        }

        // Don't cache Inertia requests - only cache pure API endpoints
        if ($request->header('X-Inertia') || $request->expectsJson() === false) {
            return $next($request);
        }

        // Generate cache key based on route, user, and query parameters
        $cacheKey = $this->generateCacheKey($request);

        // Check if we have cached response
        if ($cachedResponse = Cache::get($cacheKey)) {
            $response = response()->json($cachedResponse['data']);

            // Add cache headers
            $response->headers->add([
                'X-Cache-Status' => 'HIT',
                'X-Cache-Key' => $cacheKey,
                'X-Cache-TTL' => $ttl,
            ]);

            return $response;
        }

        // Get fresh response
        $response = $next($request);

        // Only cache successful JSON responses
        if ($response->isSuccessful() &&
            $response->headers->get('content-type') === 'application/json' ||
            str_contains($response->headers->get('content-type', ''), 'application/json')) {

            $data = json_decode($response->getContent(), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                Cache::put($cacheKey, [
                    'data' => $data,
                    'cached_at' => now()->toISOString(),
                ], now()->addSeconds((int) $ttl));

                // Add cache headers to response
                $response->headers->add([
                    'X-Cache-Status' => 'MISS',
                    'X-Cache-Key' => $cacheKey,
                    'X-Cache-TTL' => $ttl,
                ]);
            }
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request
     */
    private function generateCacheKey(Request $request): string
    {
        $user = $request->user();
        $route = $request->route()->getName() ?? $request->path();

        // Include relevant query parameters in cache key
        $queryParams = $request->query();

        // Sort query parameters for consistent cache keys
        ksort($queryParams);

        $keyComponents = [
            'api_cache',
            $route,
            'user_'.$user->id,
            md5(serialize($queryParams)),
        ];

        return implode(':', $keyComponents);
    }

    /**
     * Clear cache for specific patterns
     */
    public static function clearCacheForUser(int $userId, ?string $pattern = null): void
    {
        $cacheKeyPattern = "api_cache:*:user_{$userId}:*";

        if ($pattern) {
            $cacheKeyPattern = "api_cache:{$pattern}:user_{$userId}:*";
        }

        // For Redis cache, we could implement pattern-based clearing
        // For now, we'll rely on TTL expiration
    }

    /**
     * Clear all API cache
     */
    public static function clearAllCache(): void
    {
        // Implementation would depend on cache driver
        // For Redis: SCAN and DELETE pattern matching keys
        // For now, we'll rely on TTL expiration
    }
}
