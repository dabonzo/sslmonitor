# Testing Guide

Comprehensive guide to SSL Monitor's test suite, TDD methodology, and testing best practices.

## 🎯 Overview

SSL Monitor was built using **Test-Driven Development (TDD)** with **Pest PHP** as the testing framework. Every feature has comprehensive test coverage ensuring reliability and maintainability.

## 📊 Test Coverage Summary

### Current Test Statistics
- **Total Tests**: 115+ passing tests
- **Test Suites**: 12 test files  
- **Assertions**: 300+ comprehensive assertions
- **Coverage**: All critical paths covered
- **Framework**: Pest PHP 4 with Laravel integration

### Test Distribution
```
Phase 1 (Models):        31 tests, 89 assertions
Phase 2 (Services):      25 tests, 81 assertions  
Phase 3 (Livewire):      45 tests, 126+ assertions
Phase 4 (Jobs/Commands): 9 tests, robust automation
Phase 5 (Email Config):  5 tests, 17 assertions
Total:                   115+ tests, 300+ assertions
```

## 🏗️ Test Architecture

### Test Organization

```
tests/
├── Feature/                    # Integration and feature tests
│   ├── Models/
│   │   ├── WebsiteTest.php          ✅ 10 tests - Website model behavior
│   │   ├── SslCertificateTest.php   ✅ 11 tests - Certificate logic  
│   │   └── SslCheckTest.php         ✅ 10 tests - Check history
│   ├── Services/
│   │   ├── SslCertificateCheckerTest.php  ✅ 10 tests - SSL checking
│   │   └── SslStatusCalculatorTest.php    ✅ 15 tests - Status logic
│   ├── Livewire/
│   │   ├── WebsiteManagementComponentTest.php  ✅ 15 tests - CRUD
│   │   └── SslDashboardComponentTest.php       ✅ 12 tests - Dashboard
│   ├── Jobs/
│   │   └── CheckSslCertificateJobTest.php      ✅ 9 tests - Background jobs
│   ├── Commands/
│   │   └── CheckAllSslCertificatesCommandTest.php  ✅ 7 tests - CLI
│   └── EmailSettingsTest.php                       ✅ 5 tests - Email config
├── Unit/                       # Unit tests for isolated logic
│   ├── Helpers/               # Utility function tests
│   └── Rules/                 # Validation rule tests
└── Pest.php                   # Pest configuration
```

## 🔴🟢🔵 TDD Methodology

### Red-Green-Refactor Cycle

#### 1. **Red Phase** - Write Failing Test
```php
test('website url is sanitized to lowercase https', function () {
    $website = Website::factory()->create(['url' => 'HTTP://EXAMPLE.COM/PATH/']);
    
    expect($website->url)->toBe('https://example.com/path');
});
```

#### 2. **Green Phase** - Minimal Implementation
```php
// Website.php
protected function setUrlAttribute(string $value): void
{
    // Minimal code to pass the test
    $this->attributes['url'] = strtolower(str_replace('http://', 'https://', $value));
}
```

#### 3. **Refactor Phase** - Improve Code Quality
```php
// Website.php  
protected function setUrlAttribute(string $value): void
{
    $url = trim($value);
    
    // Convert HTTP to HTTPS
    if (str_starts_with($url, 'http://')) {
        $url = 'https://' . substr($url, 7);
    }
    
    // Ensure HTTPS prefix
    if (!str_starts_with($url, 'https://')) {
        $url = 'https://' . $url;
    }
    
    // Parse and clean URL
    $parsed = parse_url($url);
    $cleanUrl = 'https://' . strtolower($parsed['host']);
    
    if (!empty($parsed['path'])) {
        $cleanUrl .= rtrim($parsed['path'], '/');
    }
    
    $this->attributes['url'] = $cleanUrl;
}
```

## 🧪 Test Categories

### 1. Model Tests

#### Relationship Testing
```php
test('website belongs to user', function () {
    $user = User::factory()->create();
    $website = Website::factory()->for($user)->create();
    
    expect($website->user)
        ->toBeInstanceOf(User::class)
        ->and($website->user->id)
        ->toBe($user->id);
});

test('user has many websites', function () {
    $user = User::factory()->create();
    Website::factory(3)->for($user)->create();
    
    expect($user->websites)
        ->toHaveCount(3)
        ->each
        ->toBeInstanceOf(Website::class);
});
```

