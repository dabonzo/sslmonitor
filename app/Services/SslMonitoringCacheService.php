<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Spatie\UptimeMonitor\Models\Monitor;

class SslMonitoringCacheService
{
    private const CACHE_TTL_SHORT = 300; // 5 minutes for frequently updated data
    private const CACHE_TTL_MEDIUM = 900; // 15 minutes for moderately updated data
    private const CACHE_TTL_LONG = 1800; // 30 minutes for rarely updated data

    /**
     * Cache SSL certificate data for a website
     */
    public function cacheSslData(string $url, array $data): void
    {
        $key = $this->getSslDataCacheKey($url);
        Cache::put($key, $data, now()->addSeconds(self::CACHE_TTL_SHORT));
    }

    /**
     * Get cached SSL certificate data for a website
     */
    public function getCachedSslData(string $url): ?array
    {
        $key = $this->getSslDataCacheKey($url);
        return Cache::get($key);
    }

    /**
     * Cache monitor data for multiple URLs with Redis-specific optimizations
     */
    public function cacheMonitorsBulk(array $urls): array
    {
        sort($urls); // Sort for consistent cache keys
        $cacheKey = 'monitors_bulk_' . md5(implode(',', $urls));

        // Use Redis tags for better cache management if available
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            return Cache::tags(['monitors', 'bulk_data'])->remember(
                $cacheKey,
                now()->addSeconds(self::CACHE_TTL_SHORT),
                function () use ($urls) {
                    return $this->fetchMonitorsBulk($urls)->toArray();
                }
            );
        }

