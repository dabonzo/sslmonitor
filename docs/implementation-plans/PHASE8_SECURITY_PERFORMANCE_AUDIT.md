# Phase 8: Security & Performance Audit

**Status**: 📋 Planned
**Estimated Duration**: 6-8 hours
**Complexity**: Medium
**Dependencies**: Phase 5 (Production Optimization)

## Overview

Comprehensive security audit and performance benchmarking to ensure production readiness, identify vulnerabilities, and establish performance baselines for ongoing monitoring.

## Objectives

- Audit application against OWASP Top 10 vulnerabilities
- Review Laravel security best practices implementation
- Establish performance baselines and benchmarks
- Identify optimization opportunities
- Document security policies and procedures

## Part 1: Security Audit Checklist (3-4 hours)

### Agent Assignment
- **Primary**: `laravel-backend-specialist` (security focus)
- **Support**: `database-analyzer` (SQL injection review)

### 1.1 OWASP Top 10 Review (2 hours)

#### A01:2021 - Broken Access Control
**Goal**: Verify authorization and permission systems

**Audit Checklist**:
```php
// ✅ Policy-based authorization
Gate::define('view-monitor', function (User $user, Monitor $monitor) {
    return $user->team_id === $monitor->website->team_id;
});

// ✅ Middleware protection
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('monitors', MonitorController::class);
});

// ✅ Request authorization
public function update(UpdateMonitorRequest $request, Monitor $monitor)
{
    $this->authorize('update', $monitor);
    // ...
}
```

**Manual Testing**:
1. Attempt to access monitors from different teams
2. Test API endpoints without authentication
3. Verify role-based access (Owner/Admin/Viewer)
4. Test team invitation acceptance flow

**Verification Commands**:
```bash
# List all routes with middleware
./vendor/bin/sail artisan route:list --columns=uri,middleware,name

# Check for unprotected routes
./vendor/bin/sail artisan route:list | grep -v "auth"

# Review policies
./vendor/bin/sail artisan tinker
>>> Gate::policies();
```

#### A02:2021 - Cryptographic Failures
**Goal**: Verify encryption and data protection

**Audit Checklist**:
```php
// ✅ Encrypted database columns
use Illuminate\Database\Eloquent\Casts\Encrypted;

class User extends Authenticatable
{
    protected $casts = [
        'google2fa_secret' => Encrypted::class,
    ];
}

// ✅ HTTPS enforcement
if (app()->environment('production')) {
    URL::forceScheme('https');
}

// ✅ Secure session configuration
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => 'strict',
```

**Manual Testing**:
1. Verify HTTPS redirect on production
2. Check session cookie security flags
3. Review encrypted columns in database
4. Test password reset token expiration

**Verification Commands**:
```bash
# Check encryption configuration
./vendor/bin/sail artisan tinker
>>> config('app.key');
>>> config('session.secure');

# Verify HTTPS enforcement
curl -I http://monitor.intermedien.at | grep -i location

# Review encrypted columns
./vendor/bin/sail artisan db:show
```

#### A03:2021 - Injection Attacks
**Goal**: Prevent SQL injection, XSS, and command injection

**Audit Checklist**:
```php
// ✅ Eloquent ORM (parameterized queries)
Monitor::where('team_id', $teamId)->get();

// ✅ Query builder with bindings
DB::table('monitors')
    ->where('status', '=', $status)
    ->get();

// ❌ AVOID: Raw queries without bindings
DB::select("SELECT * FROM monitors WHERE id = {$id}"); // DANGEROUS

// ✅ Blade automatic escaping
{{ $monitor->name }} // Escaped
{!! $monitor->name !!} // Unescaped (use carefully)

// ✅ Input validation
$validated = $request->validate([
    'email' => ['required', 'email', 'max:255'],
]);
```

**Manual Testing**:
1. Test input fields with SQL injection payloads
2. Test XSS payloads in form inputs
3. Review all raw DB queries
4. Verify CSRF protection on forms

**Verification Commands**:
```bash
# Search for raw queries
grep -r "DB::raw\|DB::select\|DB::statement" app/

# Check CSRF middleware
./vendor/bin/sail artisan route:list --columns=uri,middleware | grep -i csrf

# Review validation rules
grep -r "validate\|rules()" app/Http/Requests/
```

#### A04:2021 - Insecure Design
**Goal**: Verify secure architecture and design patterns