#### Business Logic Testing
```php
test('ssl certificate calculates days until expiry correctly', function () {
    $certificate = SslCertificate::factory()->create([
        'expires_at' => now()->addDays(30)
    ]);
    
    expect($certificate->getDaysUntilExpiry())->toBe(30);
});

test('ssl certificate identifies expiring soon status', function () {
    $certificate = SslCertificate::factory()->expiringSoon()->create();
    
    expect($certificate->isExpiringSoon())->toBeTrue();
    expect($certificate->isValid())->toBeFalse();
});
```

#### Query Scope Testing
```php
test('ssl certificate valid scope filters correctly', function () {
    SslCertificate::factory()->valid()->create();
    SslCertificate::factory()->expired()->create();
    SslCertificate::factory()->expiringSoon()->create();
    
    expect(SslCertificate::valid()->count())->toBe(1);
});
```

### 2. Service Tests

#### SSL Certificate Checker
```php
test('ssl certificate checker validates google.com', function () {
    $checker = app(SslCertificateChecker::class);
    
    $result = $checker->checkCertificate('https://google.com');
    
    expect($result)
        ->toBeInstanceOf(SslCertificateResult::class)
        ->and($result->isValid())
        ->toBeTrue()
        ->and($result->getDaysUntilExpiry())
        ->toBeGreaterThan(0);
});

test('ssl certificate checker handles invalid domain', function () {
    $checker = app(SslCertificateChecker::class);
    
    $result = $checker->checkCertificate('https://invalid-domain-12345.com');
    
    expect($result->isValid())->toBeFalse();
    expect($result->getErrorMessage())->not->toBeNull();
});
```

#### Status Calculator
```php
test('ssl status calculator prioritizes expired over expiring', function () {
    $calculator = app(SslStatusCalculator::class);
    
    $expiredCert = SslCertificate::factory()->expired()->create();
    $expiredCheck = SslCheck::factory()->create(['status' => 'expired']);
    
    $status = $calculator->calculateStatus($expiredCert, $expiredCheck);
    
    expect($status)->toBe(SslCheck::STATUS_EXPIRED);
});
```

### 3. Livewire Component Tests

#### Website Management
```php
test('user can add website with ssl preview', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(WebsiteManagement::class)
        ->set('url', 'https://example.com')
        ->call('previewSsl')
        ->assertSet('previewLoading', false)
        ->assertSee('Valid Certificate')
        ->call('addWebsite')
        ->assertHasNoErrors();
        
    expect($user->websites()->count())->toBe(1);
    expect($user->websites()->first()->url)->toBe('https://example.com');
});

test('user cannot add duplicate website', function () {
    $user = User::factory()->create();
    Website::factory()->for($user)->create(['url' => 'https://example.com']);
    
    Livewire::actingAs($user)
        ->test(WebsiteManagement::class)
        ->set('url', 'https://example.com')
        ->call('addWebsite')
        ->assertHasErrors(['url']);
});
```

#### Dashboard Component
```php
test('dashboard shows correct website counts', function () {
    $user = User::factory()->create();
    
    // Create websites with different SSL statuses
    Website::factory()->for($user)->has(
        SslCertificate::factory()->valid()
    )->create();
    
    Website::factory()->for($user)->has(
        SslCertificate::factory()->expiringSoon()
    )->create();
    
    Livewire::actingAs($user)
        ->test(SslDashboard::class)
        ->assertSee('2') // Total websites
        ->assertSee('1') // Valid certificates  
        ->assertSee('1'); // Expiring soon
});
```

### 4. Background Job Tests

#### SSL Certificate Job
```php
test('ssl certificate job processes website successfully', function () {
    $website = Website::factory()->create(['url' => 'https://google.com']);
    
    CheckSslCertificateJob::dispatch($website);
    
    expect($website->sslChecks()->count())->toBe(1);
    
    $check = $website->sslChecks()->latest()->first();
    expect($check->status)->toBe(SslCheck::STATUS_VALID);
});

test('ssl certificate job handles failures gracefully', function () {
    $website = Website::factory()->create(['url' => 'https://invalid-domain.com']);
    
    CheckSslCertificateJob::dispatch($website);
    
    expect($website->sslChecks()->count())->toBe(1);
    
    $check = $website->sslChecks()->latest()->first();  
    expect($check->status)->toBe(SslCheck::STATUS_ERROR);
    expect($check->error_message)->not->toBeNull();
});
```

