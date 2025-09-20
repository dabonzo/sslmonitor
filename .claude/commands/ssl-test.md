# /ssl-test - SSL Testing Assistant

**Purpose**: Run SSL-specific tests and create comprehensive SSL monitoring test suites.

**Usage**: `/ssl-test [test-focus] [description]`

## SSL Testing Workflow

Please run SSL tests or create SSL test suite for: **$ARGUMENTS**.

**Context**: Validate SSL functionality using Pest v4 with existing backend services.

Follow these steps:

### 1. Test Environment Setup
```bash
# Check current test environment
./vendor/bin/sail artisan test --list

# Verify SSL services are available
filesystem-mcp: read-file app/Services/SslCertificateChecker.php
filesystem-mcp: read-file tests/Feature/Services/SslCertificateCheckerTest.php

# Check test database state
./vendor/bin/sail artisan migrate:fresh --env=testing
./vendor/bin/sail artisan db:seed --env=testing
```

### 2. Run Existing SSL Tests
```bash
# Run all SSL-related tests
./vendor/bin/sail artisan test --filter=Ssl

# Run specific SSL service tests
./vendor/bin/sail artisan test --filter=SslCertificateChecker

# Run website-related tests
./vendor/bin/sail artisan test --filter=Website

# Check for any failing tests
last-error
```

### 3. Create New SSL Test Suite
```bash
# Create comprehensive SSL test files based on focus area:

# Feature Tests
./vendor/bin/sail artisan make:test --pest Feature/Ssl${ARGUMENTS}Test

# Browser Tests (if UI testing needed)
./vendor/bin/sail artisan make:test --pest Browser/Ssl${ARGUMENTS}BrowserTest

# Unit Tests (if service testing needed)
./vendor/bin/sail artisan make:test --pest Unit/Ssl${ARGUMENTS}Test
```

### 4. SSL Feature Testing Patterns
```bash
# Test SSL certificate validation:
# - Valid certificates (GitHub.com example)
# - Expired certificates (simulation)
# - Invalid certificates (self-signed)
# - Network timeouts and errors
# - Wildcard certificate handling

# Test Website model integration:
# - URL sanitization and normalization
# - SSL monitoring enabled/disabled states
# - Relationship queries (certificates, checks)
# - Plugin data handling

# Test Inertia.js controllers:
# - Correct component rendering
# - Proper data structure in props
# - Authentication and authorization
# - Error handling and validation
```

### 5. Browser Test Implementation
```bash
# Test SSL monitoring user workflows:
# - Add new website for monitoring
# - View SSL certificate status
# - Manual certificate checks
# - Dashboard SSL statistics
# - Mobile responsive behavior

# Test real-time updates:
# - Certificate status changes
# - Notification handling
# - Error state display
```

### 6. Mock SSL Testing
```bash
# Create SSL test scenarios:
# - Mock certificate data for consistent testing
# - Simulate network failures
# - Test certificate expiry calculations
# - Validate status determination logic

# Use Laravel's HTTP client mocking for external calls
# Create factory data for consistent test datasets
```

### 7. Integration Testing
```bash
# Test SSL monitoring pipeline:
# - Website creation triggers monitoring setup
# - Background jobs process correctly
# - Certificate checks store results properly
# - Notifications send appropriately

# Test Spatie monitor integration:
# - Website model syncs with monitors
# - SSL checks work with uptime monitoring
# - Hybrid monitoring functions correctly
```

### 8. Performance Testing
```bash
# Test SSL monitoring performance:
# - Multiple simultaneous certificate checks
# - Large datasets with pagination
# - Database query optimization
# - Background job processing efficiency

# Monitor memory usage and execution time
```

### 9. Error Scenario Testing
```bash
# Test error handling:
# - SSL connection failures
# - Invalid certificates
# - Network timeouts
# - Database connection issues
# - Queue processing failures

# Validate graceful degradation
```

## Testing Standards

### Feature Test Pattern
```php
it('can check SSL certificate status', function () {
    $website = Website::factory()->create([
        'url' => 'https://github.com',
        'ssl_monitoring_enabled' => true,
    ]);

    $checker = app(SslCertificateChecker::class);
    $result = $checker->check($website->url);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('valid')
        ->and($result['expires_at'])->not->toBeNull();
});
```

### Browser Test Pattern
```php
it('displays SSL monitoring dashboard', function () {
    $user = User::factory()->create();
    $websites = Website::factory(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->visit('/dashboard')
        ->assertSee('SSL Certificate Status')
        ->assertSee('Total Certificates')
        ->assertNoJavascriptErrors();
});
```

### Mock SSL Data Pattern
```php
it('handles SSL certificate expiry correctly', function () {
    Http::fake([
        '*' => Http::response([
            'certificate' => [
                'expires_at' => now()->addDays(7)->toDateString(),
                'status' => 'expiring',
            ]
        ])
    ]);

    $website = Website::factory()->create();
    $result = app(SslCertificateChecker::class)->check($website->url);

    expect($result['status'])->toBe('expiring');
});
```

## Test Coverage Areas

### SSL Service Testing
- **Certificate Validation**: Valid, expired, invalid certificates
- **Network Handling**: Timeouts, connection failures, DNS issues
- **Data Processing**: Certificate parsing, expiry calculation
- **Error Recovery**: Graceful failure handling

### Controller Testing
- **Inertia Responses**: Correct component and data structure
- **Authentication**: Route protection and user authorization
- **Form Validation**: Website creation and SSL settings
- **Error Handling**: Network failures and validation errors

### Browser Testing
- **User Workflows**: Complete SSL monitoring workflows
- **Real-time Updates**: Certificate status changes
- **Mobile Experience**: Responsive design functionality
- **Error States**: User-friendly error displays

### Integration Testing
- **Website-Monitor Sync**: Automatic monitor creation/updates
- **Background Processing**: Job queue and SSL checking
- **Database Relationships**: Model associations and queries
- **Notification System**: Alert triggering and delivery

## Test Execution Commands

```bash
# Run specific SSL test suites
./vendor/bin/sail artisan test --filter=SslCertificate
./vendor/bin/sail artisan test --filter=Website
./vendor/bin/sail artisan test tests/Feature/Services/
./vendor/bin/sail artisan test tests/Browser/

# Run tests with coverage
./vendor/bin/sail artisan test --coverage

# Run performance profiling
./vendor/bin/sail artisan test --profile

# Debug specific test
./vendor/bin/sail artisan test --filter=specific_test_name --debug
```

## Success Criteria
1. All SSL tests pass consistently
2. Edge cases are properly covered
3. Error scenarios are handled gracefully
4. Browser tests validate user workflows
5. Performance remains acceptable
6. Test coverage is comprehensive

**Ready to validate SSL monitoring with comprehensive testing!** 🧪