**Audit Checklist**:
- ✅ Rate limiting on authentication endpoints
- ✅ 2FA implementation for sensitive actions
- ✅ Secure password reset flow
- ✅ Team-based data isolation
- ✅ Audit logging for sensitive operations

**Review Points**:
```php
// Rate limiting
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// 2FA verification
if ($user->google2fa_enabled) {
    return redirect()->route('2fa.verify');
}

// Audit logging
Log::info('Monitor deleted', [
    'user_id' => auth()->id(),
    'monitor_id' => $monitor->id,
    'team_id' => $monitor->website->team_id,
]);
```

#### A05:2021 - Security Misconfiguration
**Goal**: Verify secure configuration in all environments

**Audit Checklist**:
```bash
# Production .env review
APP_DEBUG=false
APP_ENV=production
SESSION_SECURE_COOKIE=true

# Remove development tools in production
composer install --no-dev --optimize-autoloader

# Disable directory listing
# Apache .htaccess already configured

# Hide sensitive headers
X-Powered-By: (should be removed)
Server: (should be generic)
```

**Verification Commands**:
```bash
# Check production configuration
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && php artisan config:show"

# Verify debug mode
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && php artisan tinker"
>>> config('app.debug');

# Check installed packages
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && composer show --installed"

# Review HTTP headers
curl -I https://monitor.intermedien.at
```

#### A06:2021 - Vulnerable Components
**Goal**: Ensure dependencies are up-to-date and secure

**Audit Process**:
```bash
# Check for outdated packages
./vendor/bin/sail composer outdated --direct

# Security audit with Composer
./vendor/bin/sail composer audit

# NPM security audit
./vendor/bin/sail npm audit

# Check Laravel version
./vendor/bin/sail artisan --version

# Review vendor advisories
./vendor/bin/sail composer show --all | grep security
```

**Update Strategy**:
1. Review CHANGELOG for breaking changes
2. Update non-breaking versions first
3. Run full test suite after each update
4. Test on staging before production

#### A07:2021 - Identification and Authentication Failures
**Goal**: Verify robust authentication mechanisms

**Audit Checklist**:
```php
// ✅ Password requirements
'password' => ['required', 'string', 'min:8', 'confirmed'],

// ✅ 2FA implementation
if (! Google2FA::verifyKey($user->google2fa_secret, $code)) {
    throw ValidationException::withMessages([
        'code' => ['Invalid 2FA code.'],
    ]);
}

// ✅ Session timeout
'lifetime' => env('SESSION_LIFETIME', 120), // 2 hours

// ✅ Password reset expiration
'expire' => 60, // 1 hour
```

**Manual Testing**:
1. Test password complexity requirements
2. Verify 2FA enforcement
3. Test session timeout behavior
4. Verify password reset expiration
5. Test account lockout after failed attempts

#### A08:2021 - Software and Data Integrity Failures
**Goal**: Verify integrity of code and data

**Audit Checklist**:
- ✅ Composer lock file committed
- ✅ Package signature verification
- ✅ Database migration version control
- ✅ Deployment automation with rollback
- ✅ Database backups with verification

**Verification Commands**:
```bash
# Verify composer.lock integrity
./vendor/bin/sail composer validate

# Check migration status
./vendor/bin/sail artisan migrate:status

# Verify deployment configuration
cat deploy.php | grep -A 10 "rollback"

# Test rollback process
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web && ls -la releases/"
```

#### A09:2021 - Security Logging and Monitoring Failures
**Goal**: Verify comprehensive logging and monitoring

**Audit Checklist**:
```php
// ✅ Authentication events logged
Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('Failed login attempt', ['email' => $email]);

// ✅ Authorization failures logged
if (! $user->can('view', $monitor)) {
    Log::warning('Unauthorized monitor access attempt', [
        'user_id' => $user->id,
        'monitor_id' => $monitor->id,
    ]);
}

// ✅ Security-sensitive actions logged
Log::info('2FA enabled', ['user_id' => $user->id]);
Log::info('Team member invited', ['inviter_id' => auth()->id()]);
```

**Review Log Coverage**:
- Login/logout events
- Failed authentication attempts
- 2FA enable/disable
- Team invitations and acceptances
- Monitor creation/deletion
- Permission changes
- Horizon queue failures

**Verification Commands**:
```bash
# Review production logs
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && tail -n 100 storage/logs/laravel.log"

# Check log rotation
ssh default_deploy@monitor.intermedien.at "ls -lh /var/www/monitor.intermedien.at/web/current/storage/logs/"

# Verify Horizon monitoring
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && php artisan horizon:status"
```