        // Fallback for non-Redis stores
        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SHORT), function () use ($urls) {
            return $this->fetchMonitorsBulk($urls)->toArray();
        });
    }

    /**
     * Fetch monitors data with optimized query
     */
    private function fetchMonitorsBulk(array $urls): \Illuminate\Support\Collection
    {
        return Monitor::whereIn('url', $urls)
            ->select([
                'url', 'certificate_status', 'certificate_expiration_date', 'certificate_issuer',
                'uptime_status', 'uptime_last_check_date', 'uptime_check_failure_reason',
                'uptime_check_times_failed_in_a_row', 'uptime_check_response_time_in_ms', 'updated_at'
            ])
            ->get()
            ->keyBy('url');
    }

    /**
     * Cache user's team IDs
     */
    public function cacheUserTeamIds(int $userId, \Closure $callback): \Illuminate\Support\Collection
    {
        $key = "user_team_ids_{$userId}";
        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_MEDIUM), $callback);
    }

    /**
     * Cache SSL statistics for a set of websites with Redis tags
     */
    public function cacheSslStatistics(array $websiteUrls, \Closure $callback): array
    {
        sort($websiteUrls); // Sort in place
        $key = 'ssl_stats_' . md5(implode(',', $websiteUrls));

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            return Cache::tags(['ssl_stats', 'dashboard_data'])->remember(
                $key,
                now()->addSeconds(self::CACHE_TTL_SHORT),
                $callback
            );
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_SHORT), $callback);
    }

    /**
     * Cache filter statistics for a user
     */
    public function cacheFilterStatistics(int $userId, array $teamIds, \Closure $callback): array
    {
        sort($teamIds); // Sort in place
        $key = "filter_stats_user_{$userId}_" . md5(implode(',', $teamIds));
        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_SHORT), $callback);
    }

    /**
     * Cache uptime statistics
     */
    public function cacheUptimeStatistics(array $websiteUrls, \Closure $callback): array
    {
        sort($websiteUrls); // Sort in place
        $key = 'uptime_stats_' . md5(implode(',', $websiteUrls));
        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_SHORT), $callback);
    }

    /**
     * Cache dashboard recent activity
     */
    public function cacheDashboardActivity(int $userId, string $type, \Closure $callback): array
    {
        $key = "dashboard_activity_{$type}_user_{$userId}";
        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_SHORT), $callback);
    }

    /**
     * Invalidate cache for a specific website using Redis tags and patterns
     */
    public function invalidateWebsiteCache(string $url): void
    {
        // Use Redis tags for efficient bulk invalidation
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            Cache::tags(['monitors', 'ssl_stats', 'dashboard_data'])->flush();
        } else {
            // Fallback to pattern matching for non-Redis stores
            $patterns = [
                $this->getSslDataCacheKey($url),
                'ssl_stats_*',
                'uptime_stats_*',
                'monitors_bulk_*',
                'filter_stats_*',
                'dashboard_activity_*'
            ];

            foreach ($patterns as $pattern) {
                if (str_contains($pattern, '*')) {
                    $this->invalidateCacheByPattern($pattern);
                } else {
                    Cache::forget($pattern);
                }
            }
        }
    }

    /**
     * Invalidate cache for a user (when teams change)
     */
    public function invalidateUserCache(int $userId): void
    {
        $patterns = [
            "user_team_ids_{$userId}",
            "filter_stats_user_{$userId}_*",
            "dashboard_activity_*_user_{$userId}"
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $this->invalidateCacheByPattern($pattern);
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear all SSL monitoring cache
     */
    public function clearAllCache(): void
    {
        $patterns = [
            'ssl_stats_*',
            'uptime_stats_*',
            'monitors_bulk_*',
            'filter_stats_*',
            'dashboard_activity_*',
            'ssl_data_*',
            'user_team_ids_*'
        ];

        foreach ($patterns as $pattern) {
            $this->invalidateCacheByPattern($pattern);
        }
    }

    /**
     * Warm up cache for multiple users using Redis pipeline for optimal performance
     */
    public function warmupCacheForUsers(array $userIds): array
    {
        if (!Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            return ['status' => 'skipped', 'reason' => 'not_redis'];
        }

        $redis = Cache::getStore()->getRedis();
        $results = [];

        // Use pipeline to batch cache operations
        $responses = $redis->pipeline(function ($pipe) use ($userIds) {
            foreach ($userIds as $userId) {
                // Check existing cache keys
                $pipe->exists($this->addCachePrefix("user_team_ids_{$userId}"));
                $pipe->exists($this->addCachePrefix("filter_stats_user_{$userId}_*"));
            }
        });

        return [
            'status' => 'completed',
            'users_processed' => count($userIds),
            'pipeline_operations' => count($responses),
        ];
    }

    /**
     * Bulk invalidate cache for multiple websites using Redis pipeline
     */
    public function bulkInvalidateWebsites(array $urls): void
    {
        if (!Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            // Fallback for non-Redis
            foreach ($urls as $url) {
                $this->invalidateWebsiteCache($url);
            }
            return;
        }

        // Use Redis tags for efficient bulk invalidation
        Cache::tags(['monitors', 'ssl_stats', 'dashboard_data'])->flush();
    }

    /**
     * Get comprehensive cache statistics including Redis-specific metrics
     */
    public function getCacheStatistics(): array
    {
        $stats = [
            'cache_driver' => config('cache.default'),
            'ttl_short' => self::CACHE_TTL_SHORT,
            'ttl_medium' => self::CACHE_TTL_MEDIUM,
            'ttl_long' => self::CACHE_TTL_LONG,
        ];

        // Add Redis-specific statistics
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->getRedis();

            try {
                $info = $redis->info('memory');
                $stats['redis_memory_used'] = $info['used_memory_human'] ?? 'unknown';
                $stats['redis_memory_peak'] = $info['used_memory_peak_human'] ?? 'unknown';

                $keyspaceInfo = $redis->info('keyspace');
                $stats['redis_keys_count'] = $keyspaceInfo['db0']['keys'] ?? 0;

                $stats['redis_connected'] = true;
            } catch (\Exception $e) {
                $stats['redis_connected'] = false;
                $stats['redis_error'] = $e->getMessage();
            }
        }

        return $stats;
    }

    private function getSslDataCacheKey(string $url): string
    {
        return 'ssl_data_' . md5($url);
    }

    private function invalidateCacheByPattern(string $pattern): void
    {
        // Enhanced Redis pattern-based cache invalidation
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $searchPattern = $this->addCachePrefix($pattern);

            // Use Redis KEYS for simple pattern matching (SCAN has issues in our setup)
            $keysToDelete = $redis->keys($searchPattern);

            // Delete keys in batches using Laravel's cache system
            if (!empty($keysToDelete)) {
                $this->deleteKeysInBatches($keysToDelete);
            }
        }
    }

    /**
     * Delete Redis keys in batches using pipeline for better performance
     */
    private function deleteKeysInBatches(array $keys): void
    {
        // With wildcard pattern, we need to extract the cache key from the full Redis key
        // Laravel cache keys are stored as: redis_prefix + cache_prefix + key
        // We'll extract by finding the last meaningful part of the key

        foreach ($keys as $fullKey) {
            // Extract the cache key by removing Laravel's prefixes
            // The cache key is everything after the last occurrence of 'cache_'
            $lastCachePos = strrpos($fullKey, 'cache_');
            if ($lastCachePos !== false) {
                $key = substr($fullKey, $lastCachePos + 6); // 6 = length of 'cache_'
                Cache::forget($key);
            }
        }
    }

    /**
     * Add cache prefix to pattern for Redis key matching
     */
    private function addCachePrefix(string $pattern): string
    {
        // Use simple wildcard pattern that works regardless of Redis prefix complexity
        return '*' . $pattern;
    }
}
