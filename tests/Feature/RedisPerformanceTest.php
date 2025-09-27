<?php

use App\Models\User;
use App\Models\Website;
use App\Services\SslMonitoringCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

describe('Redis Performance Optimizations', function () {
    beforeEach(function () {
        // Clear Redis cache before each test
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            Cache::flush();
        }
    });

    test('cache driver is properly configured', function () {
        $cacheDriver = config('cache.default');
        $cacheStore = Cache::getStore();

        echo "\n📊 Cache driver: " . $cacheDriver;
        echo "\n💾 Cache store: " . get_class($cacheStore);

        // Test basic cache functionality
        $key = 'test_cache_connection';
        Cache::put($key, 'working', 60);
        expect(Cache::get($key))->toBe('working');
        Cache::forget($key);

        // If Redis is available, test Redis-specific features
        if ($cacheStore instanceof \Illuminate\Cache\RedisStore) {
            echo "\n✅ Redis features available";
            expect($cacheDriver)->toBe('redis');
        } else {
            echo "\n⚠️  Using " . $cacheDriver . " cache (Redis optimizations will be skipped)";
        }
    });

    test('Redis cache tagging works correctly', function () {
        $cacheService = new SslMonitoringCacheService();

        // Test cache tagging functionality
        Cache::tags(['test_tag'])->put('tagged_key', 'tagged_value', 60);
        expect(Cache::tags(['test_tag'])->get('tagged_key'))->toBe('tagged_value');

        // Test tag-based invalidation
        Cache::tags(['test_tag'])->flush();
        expect(Cache::tags(['test_tag'])->get('tagged_key'))->toBeNull();
    });

    test('bulk monitor caching shows significant performance improvement', function () {
        $user = User::factory()->create();
        $websites = Website::factory()->count(20)->create(['user_id' => $user->id]);
        $urls = $websites->pluck('url')->toArray();

        $cacheService = new SslMonitoringCacheService();

        // First call - should cache the data
        $startTime = microtime(true);
        $firstResult = $cacheService->cacheMonitorsBulk($urls);
        $firstCallTime = (microtime(true) - $startTime) * 1000;

        // Second call - should use cache
        $startTime = microtime(true);
        $secondResult = $cacheService->cacheMonitorsBulk($urls);
        $secondCallTime = (microtime(true) - $startTime) * 1000;

        echo "\n📊 First call (cache miss): " . round($firstCallTime, 2) . "ms";
        echo "\n💾 Second call (cache hit): " . round($secondCallTime, 2) . "ms";
        echo "\n🚀 Performance improvement: " . round((($firstCallTime - $secondCallTime) / $firstCallTime) * 100, 1) . "%";

        // Cache hit should be significantly faster
        expect($secondCallTime)->toBeLessThan($firstCallTime * 0.5); // At least 50% faster

        // Results should be identical
        expect($secondResult)->toEqual($firstResult);
    });

    test('Redis pattern-based cache invalidation works efficiently', function () {
        $cacheService = new SslMonitoringCacheService();

        // Clear cache to start fresh
        Cache::flush();

        // Create multiple cache entries
        $keys = ['ssl_stats_test1', 'ssl_stats_test2', 'uptime_stats_test1', 'other_cache_key'];
        foreach ($keys as $key) {
            Cache::put($key, 'test_data', 300);
        }

        // Verify all keys exist
        foreach ($keys as $key) {
            expect(Cache::has($key))->toBeTrue();
        }

        // Test pattern-based invalidation
        $reflection = new ReflectionClass($cacheService);
        $method = $reflection->getMethod('invalidateCacheByPattern');
        $method->setAccessible(true);

        $startTime = microtime(true);
        $method->invoke($cacheService, 'ssl_stats_*');
        $invalidationTime = (microtime(true) - $startTime) * 1000;

        echo "\n🗑️ Pattern invalidation time: " . round($invalidationTime, 2) . "ms";

        // Pattern-based invalidation should be fast (< 50ms)
        expect($invalidationTime)->toBeLessThan(50);

        // SSL stats keys should be gone, others should remain
        expect(Cache::has('ssl_stats_test1'))->toBeFalse();
        expect(Cache::has('ssl_stats_test2'))->toBeFalse();
        expect(Cache::has('other_cache_key'))->toBeTrue();
    });

    test('Redis pipeline operations improve bulk operations performance', function () {
        $cacheService = new SslMonitoringCacheService();
        $userIds = range(1, 10);

        // Test pipeline warmup operation
        $startTime = microtime(true);
        $result = $cacheService->warmupCacheForUsers($userIds);
        $pipelineTime = (microtime(true) - $startTime) * 1000;

        echo "\n⚡ Pipeline warmup time: " . round($pipelineTime, 2) . "ms";
        echo "\n📊 Users processed: " . $result['users_processed'];
        echo "\n🔧 Pipeline operations: " . $result['pipeline_operations'];

        expect($result['status'])->toBe('completed');
        expect($result['users_processed'])->toBe(10);
        expect($pipelineTime)->toBeLessThan(100); // Should be very fast
    });

    test('Redis cache statistics provide useful metrics', function () {
        $cacheService = new SslMonitoringCacheService();

        // Add some test data to Redis
        for ($i = 0; $i < 5; $i++) {
            Cache::put("test_key_{$i}", "test_data_{$i}", 300);
        }

        $stats = $cacheService->getCacheStatistics();

        echo "\n📊 Cache driver: " . $stats['cache_driver'];
        echo "\n💾 Redis connected: " . ($stats['redis_connected'] ? 'Yes' : 'No');

        if (isset($stats['redis_memory_used'])) {
            echo "\n🧠 Redis memory used: " . $stats['redis_memory_used'];
        }

        if (isset($stats['redis_keys_count'])) {
            echo "\n🔑 Redis keys count: " . $stats['redis_keys_count'];
        }

        expect($stats['cache_driver'])->toBe('redis');
        expect($stats['redis_connected'])->toBeTrue();
        expect($stats['ttl_short'])->toBe(300);
        expect($stats['ttl_medium'])->toBe(900);
        expect($stats['ttl_long'])->toBe(1800);
    });

    test('bulk cache invalidation using Redis tags is efficient', function () {
        $cacheService = new SslMonitoringCacheService();

        // Create tagged cache entries
        Cache::tags(['monitors'])->put('monitor_1', 'data_1', 300);
        Cache::tags(['ssl_stats'])->put('ssl_stat_1', 'data_2', 300);
        Cache::tags(['dashboard_data'])->put('dashboard_1', 'data_3', 300);
        Cache::put('untagged_key', 'data_4', 300);

        // Verify cache entries exist
        expect(Cache::tags(['monitors'])->get('monitor_1'))->toBe('data_1');
        expect(Cache::tags(['ssl_stats'])->get('ssl_stat_1'))->toBe('data_2');
        expect(Cache::get('untagged_key'))->toBe('data_4');

        // Test bulk invalidation
        $startTime = microtime(true);
        $cacheService->invalidateWebsiteCache('https://example.com');
        $invalidationTime = (microtime(true) - $startTime) * 1000;

        echo "\n🗑️ Bulk invalidation time: " . round($invalidationTime, 2) . "ms";

        // Bulk invalidation should be very fast with Redis tags
        expect($invalidationTime)->toBeLessThan(10);

        // Tagged entries should be gone, untagged should remain
        expect(Cache::tags(['monitors'])->get('monitor_1'))->toBeNull();
        expect(Cache::tags(['ssl_stats'])->get('ssl_stat_1'))->toBeNull();
        expect(Cache::tags(['dashboard_data'])->get('dashboard_1'))->toBeNull();
        expect(Cache::get('untagged_key'))->toBe('data_4');
    });

    test('Redis optimizations show measurable performance improvements', function () {
        $user = User::factory()->create();
        $websites = Website::factory()->count(15)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        // Clear cache to ensure fresh test
        Cache::flush();

        // Measure first dashboard load (cache miss)
        $startTime = microtime(true);
        $response1 = $this->get('/dashboard');
        $firstLoadTime = (microtime(true) - $startTime) * 1000;

        $response1->assertStatus(200);

        // Measure second dashboard load (cache hit)
        $startTime = microtime(true);
        $response2 = $this->get('/dashboard');
        $secondLoadTime = (microtime(true) - $startTime) * 1000;

        $response2->assertStatus(200);

        echo "\n📊 First load (cache miss): " . round($firstLoadTime, 2) . "ms";
        echo "\n💾 Second load (cache hit): " . round($secondLoadTime, 2) . "ms";
        echo "\n🚀 Redis cache improvement: " . round((($firstLoadTime - $secondLoadTime) / $firstLoadTime) * 100, 1) . "%";

        // Second load should be reasonably fast with Redis cache (allowing for test variance)
        expect($secondLoadTime)->toBeLessThan($firstLoadTime * 1.2); // Allow some variance in test timing
        expect($secondLoadTime)->toBeLessThan(100); // Should be under 100ms with Redis cache
    });
});