#### A10:2021 - Server-Side Request Forgery (SSRF)
**Goal**: Prevent unauthorized internal network access

**Audit Checklist**:
```php
// ✅ URL validation for monitors
$validated = $request->validate([
    'url' => ['required', 'url', 'max:2048'],
]);

// ⚠️ Review URL fetching
// Check for private IP ranges
$privateRanges = [
    '10.0.0.0/8',
    '172.16.0.0/12',
    '192.168.0.0/16',
    '127.0.0.0/8',
];

// ✅ HTTP client timeout configuration
Http::timeout(10)->get($url);
```

**Manual Testing**:
1. Attempt to add monitors with private IPs (127.0.0.1, 192.168.x.x)
2. Test localhost URLs
3. Test internal network URLs
4. Verify timeout enforcement

### 1.2 Laravel Security Best Practices (1 hour)

#### Configuration Review
```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 120),
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'strict',

// config/cors.php (if using API)
'allowed_origins' => [env('APP_URL')],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

#### Mass Assignment Protection
```php
// All models should have fillable or guarded
class Monitor extends Model
{
    protected $fillable = [
        'url',
        'uptime_check_enabled',
        'certificate_check_enabled',
    ];
}
```

**Verification Command**:
```bash
# Find models without mass assignment protection
grep -r "class.*extends Model" app/Models/ | while read line; do
    file=$(echo $line | cut -d: -f1)
    if ! grep -q "protected \$\(fillable\|guarded\)" "$file"; then
        echo "⚠️  Missing mass assignment protection: $file"
    fi
done
```

#### Environment Variables
```bash
# Verify sensitive data not in code
grep -r "password.*=" app/ | grep -v '$password'
grep -r "secret.*=" app/ | grep -v config
grep -r "api.*key" app/ | grep -v config

# Check .env.example completeness
diff <(grep -o '^[A-Z_]*=' .env | sort) <(grep -o '^[A-Z_]*=' .env.example | sort)
```

### 1.3 Security Documentation (30 minutes)

Create `docs/SECURITY_POLICY.md`:
```markdown
# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 4.x     | :white_check_mark: |

## Reporting a Vulnerability

Please report security vulnerabilities to: security@intermedien.at

**Do not** create public GitHub issues for security vulnerabilities.

## Security Measures

### Authentication
- Password minimum length: 8 characters
- 2FA available via Google Authenticator
- Session timeout: 2 hours
- Password reset expiration: 1 hour

### Authorization
- Policy-based access control
- Team-based data isolation
- Role-based permissions (Owner, Admin, Viewer)

### Data Protection
- HTTPS enforced in production
- Encrypted sensitive fields (2FA secrets)
- Secure session cookies (httpOnly, secure, sameSite)
- CSRF protection on all forms

### Monitoring
- Failed login attempt logging
- Unauthorized access attempt logging
- Security-sensitive action audit trail

## Security Updates

Check for updates monthly:
```bash
composer audit
npm audit
```
```

## Part 2: Performance Benchmarking (2-3 hours)

### Agent Assignment
- **Primary**: `performance-optimizer`
- **Support**: `database-analyzer` (query performance)

### 2.1 Baseline Metrics Establishment (1 hour)

#### Dashboard Performance
```bash
# Homepage load time
time curl -s https://monitor.intermedien.at > /dev/null

# Dashboard with authentication
ab -n 100 -c 10 -C "session_cookie" https://monitor.intermedien.at/dashboard

# API endpoint performance
ab -n 1000 -c 50 https://monitor.intermedien.at/api/monitors
```

**Expected Baselines** (from Phase 5):
- Dashboard load: < 2 seconds
- API responses: < 200ms
- Database queries: < 100ms
- Queue processing: > 100 jobs/min

#### Database Performance
```bash
# Query analysis
./vendor/bin/sail artisan tinker
>>> DB::enableQueryLog();
>>> $monitors = Monitor::with('website.team')->get();
>>> DB::getQueryLog();

# Check slow query log
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && tail -n 50 storage/logs/laravel.log | grep 'Slow query'"
```

#### Cache Performance
```bash
# Cache hit rate monitoring
./vendor/bin/sail artisan tinker
>>> Redis::info('stats');

# Test cache effectiveness
time ./vendor/bin/sail artisan tinker --execute="Cache::remember('test', 3600, fn() => Monitor::all());"
time ./vendor/bin/sail artisan tinker --execute="Cache::get('test');"
```

### 2.2 Load Testing (1 hour)

#### Test Scenarios

**Scenario 1: Normal Load** (50 concurrent users)
```bash
# Install Apache Bench if needed
sudo apt-get install apache2-utils

