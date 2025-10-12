# SSL Monitor v4 - External Service Integration Patterns

This document provides comprehensive guidance for integrating with external services while maintaining optimal test performance and reliability.

## 🎯 Core Principles

### 1. **Always Mock External Services**
- **Never** make real network calls in tests
- **Always** use mock traits for external service dependencies
- **Maintain** test independence from external service availability

### 2. **Performance First**
- Real network calls cause 30+ second timeouts
- Mocked services provide sub-second execution
- CI/CD environments may not have external service access

### 3. **Reliability & Consistency**
- External services can be unreliable
- Mocks provide consistent test data
- Tests should be deterministic and repeatable

## 🔌 External Services in SSL Monitor v4

### 1. SSL Certificate Analysis Service

#### **Service Location**: `app/Services/SslCertificateAnalysisService.php`
#### **Purpose**: Analyzes SSL certificates for domains
#### **Performance Impact**: 30+ seconds per real connection
#### **Mock Trait**: `Tests\Traits\MocksSslCertificateAnalysis`

#### **Service Architecture**
```php
class SslCertificateAnalysisService
{
    public function analyzeCertificate(string $url): array
    {
        // Makes real SSL connections via stream_socket_client()
        // 30-second timeout causes massive test slowdown
        $socket = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            30, // ← Performance bottleneck
            STREAM_CLIENT_CONNECT,
            $context
        );

        return $this->parseCertificateDetails($cert, $host);
    }
}
```

#### **Mock Implementation**
```php
// tests/Traits/MocksSslCertificateAnalysis.php
trait MocksSslCertificateAnalysis
{
    protected function mockSslCertificateAnalysis(): void
    {
        $this->mock(SslCertificateAnalysisService::class, function ($mock) {
            $mock->shouldReceive('analyzeCertificate')
                ->andReturnUsing(function ($domain) {
                    return $this->getMockSslAnalysis($domain);
                });
        });
    }

    private function getMockSslAnalysis(string $domain): array
    {
        return [
            'basic_info' => [
                'domain' => $domain,
                'is_valid' => true,
                'checked_at' => now()->toISOString(),
                'response_time' => 0.05,
            ],
            'validity' => [
                'issued_at' => now()->subDays(60)->toISOString(),
                'expires_at' => now()->addDays(90)->toISOString(),
                'days_until_expiry' => 90,
                'is_expired' => false,
            ],
            'security' => [
                'key_algorithm' => 'RSA',
                'key_size' => 2048,
                'signature_algorithm' => 'SHA256withRSA',
                'security_score' => 95,
            ],
            // ... comprehensive mock data
        ];
    }
}
```

#### **Usage Pattern**
```php
// tests/Feature/SslMonitoringTest.php
use Tests\Traits\MocksSslCertificateAnalysis;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();
});

test('ssl certificate analysis works', function () {
    $service = app(SslCertificateAnalysisService::class);
    $analysis = $service->analyzeCertificate('example.com');

    expect($analysis['basic_info']['is_valid'])->toBeTrue();
    expect($analysis['security']['security_score'])->toBe(95);
});
```

### 2. JavaScript Content Fetcher Service

#### **Service Location**: `app/Services/UptimeMonitor/JavaScriptContentFetcher.php`
#### **Purpose**: Fetches JavaScript-rendered content via BrowserShot
#### **HTTP Endpoint**: `http://127.0.0.1:3000/fetch`
#### **Node.js Script**: `scripts/fetch-js-content.mjs`
#### **Performance Impact**: 5+ seconds per real call
#### **Mock Trait**: `Tests\Traits\MocksJavaScriptContentFetcher`

#### **Service Architecture**
```php
class JavaScriptContentFetcher
{
    public function fetchContent(string $url, ?int $waitSeconds = null): string
    {
        // Makes HTTP call to BrowserShot service
        $response = Http::timeout(config('browsershot.timeout', 30) + 10)
            ->post("{$serviceUrl}/fetch", [
                'url' => $url,
                'waitSeconds' => $waitSeconds,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP service returned error');
        }

        return $response->json('content', '');
    }
}
```

#### **BrowserShot Node.js Service**
```javascript
// scripts/fetch-js-content.mjs
import { firefox } from 'playwright-core';

(async () => {
    let browser;
    try {
        browser = await firefox.launch(launchOptions);
        const context = await browser.newContext();
        const page = await context.newPage();

        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForTimeout(waitSeconds * 1000);

        const content = await page.content();
        console.log(content);

        await browser.close();
    } catch (error) {
        console.error('Error fetching content:', error.message);
        process.exit(1);
    }
})();
```

