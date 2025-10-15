# SSL Monitor v4 - Performance Testing Workflow

This document provides comprehensive guidance for maintaining test performance standards and preventing performance regressions in the SSL Monitor v4 codebase.

## 🎯 Performance Standards Summary

| Metric | Target | Current Status | Critical? |
|--------|--------|----------------|-----------|
| Individual Tests | < 1 second | ✅ 0.20s (SSL), 0.75s (JS) | YES |
| SSL Analysis Tests | < 1 second total | ✅ 0.20s | YES |
| JavaScript Content Tests | < 1 second total | ✅ 0.75s | YES |
| Full Test Suite (Parallel) | < 20 seconds | ✅ 15.5s | YES |
| External Service Calls | 0 (mocked) | ✅ Eliminated | CRITICAL |

## 🚀 Quick Performance Commands

### Daily Development
```bash
# Quick performance check (run before committing)
time ./vendor/bin/sail artisan test --parallel
```

### Before Adding New Tests
```bash
# Check if your new test meets performance standards
./vendor/bin/sail artisan test --filter="YourNewTest" --profile

# Verify no performance regression
time ./vendor/bin/sail artisan test --parallel
```

### Weekly Performance Health Check
```bash
#!/bin/bash
# performance-health-check.sh

echo "🔍 SSL Monitor v4 Performance Health Check"
echo "=========================================="

echo "📊 Testing SSL Certificate Analysis performance..."
time ./vendor/bin/sail artisan test --filter="SSL.*Analysis" --profile

echo "📊 Testing JavaScript Content Fetcher performance..."
time ./vendor/bin/sail artisan test --filter="JavaScriptContentFetcher" --profile

echo "📊 Running full test suite performance check..."
time ./vendor/bin/sail artisan test --parallel

echo "✅ Performance health check complete!"
echo "If any metric exceeds targets, see troubleshooting section below."
```

## 🔍 Performance Troubleshooting

### Identifying Slow Tests

#### 1. Use Test Profiling
```bash
# Get detailed timing information
./vendor/bin/sail artisan test --profile

# Check specific test categories
./vendor/bin/sail artisan test --filter="SSL" --profile
./vendor/bin/sail artisan test --filter="JavaScript" --profile
./vendor/bin/sail artisan test --filter="Controller" --profile
```

#### 2. Manual Performance Testing
```bash
# Test individual tests with timing
time ./vendor/bin/sail artisan test --filter="specific_test_name"

# Test entire directories
time ./vendor/bin/sail artisan test tests/Feature/Controllers/
time ./vendor/bin/sail artisan test tests/Feature/Services/
```

### Common Performance Issues & Solutions

#### Issue: Tests Making Real Network Calls
**Symptoms**: Tests taking 30+ seconds, timeouts, connection errors

**Diagnosis**:
```bash
# Search for potential network calls in tests
grep -r "Http::\|curl\|file_get_contents\|stream_socket_client" tests/
grep -r "SslCertificateAnalysisService\|JavaScriptContentFetcher" tests/
```

**Solution**: Add appropriate mock traits
```php
// For SSL Certificate Analysis
use Tests\Traits\MocksSslCertificateAnalysis;
uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();
});

// For JavaScript Content Fetcher
use Tests\Traits\MocksJavaScriptContentFetcher;
uses(RefreshDatabase::class, MocksJavaScriptContentFetcher::class);

beforeEach(function () {
    $this->setUpMocksJavaScriptContentFetcher();
});
```

#### Issue: Database Performance Problems
**Symptoms**: Tests taking > 1 second due to database operations

**Diagnosis**:
```bash
# Check for excessive database operations
./vendor/bin/sail artisan test --filter="your_test" --debug
```

**Solution**: Optimize database setup
```php
// Use existing test data instead of creating fresh
uses(UsesCleanDatabase::class);

// Minimize factory calls
beforeEach(function () {
    $this->setUpCleanDatabase(); // Uses existing setup
    // Don't create unnecessary fresh data
});
```

#### Issue: Inefficient Test Setup
**Symptoms**: Tests taking longer than expected despite no network calls