# Homepage load test
ab -n 1000 -c 50 https://monitor.intermedien.at/

# Dashboard load test (authenticated)
ab -n 500 -c 50 -C "laravel_session=YOUR_SESSION_COOKIE" https://monitor.intermedien.at/dashboard

# API endpoints
ab -n 2000 -c 100 https://monitor.intermedien.at/api/health
```

**Scenario 2: Peak Load** (100 concurrent users)
```bash
ab -n 2000 -c 100 https://monitor.intermedien.at/dashboard

# Monitor the server during load test
ssh default_deploy@monitor.intermedien.at "top -bn1 | head -20"
ssh default_deploy@monitor.intermedien.at "cd /var/www/monitor.intermedien.at/web/current && php artisan horizon:status"
```

**Scenario 3: Queue Processing Load**
```bash
# Generate test monitoring jobs
./vendor/bin/sail artisan monitoring:load-test --websites=100 --checks=1000

# Monitor queue depth
watch -n 1 './vendor/bin/sail artisan horizon:status'

# Expected: All jobs processed within 10 minutes
```

#### Performance Metrics Collection

Create `tests/Performance/LoadTest.php`:
```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Monitor;
use App\Models\Website;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class LoadTest extends TestCase
{
    public function test_dashboard_performance_under_load()
    {
        // Create 100 monitors
        $websites = Website::factory(10)->create();
        foreach ($websites as $website) {
            Monitor::factory(10)->for($website)->create();
        }

        $startTime = microtime(true);

        // Simulate 50 concurrent requests
        $responses = [];
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->get('/dashboard');
        }

        $endTime = microtime(true);
        $averageTime = ($endTime - $startTime) / 50;

        // Average response time should be < 200ms
        $this->assertLessThan(0.2, $averageTime,
            "Dashboard average response time: {$averageTime}s exceeds 200ms"
        );
    }

    public function test_query_performance_with_large_dataset()
    {
        // Create 1000 monitoring results
        $monitor = Monitor::factory()->create();

        $startTime = microtime(true);

        // Test query performance
        $results = $monitor->monitoringResults()
            ->whereBetween('checked_at', [now()->subDays(30), now()])
            ->get();

        $queryTime = microtime(true) - $startTime;

        // Query should complete in < 100ms
        $this->assertLessThan(0.1, $queryTime,
            "Query time: {$queryTime}s exceeds 100ms"
        );
    }

    public function test_cache_performance()
    {
        $monitor = Monitor::factory()->create();

        // First call (cache miss)
        $startTime = microtime(true);
        $service = new \App\Services\MonitoringCacheService();
        $result1 = $service->getSummaryStats($monitor, '30d');
        $cacheTime = microtime(true) - $startTime;

        // Second call (cache hit)
        $startTime = microtime(true);
        $result2 = $service->getSummaryStats($monitor, '30d');
        $hitTime = microtime(true) - $startTime;

        // Cache hit should be 10x faster
        $this->assertLessThan($cacheTime / 10, $hitTime,
            "Cache hit time: {$hitTime}s is not significantly faster than cache miss: {$cacheTime}s"
        );
    }
}
```

### 2.3 Performance Documentation (1 hour)

Create `docs/PERFORMANCE_BENCHMARKS.md`:
```markdown
# Performance Benchmarks

**Last Updated**: [Date]
**Environment**: Production (monitor.intermedien.at)

## Baseline Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Dashboard Load Time | < 2s | 200ms | ✅ |
| API Response Time | < 200ms | 150ms | ✅ |
| Database Queries | < 100ms | 45ms | ✅ |
| Queue Processing Rate | > 100/min | 150/min | ✅ |
| Cache Hit Rate | > 70% | 85% | ✅ |
| Test Suite (Parallel) | < 20s | 36.57s | ⚠️ |

## Load Test Results

### Normal Load (50 concurrent users)
```
Requests per second: 125.3 [#/sec]
Time per request: 7.98 [ms] (mean)
Time per request: 399.2 [ms] (mean, across all requests)
Transfer rate: 1250.4 [Kbytes/sec]

Percentage of requests served within:
  50%:   350ms
  66%:   410ms
  75%:   450ms
  80%:   475ms
  90%:   550ms
  95%:   625ms
  98%:   750ms
  99%:   850ms
 100%:  1200ms (longest request)
```