#### **Mock Implementation**
```php
// tests/Traits/MocksJavaScriptContentFetcher.php
trait MocksJavaScriptContentFetcher
{
    protected function mockJavaScriptContentFetcher(): void
    {
        Http::fake([
            '*/fetch' => function ($request) {
                $url = $request['url'] ?? '';
                $waitSeconds = $request['waitSeconds'] ?? 5;

                // Handle different URL types
                if (str_starts_with($url, 'invalid://') || $url === 'invalid-url') {
                    return Http::response(['content' => ''], 200);
                }

                // Generate realistic mock HTML
                return Http::response([
                    'content' => $this->generateMockHtmlContent($url, $waitSeconds),
                    'url' => $url,
                    'timestamp' => now()->toISOString(),
                    'wait_seconds' => $waitSeconds,
                ], 200);
            },
        ]);
    }

    private function generateMockHtmlContent(string $url, int $waitSeconds): string
    {
        $timestamp = now()->toISOString();
        $encodedUrl = htmlspecialchars($url);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mock JavaScript Content for {$encodedUrl}</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('JavaScript executed for {$encodedUrl}');
            document.body.innerHTML += '<p id="js-content">JavaScript was executed!</p>';
        });
    </script>
</head>
<body>
    <h1>Mock JavaScript Content</h1>
    <p>This content is simulated for: {$encodedUrl}</p>
    <p>Wait time: {$waitSeconds} seconds</p>
    <p>Generated at: {$timestamp}</p>
    <div id="dynamic-content">
        <p>This content would normally be rendered by JavaScript</p>
    </div>
</body>
</html>
HTML;
    }
}
```

#### **Usage Pattern**
```php
// tests/Feature/JavaScriptContentFetcherTest.php
use Tests\Traits\MocksJavaScriptContentFetcher;

uses(RefreshDatabase::class, MocksJavaScriptContentFetcher::class);

beforeEach(function () {
    $this->setUpMocksJavaScriptContentFetcher();
    $this->fetcher = new JavaScriptContentFetcher();
});

test('javascript content fetcher works', function () {
    $content = $this->fetcher->fetchContent('https://example.com');

    expect($content)->toContain('Mock JavaScript Content');
    expect($content)->toContain('https://example.com');
});
```

### 3. HTTP Monitoring Requests

#### **Mock Trait**: `Tests\Traits\MocksMonitorHttpRequests` (Existing)
#### **Purpose**: Mock HTTP monitoring and uptime checks
#### **Covers**: Spatie Uptime Monitor HTTP requests

#### **Usage Pattern**
```php
// tests/Feature/SomeMonitoringTest.php
use Tests\Traits\MocksMonitorHttpRequests;

uses(UsesCleanDatabase::class, MocksMonitorHttpRequests::class);

beforeEach(function () {
    $this->setUpMocksMonitorHttpRequests();
});
```

## 🛠 Integration Guidelines

### When Adding New External Services

#### 1. **Assessment Phase**
```php
// Before implementation, ask:
- Does this service make network calls?
- What are the timeout configurations?
- Can the service be unreliable or unavailable?
- Are there authentication requirements?
- What data formats does it return?
```

#### 2. **Mock Design Phase**
```php
// Design your mock trait:
trait MocksNewService
{
    /**
     * Mock New External Service to avoid real network calls
     *
     * Performance improvement: 30s+ → 0.20s
     * Reliability: Works offline and in CI/CD
     * Consistency: Provides predictable test data
     */
    protected function mockNewService(): void
    {
        $this->mock(NewService::class, function ($mock) {
            $mock->shouldReceive('apiCall')
                ->andReturnUsing(function ($params) {
                    return $this->generateMockResponse($params);
                });
        });
    }

    private function generateMockResponse(array $params): array
    {
        return [
            'status' => 'success',
            'data' => 'mock_data_based_on_params',
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function setUpMocksNewService(): void
    {
        $this->mockNewService();
    }
}
```

#### 3. **Implementation Phase**
```php
// tests/Feature/NewServiceTest.php
use Tests\Traits\MocksNewService;

uses(UsesCleanDatabase::class, MocksNewService::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksNewService();
});

test('new service integration works', function () {
    $service = app(NewService::class);
    $result = $service->processData($input);

    expect($result['status'])->toBe('success');
    // Test logic using mocked responses
});
```

### Service Integration Checklist