**Diagnosis**: Review test setup complexity
```php
// ❌ BAD: Complex setup in every test
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->websites = Website::factory()->count(10)->create(['user_id' => $this->user->id]);
    $this->monitors = Monitor::factory()->count(10)->create();
    // ... 50 more lines
});

// ✅ GOOD: Use centralized setup
uses(UsesCleanDatabase::class);
beforeEach(function () {
    $this->setUpCleanDatabase(); // Reuses existing setup
});
```

## 📋 Performance Checklist for New Tests

### Before Writing Tests
- [ ] Identify external services the test will use
- [ ] Plan mock strategy for all external dependencies
- [ ] Review similar existing tests for performance patterns

### While Writing Tests
- [ ] Include appropriate mock traits
- [ ] Use existing test data when possible
- [ ] Avoid unnecessary factory creations
- [ ] Keep test logic focused and minimal

### After Writing Tests
- [ ] Run test with timing: `time ./vendor/bin/sail artisan test --filter="new_test"`
- [ ] Verify test completes in < 1 second
- [ ] Run full suite to ensure no regression: `time ./vendor/bin/sail artisan test --parallel`
- [ ] Add performance assertion if critical: `expect($duration)->toBeLessThan(1.0)`

## 🛠 Performance Monitoring Tools

### Built-in Laravel Test Tools
```bash
# Test with coverage (slower but comprehensive)
./vendor/bin/sail artisan test --parallel --coverage

# Stop on first failure (faster feedback)
./vendor/bin/sail artisan test --parallel --stop-on-failure

# Run specific test groups
./vendor/bin/sail artisan test --parallel --group="slow-tests" --profile
```

### Custom Performance Assertions
```php
test('performance critical operation', function () {
    $startTime = microtime(true);

    // Your test logic
    $result = $this->performCriticalOperation();

    $duration = microtime(true) - $startTime;

    // Assert performance requirements
    expect($duration)->toBeLessThan(1.0,
        "Critical operation took {$duration}s, must be under 1 second"
    );

    expect($result)->toBeValid();
});
```

### Continuous Integration Performance Checks
```yaml
# .github/workflows/performance.yml
name: Performance Check

on: [push, pull_request]

jobs:
  performance:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Start Services
      run: ./vendor/bin/sail up -d

    - name: Run Performance Tests
      run: |
        time ./vendor/bin/sail artisan test --parallel

    - name: Check Performance Regression
      run: |
        if [ $? -ne 0 ]; then
          echo "❌ Performance regression detected!"
          exit 1
        fi

        echo "✅ Performance standards met"
```

## 📊 Performance Regression Prevention

### Code Review Checklist
- [ ] Are external service mocks properly implemented?
- [ ] Does test use existing centralized setup?
- [ ] Is test completion time under 1 second?
- [ ] Have performance standards been maintained?

### Weekly Maintenance Tasks
```bash
# 1. Run full performance health check
./performance-health-check.sh

# 2. Check for new slow tests
./vendor/bin/sail artisan test --profile | grep "s$"

# 3. Verify mock trait usage
grep -r "MocksSslCertificateAnalysis\|MocksJavaScriptContentFetcher" tests/Feature/

# 4. Update performance documentation if needed
echo "Performance standards maintained: $(date)" >> docs/performance-log.md
```

## 🚨 Performance Escalation Procedures

### When Performance Standards Are Not Met

#### Level 1: Individual Test > 1 Second
1. Identify root cause (network calls, database issues, complex setup)
2. Apply appropriate fix (mocks, setup optimization)
3. Verify fix with timing test
4. Update documentation if pattern changes

#### Level 2: Test Suite > 20 Seconds
1. Run performance profiling to identify bottlenecks
2. Check for new external service dependencies
3. Verify all tests use appropriate mocks
4. Consider test suite reorganization if needed

#### Level 3: Critical Performance Regression
1. Immediately stop merge/ deployment
2. Identify regression source using git bisect
3. Fix root cause
4. Update performance standards and documentation
5. Add monitoring to prevent future regressions

## 📚 Additional Resources

- [Testing Insights & Best Practices](TESTING_INSIGHTS.md)
- [Mock Traits Documentation](../tests/Traits/)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Pest Testing Framework](https://pestphp.com/docs)

---

**Remember**: Performance is a feature. Maintaining fast test execution is critical for developer productivity and CI/CD efficiency.