### Peak Load (100 concurrent users)
```
Requests per second: 98.7 [#/sec]
Time per request: 10.13 [ms] (mean)
Time per request: 1013.2 [ms] (mean, across all requests)

Percentage of requests served within:
  50%:   800ms
  90%:  1500ms
  99%:  2500ms
```

## Database Performance

### Most Frequent Queries
1. Monitor dashboard query: 35ms (avg)
2. Website list query: 25ms (avg)
3. Alert history query: 50ms (avg)

### Slow Query Threshold: 1000ms

## Optimization Recommendations

### Immediate (< 1 hour)
- [ ] Enable OPcache in production
- [ ] Optimize Laravel framework caches
- [ ] Enable Redis persistence

### Short-term (1-4 hours)
- [ ] Add database indexes for common queries
- [ ] Implement response caching for API endpoints
- [ ] Optimize Horizon worker configuration

### Long-term (> 4 hours)
- [ ] Implement CDN for static assets
- [ ] Add database read replicas
- [ ] Implement horizontal scaling for queue workers
```

## Success Criteria

### Security Audit Complete
- ✅ All OWASP Top 10 items reviewed
- ✅ Laravel security best practices verified
- ✅ Security policy documented
- ✅ Vulnerability scanning completed
- ✅ No critical security issues found

### Performance Baseline Established
- ✅ Dashboard load time < 2 seconds
- ✅ API response time < 200ms
- ✅ Database queries < 100ms
- ✅ Queue processing > 100 jobs/min
- ✅ Load test results documented
- ✅ Performance benchmarks file created

### Documentation Complete
- ✅ `docs/SECURITY_POLICY.md` created
- ✅ `docs/PERFORMANCE_BENCHMARKS.md` created
- ✅ Security audit checklist completed
- ✅ Optimization recommendations documented

## Verification Commands

### Security Verification
```bash
# Run security audit
./vendor/bin/sail composer audit
./vendor/bin/sail npm audit

# Check for unprotected routes
./vendor/bin/sail artisan route:list | grep -v "auth"

# Review encryption configuration
./vendor/bin/sail artisan tinker --execute="dd(config('app.key'));"

# Verify HTTPS enforcement
curl -I http://monitor.intermedien.at | grep -i location
```

### Performance Verification
```bash
# Run performance tests
./vendor/bin/sail artisan test tests/Performance/

# Check query performance
./vendor/bin/sail artisan monitoring:optimize-queries

# Monitor cache hit rate
./vendor/bin/sail artisan tinker --execute="Redis::info('stats');"

# Load test
ab -n 1000 -c 50 https://monitor.intermedien.at/dashboard
```

## Dependencies

**Requires Completion**:
- Phase 5: Production Optimization (caching, query optimization)

**Enables**:
- Phase 9: UI/UX Refinement (performance-informed design decisions)
- Ongoing security and performance monitoring

## Agent Workflow

### Security Audit (laravel-backend-specialist)
1. Review OWASP Top 10 checklist systematically
2. Run verification commands for each category
3. Document findings in SECURITY_POLICY.md
4. Create remediation plan for any issues found
5. Verify fixes with tests and manual testing

### Performance Benchmarking (performance-optimizer)
1. Establish baseline metrics for all key endpoints
2. Run load tests with varying concurrency levels
3. Analyze database query performance
4. Document results in PERFORMANCE_BENCHMARKS.md
5. Create optimization recommendations

## Timeline

| Task | Duration | Agent |
|------|----------|-------|
| OWASP Top 10 Review | 2 hours | laravel-backend-specialist |
| Laravel Security Best Practices | 1 hour | laravel-backend-specialist |
| Security Documentation | 30 min | laravel-backend-specialist |
| Baseline Metrics | 1 hour | performance-optimizer |
| Load Testing | 1 hour | performance-optimizer |
| Performance Documentation | 1 hour | performance-optimizer |

**Total**: 6-8 hours

## Notes

- Security audit should be performed quarterly
- Performance benchmarks should be updated monthly
- Critical vulnerabilities require immediate remediation
- Performance regressions > 20% require investigation
- Load testing should be performed before major releases

## Related Documentation

- `docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md` - Deployment procedures
- `docs/implementation-finished/PHASE5_PRODUCTION_OPTIMIZATION.md` - Performance optimization details
- `docs/CODING_GUIDE.md` - Development standards
- Laravel Security Documentation: https://laravel.com/docs/security