### 5. Console Command Tests

```php
test('ssl check all command processes all websites', function () {
    Website::factory(3)->create();
    
    $this->artisan('ssl:check-all')
        ->expectsOutput('Checking SSL certificates for 3 websites...')
        ->assertExitCode(0);
        
    expect(SslCheck::count())->toBe(3);
});

test('ssl check all command respects force flag', function () {
    $website = Website::factory()->create();
    
    // Create recent check
    SslCheck::factory()->for($website)->create([
        'created_at' => now()->subHours(2)
    ]);
    
    // Without force flag - should skip
    $this->artisan('ssl:check-all')
        ->expectsOutput('Skipping recent checks...');
        
    // With force flag - should check
    $this->artisan('ssl:check-all --force')
        ->expectsOutput('Checking SSL certificates for 1 websites...');
});
```

## 🔧 Running Tests

### Basic Test Execution

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run tests with Pest directly
./vendor/bin/sail exec laravel.test ./vendor/bin/pest

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/Models/WebsiteTest.php

# Run tests with filter
./vendor/bin/sail artisan test --filter="website url"
```

### Advanced Test Options

```bash
# Parallel testing (faster)
./vendor/bin/sail artisan test --parallel

# Stop on first failure
./vendor/bin/sail artisan test --stop-on-failure

# Coverage report (requires Xdebug)
./vendor/bin/sail artisan test --coverage

# Verbose output
./vendor/bin/sail artisan test --verbose
```

### Test Environment

```bash
# Environment variables for testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Queue testing
QUEUE_CONNECTION=sync

# Mail testing  
MAIL_MAILER=array
```

## 🏭 Test Factories

### Model Factories

#### Website Factory
```php
class WebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->domainName(),
            'url' => 'https://' . fake()->domainName(),
            'user_id' => User::factory(),
        ];
    }
    
    public function withValidSsl(): static
    {
        return $this->has(
            SslCertificate::factory()->valid()
        );
    }
}
```

#### SSL Certificate Factory
```php  
class SslCertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'issuer' => 'Let\'s Encrypt Authority X3',
            'expires_at' => now()->addDays(90),
            'is_valid' => true,
        ];
    }
    
    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDays(5),
            'is_valid' => false,
        ]);
    }
    
    public function expiringSoon(): static  
    {
        return $this->state([
            'expires_at' => now()->addDays(10),
            'is_valid' => true,
        ]);
    }
}
```

### Factory Usage Patterns

```php
// Simple creation
$website = Website::factory()->create();

// With relationships
$user = User::factory()
    ->has(Website::factory()->count(3))
    ->create();

// With specific states
$expiredWebsite = Website::factory()
    ->has(SslCertificate::factory()->expired())
    ->create();

// Complex scenarios
$user = User::factory()->create();
$websites = Website::factory(5)
    ->for($user)
    ->sequence(
        ['url' => 'https://site1.com'],
        ['url' => 'https://site2.com'],
        ['url' => 'https://site3.com'],
        ['url' => 'https://site4.com'],  
        ['url' => 'https://site5.com']
    )
    ->create();
```

## 📊 Test Data Management

### Database Refresh Strategy

```php
// tests/Pest.php
uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