#### **Before Integration**
- [ ] Service identified as external dependency
- [ ] Performance impact assessed (timeouts, network latency)
- [ ] Reliability concerns documented
- [ ] Mock trait designed and implemented

#### **During Implementation**
- [ ] Mock trait properly integrated
- [ ] Test uses mocked service, not real service
- [ ] Mock covers all service methods and edge cases
- [ ] Performance tests pass (< 1 second per test)

#### **After Implementation**
- [ ] Full test suite runs under performance targets
- [ ] Documentation updated with new service patterns
- [ ] Mock trait added to required traits checklist
- [ ] CI/CD pipeline works without external service dependencies

## 🚫 Anti-Patterns to Avoid

### **Never Do This**
```php
// ❌ ANTI-PATTERN: Real external service calls in tests
test('ssl analysis works', function () {
    $service = new SslCertificateAnalysisService();
    $result = $service->analyzeCertificate('https://real-domain.com'); // 30s+ timeout!
    expect($result)->toBeValid();
});

// ❌ ANTI-PATTERN: No mocking for external dependencies
test('javascript content fetching', function () {
    $fetcher = new JavaScriptContentFetcher();
    $content = $fetcher->fetchContent('https://example.com'); // 5s+ timeout!
    expect($content)->toContain('content');
});

// ❌ ANTI-PATTERN: Conditional mocking based on service availability
beforeEach(function () {
    if (ExternalService::isAvailable()) {
        // Use real service
    } else {
        // Use mock - inconsistent behavior!
    }
});
```

### **Always Do This**
```php
// ✅ PATTERN: Always mock external services
use Tests\Traits\MocksSslCertificateAnalysis;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();
});

test('ssl analysis works', function () {
    $service = app(SslCertificateAnalysisService::class);
    $result = $service->analyzeCertificate('https://example.com'); // 0.20s!
    expect($result)->toBeValid();
});
```

## 📊 Performance Impact Summary

| Service | Real Call Time | Mocked Time | Improvement | Mock Trait |
|---------|----------------|-------------|-------------|------------|
| SSL Certificate Analysis | 30s+ | 0.20s | **99%** | `MocksSslCertificateAnalysis` |
| JavaScript Content Fetcher | 5s+ | 0.75s | **95%** | `MocksJavaScriptContentFetcher` |
| HTTP Monitoring | Variable | < 0.1s | **90%+** | `MocksMonitorHttpRequests` |

## 🔍 Debugging External Service Issues

### Identifying Unmocked Service Calls

#### **Search Patterns**
```bash
# Search for potential external service calls
grep -r "Http::\|curl\|file_get_contents\|stream_socket_client" tests/
grep -r "timeout\|connect\|fetch\|request" tests/
grep -r "Service::\|Client::\|Api::" tests/

# Check for missing mock traits
grep -r "new.*Service\|new.*Client\|new.*Api" tests/
```

#### **Performance Debugging**
```bash
# Run specific test with timing
time ./vendor/bin/sail artisan test --filter="suspect_test_name"

# Run with verbose output to see what's happening
./vendor/bin/sail artisan test --filter="suspect_test_name" --debug

# Check for HTTP calls during test execution
./vendor/bin/sail artisan test --filter="suspect_test_name" 2>&1 | grep -i "http\|timeout\|connect"
```

### Common Issues & Solutions

#### **Issue**: Tests timing out after 30 seconds
**Cause**: Unmocked SSL certificate analysis
**Solution**: Add `MocksSslCertificateAnalysis` trait

#### **Issue**: Tests taking 5+ seconds
**Cause**: Unmocked JavaScript content fetcher
**Solution**: Add `MocksJavaScriptContentFetcher` trait

#### **Issue**: Intermittent test failures
**Cause**: External service dependency
**Solution**: Ensure complete mocking of external dependencies

#### **Issue**: Tests failing in CI but passing locally
**Cause**: External service not available in CI environment
**Solution**: Add comprehensive mocking for all external services

## 📚 Additional Resources

- [Testing Insights & Best Practices](TESTING_INSIGHTS.md)
- [Performance Testing Workflow](PERFORMANCE_WORKFLOW.md)
- [Mock Traits Documentation](../tests/Traits/)
- [Laravel HTTP Client Documentation](https://laravel.com/docs/http-client)
- [Pest Testing Framework](https://pestphp.com/docs)

---

**Remember**: External service mocking is not optional - it's essential for maintaining test performance, reliability, and CI/CD compatibility.