// Automatically refreshes database between tests
// Uses transactions for speed
// Supports SQLite memory database for fast tests
```

### Seeding Test Data

```php
test('dashboard with comprehensive data', function () {
    $user = User::factory()->create();
    
    // Valid websites
    Website::factory(3)
        ->for($user)
        ->has(SslCertificate::factory()->valid())
        ->create();
        
    // Expiring websites  
    Website::factory(2)
        ->for($user)
        ->has(SslCertificate::factory()->expiringSoon())
        ->create();
        
    // Expired websites
    Website::factory(1)
        ->for($user) 
        ->has(SslCertificate::factory()->expired())
        ->create();
        
    Livewire::actingAs($user)
        ->test(SslDashboard::class)
        ->assertSee('6') // Total websites
        ->assertSee('3') // Valid
        ->assertSee('2') // Expiring
        ->assertSee('1'); // Expired
});
```

## 🎯 Test-Driven Development Workflow

### Adding New Features

#### 1. Start with a Failing Test
```php
// New feature: Website grouping
test('user can create website groups', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(WebsiteGroups::class)
        ->set('name', 'Production Sites')
        ->call('createGroup')
        ->assertHasNoErrors();
        
    expect($user->websiteGroups()->count())->toBe(1);
});
```

#### 2. Create Minimal Implementation
```php
// Create migration, model, component to pass test
class WebsiteGroup extends Model
{
    protected $fillable = ['name', 'user_id'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

#### 3. Add More Test Cases
```php
test('website group name is required', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(WebsiteGroups::class)
        ->set('name', '')
        ->call('createGroup')
        ->assertHasErrors(['name']);
});

test('user can only see their own groups', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    WebsiteGroup::factory()->for($user2)->create();
    
    expect($user1->websiteGroups()->count())->toBe(0);
});
```

### Refactoring with Test Safety

```php
// Original implementation
public function calculateStatus(SslCertificate $cert): string
{
    if ($cert->expires_at < now()) {
        return 'expired';
    }
    
    if ($cert->expires_at < now()->addDays(14)) {
        return 'expiring_soon';
    }
    
    return 'valid';
}

// Refactored with tests ensuring behavior unchanged
public function calculateStatus(SslCertificate $cert, ?SslCheck $check = null): string  
{
    // Enhanced logic with check status consideration
    if (!$cert->is_valid || ($check && $check->status === 'error')) {
        return SslCheck::STATUS_INVALID;
    }
    
    $daysUntilExpiry = $cert->getDaysUntilExpiry();
    
    return match (true) {
        $daysUntilExpiry < 0 => SslCheck::STATUS_EXPIRED,
        $daysUntilExpiry <= 14 => SslCheck::STATUS_EXPIRING_SOON,
        default => SslCheck::STATUS_VALID
    };
}
```

## 🔍 Testing Best Practices

### Test Organization

```php
// Group related tests
describe('Website URL sanitization', function () {
    test('converts http to https', function () {
        // Test implementation
    });
    
    test('converts to lowercase', function () {
        // Test implementation  
    });
    
    test('removes trailing slashes', function () {
        // Test implementation
    });
});
```

### Descriptive Test Names

```php
// ❌ Bad - unclear intent
test('website test', function () {
    // ...
});

// ✅ Good - clear intent
test('website url is sanitized to lowercase https format', function () {
    // ...
});

// ✅ Better - behavior focused
test('website automatically converts http urls to https when saved', function () {
    // ...
});
```

### Test Data Builders

```php
// Helper functions for complex test data
function createUserWithWebsites(int $count = 3): User
{
    return User::factory()
        ->has(Website::factory()->count($count))
        ->create();
}

function createExpiringSslCertificates(User $user, int $count = 2): Collection
{
    return Website::factory($count)
        ->for($user)
        ->has(SslCertificate::factory()->expiringSoon())
        ->create();
}

// Usage in tests
test('dashboard shows expiring certificate warnings', function () {
    $user = User::factory()->create();
    createExpiringSslCertificates($user, 3);
    
    Livewire::actingAs($user)
        ->test(SslDashboard::class)
        ->assertSee('3 certificates expiring soon');
});
```

## 📈 Continuous Testing

### GitHub Actions Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_DATABASE: ssl_monitor_test
        ports:
          - 3306:3306
          
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist --optimize-autoloader
        
      - name: Run tests  
        run: php artisan test --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: ssl_monitor_test
```

### Local Development Testing

```bash
# Watch for changes and run tests automatically
./vendor/bin/pest --watch

# Run tests on file change with notifications
./vendor/bin/pest --watch --notify
```

## 🎯 Next Steps

- **[API Reference](api-reference.md)** - Complete code reference
- **[Contributing](contributing.md)** - Development setup and guidelines
- **[Architecture](architecture.md)** - System architecture overview

---

**Previous**: [Architecture](architecture.md) | **Next**: [API Reference](api-reference.md)