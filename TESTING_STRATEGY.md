# Testing Strategy for SSL Monitor v3

## 🎯 Testing Philosophy

SSL Monitor v3 follows a comprehensive testing approach that ensures reliability, performance, and user experience excellence. Our testing strategy is built on **Test-Driven Development (TDD)** principles with modern testing tools.

**Core Testing Principles:**
- **Test-First Development**: Write tests before implementation
- **Comprehensive Coverage**: Unit, Feature, and Browser testing
- **Visual Validation**: Screenshot-based UI testing
- **Performance Testing**: Load and stress testing
- **Security Testing**: Vulnerability and penetration testing

---

## 🧪 Testing Stack

### Backend Testing
- **Pest v4**: Modern PHP testing framework with expressive syntax
- **Laravel Testing Utilities**: Built-in testing helpers and assertions
- **Database Testing**: In-memory SQLite for fast test execution
- **HTTP Testing**: Request/response testing with Laravel's HTTP client

### Frontend Testing
- **Vitest**: Fast Vue.js unit testing
- **Vue Test Utils**: Official Vue.js testing utilities
- **Playwright**: Cross-browser end-to-end testing with real database
- **Visual Regression**: Screenshot comparison testing
- **Browser Automation**: Real user interaction testing

### Integration Testing
- **Playwright Browser Tests**: Full application workflow testing
- **API Testing**: RESTful endpoint validation
- **Database Integration**: Real database scenario testing
- **Email Testing**: Mail sending and template validation

---

## 🎭 Playwright Browser Testing with Real Database

### Overview
SSL Monitor v3 uses **Playwright for comprehensive browser testing with the real development database**. This approach provides:

- **Real User Interactions**: Actual form submissions, navigation, and workflows
- **Visual Validation**: Screenshot-based UI testing across devices
- **Performance Monitoring**: Real-time page load and interaction testing
- **Cross-Browser Compatibility**: Chromium, Firefox, and WebKit support
- **Development Database**: Test with actual data without complex mocking

### Playwright Test Structure
```javascript
// run-comprehensive-test.js - Main test execution script
import { chromium } from 'playwright';

async function runComprehensiveUserFlow() {
    console.log('🚀 Starting comprehensive SSL Monitor v3 user flow test...');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Test data for registration and login
    const TEST_USER = {
        name: 'SSL Monitor Test User',
        email: 'testuser@sslmonitor.test',
        password: 'SecurePassword123!'
    };

    try {
        // Step 1: Test landing page (login)
        await page.goto('http://localhost', { waitUntil: 'networkidle' });
        const loginTitle = await page.textContent('h1');
        console.log(`✅ Login page loaded: "${loginTitle}"`);

        // Step 2: Navigation testing
        await page.click('a[href="/register"]');
        await page.waitForLoadState('networkidle');

        // Step 3: Form interaction testing
        await page.fill('input[id="name"]', TEST_USER.name);
        await page.fill('input[id="email"]', TEST_USER.email);
        await page.fill('input[id="password"]', TEST_USER.password);

        // Step 4: Responsive design testing
        await page.setViewportSize({ width: 375, height: 667 }); // Mobile
        await page.setViewportSize({ width: 768, height: 1024 }); // Tablet

        // Step 5: Interactive element testing
        const passwordInput = page.locator('input[id="password"]');
        const toggleButton = page.locator('button[type="button"]').last();
        await toggleButton.click(); // Test password visibility toggle

        // Generate comprehensive screenshots
        await page.screenshot({
            path: 'tests/Browser/screenshots/comprehensive-test.png',
            fullPage: true
        });

    } catch (error) {
        console.error('💥 Test failed:', error.message);
        await page.screenshot({
            path: 'tests/Browser/screenshots/error-screenshot.png',
            fullPage: true
        });
    } finally {
        await browser.close();
    }
}
```

### Playwright Test Execution
```bash
# Install Playwright browsers (one-time setup)
npx playwright install

# Run comprehensive browser test
node run-comprehensive-test.js

# Run Playwright test suite (advanced)
npx playwright test tests/Browser/ComprehensiveUserFlowTest.js

# Run with UI mode for debugging
npx playwright test --ui
```

### Test Results & Screenshots
The Playwright tests generate comprehensive screenshots saved to `tests/Browser/screenshots/`:

- **Landing Page**: Login page with proper styling and branding
- **Registration Flow**: Complete user registration form interaction
- **Navigation Testing**: Links and routing between authentication pages
- **Responsive Design**: Mobile, tablet, and desktop layout validation
- **Interactive Elements**: Password visibility toggle, form submissions
- **Error Scenarios**: Validation and error handling

### Real Database Integration Benefits
Using the development database for testing provides:

1. **Realistic Data**: Test with actual models and relationships
2. **No Mocking Complexity**: Avoid complex mock setups for database interactions
3. **End-to-End Validation**: Complete request/response cycles through real database
4. **Development Safety**: Development server data isn't critical, safe for testing
5. **Quick Iteration**: Immediate feedback on real application behavior

### Browser Testing Workflow
```bash
# 1. Ensure development environment is running
./vendor/bin/sail up -d
./vendor/bin/sail npm run build  # Build assets for production-like testing

# 2. Install Playwright browsers (if not already installed)
npx playwright install

# 3. Run comprehensive browser test
node run-comprehensive-test.js

# 4. Review screenshots and test results
ls -la tests/Browser/screenshots/

# 5. Debug issues by viewing screenshots
open tests/Browser/screenshots/error-screenshot.png  # If tests fail
```

### Advanced Playwright Features
```javascript
// Cross-browser testing
test.describe('Cross-browser compatibility', () => {
    test('should work in different browsers', async ({ page, browserName }) => {
        console.log(`🌐 Testing in ${browserName}...`);

        await page.goto('/');
        await page.waitForLoadState('networkidle');

        await page.screenshot({
            path: `tests/Browser/screenshots/cross-browser-${browserName}.png`,
            fullPage: true
        });
    });
});

// Performance monitoring
test('should load pages within performance thresholds', async ({ page }) => {
    const startTime = Date.now();
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;

    expect(loadTime).toBeLessThan(5000); // 5 second threshold
});

// Mobile device simulation
test('should work on mobile devices', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 }); // iPhone 8
    await page.goto('/');

    // Test mobile-specific interactions
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('input[type="email"]')).toBeVisible();
});
```

---

## 📊 Test Pyramid Structure

### Level 1: Unit Tests (40% of total tests)
**Target**: ~80 unit tests
**Focus**: Individual classes, methods, and functions
**Speed**: Very fast (<1ms per test)

#### Coverage Areas:
- **Service Classes**: Business logic validation
- **Model Methods**: Eloquent model behavior
- **Value Objects**: Data transformation and validation
- **Utility Functions**: Helper methods and formatters
- **Vue Components**: Component behavior and rendering

#### Example Unit Tests:
```php
<?php
// tests/Unit/Services/SslCertificateCheckerTest.php

use App\Services\SslCertificateChecker;
use App\Services\SslCheckResult;
use Spatie\SslCertificate\SslCertificate;

test('ssl certificate checker validates valid certificate', function () {
    $checker = app(SslCertificateChecker::class);

    // Mock the Spatie SSL certificate
    $mockCertificate = Mockery::mock(SslCertificate::class);
    $mockCertificate->shouldReceive('isValid')->andReturn(true);
    $mockCertificate->shouldReceive('isExpired')->andReturn(false);
    $mockCertificate->shouldReceive('daysUntilExpirationDate')->andReturn(30);
    $mockCertificate->shouldReceive('getIssuer')->andReturn('Let\'s Encrypt');

    $result = $checker->processCheck($mockCertificate);

    expect($result)
        ->toBeInstanceOf(SslCheckResult::class)
        ->and($result->status)->toBe('valid')
        ->and($result->isValid)->toBeTrue()
        ->and($result->daysUntilExpiry)->toBe(30)
        ->and($result->issuer)->toBe('Let\'s Encrypt');
});

test('ssl certificate checker handles expired certificate', function () {
    $checker = app(SslCertificateChecker::class);

    $mockCertificate = Mockery::mock(SslCertificate::class);
    $mockCertificate->shouldReceive('isValid')->andReturn(false);
    $mockCertificate->shouldReceive('isExpired')->andReturn(true);
    $mockCertificate->shouldReceive('daysUntilExpirationDate')->andReturn(-5);

    $result = $checker->processCheck($mockCertificate);

    expect($result->status)->toBe('expired')
        ->and($result->isValid)->toBeFalse()
        ->and($result->daysUntilExpiry)->toBe(-5);
});
```

```javascript
// tests/frontend/unit/components/WebsiteCard.test.js
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import WebsiteCard from '@/Components/WebsiteCard.vue';

describe('WebsiteCard', () => {
    it('displays website information correctly', () => {
        const website = {
            id: 1,
            name: 'Test Website',
            url: 'https://example.com',
            ssl_status: 'valid',
            days_until_expiry: 30,
            last_checked_at: '2024-01-15T10:00:00Z'
        };

        const wrapper = mount(WebsiteCard, {
            props: { website }
        });

        expect(wrapper.find('.website-name').text()).toBe('Test Website');
        expect(wrapper.find('.website-url').text()).toBe('https://example.com');
        expect(wrapper.find('.ssl-status').classes()).toContain('status-valid');
    });

    it('shows correct status badge for expiring certificate', () => {
        const website = {
            id: 1,
            name: 'Expiring Site',
            ssl_status: 'expiring_soon',
            days_until_expiry: 7
        };

        const wrapper = mount(WebsiteCard, {
            props: { website }
        });

        expect(wrapper.find('.ssl-status').classes()).toContain('status-expiring');
        expect(wrapper.find('.expiry-warning').text()).toContain('7 days');
    });
});
```

### Level 2: Feature Tests (35% of total tests)
**Target**: ~70 feature tests
**Focus**: API endpoints, form submissions, workflows
**Speed**: Fast (~10-50ms per test)

#### Coverage Areas:
- **HTTP Endpoints**: All routes and controllers
- **Authentication**: Login, registration, password reset
- **CRUD Operations**: Website management, team management
- **Business Workflows**: SSL checking, notifications
- **Database Interactions**: Model relationships, queries

#### Example Feature Tests:
```php
<?php
// tests/Feature/WebsiteManagementTest.php

use App\Models\User;
use App\Models\Website;
use App\Jobs\CheckSslCertificateJob;
use Illuminate\Support\Facades\Queue;

test('authenticated user can create website', function () {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => 'Test Website',
            'url' => 'https://example.com',
        ]);

    $response->assertRedirect(route('websites.index'))
        ->assertSessionHas('success', 'Website added successfully. SSL check is in progress.');

    expect(Website::where('url', 'https://example.com')->exists())->toBeTrue();

    Queue::assertPushed(CheckSslCertificateJob::class);
});

test('website creation validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => '',
            'url' => 'invalid-url',
        ]);

    $response->assertSessionHasErrors(['name', 'url']);
    expect(Website::count())->toBe(0);
});

test('user can only view their own websites', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $website1 = Website::factory()->for($user1)->create();
    $website2 = Website::factory()->for($user2)->create();

    $response = $this->actingAs($user1)->get('/websites');

    $response->assertInertia(fn ($page) =>
        $page->component('Websites/Index')
            ->has('websites', 1)
            ->where('websites.0.id', $website1->id)
    );
});

test('ssl certificate check job processes correctly', function () {
    Http::fake([
        'https://example.com' => Http::response('', 200),
    ]);

    $website = Website::factory()->create(['url' => 'https://example.com']);

    $job = new CheckSslCertificateJob($website);
    $job->handle(app(SslCertificateChecker::class));

    expect($website->fresh()->last_ssl_check_at)->not->toBeNull()
        ->and($website->fresh()->ssl_status)->not->toBe('unknown');
});
```

### Level 3: Browser Tests (25% of total tests)
**Target**: ~50 browser tests
**Focus**: End-to-end user workflows
**Speed**: Slower (~1-5s per test)

#### Coverage Areas:
- **User Authentication Flow**: Complete login/logout process
- **Website Management**: Add, edit, delete websites via UI
- **Dashboard Interactions**: Real-time updates, filters
- **Team Collaboration**: Multi-user scenarios
- **Responsive Design**: Mobile and desktop layouts
- **Visual Regression**: UI consistency validation

#### Example Browser Tests:
```php
<?php
// tests/Browser/WebsiteManagementFlowTest.php

use App\Models\User;
use App\Models\Website;

test('user can complete website management workflow', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $page = visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertUrlIs('/dashboard')
        ->screenshot('logged-in-dashboard');

    // Navigate to websites
    $page->click('Websites')
        ->assertSee('Website Management')
        ->assertSee('Add Website')
        ->screenshot('websites-index');

    // Add new website
    $page->click('Add Website')
        ->assertSee('Add New Website')
        ->fill('name', 'My Test Site')
        ->fill('url', 'https://example.com')
        ->screenshot('website-form-filled')
        ->click('Preview SSL')
        ->waitForText('SSL Certificate Preview', timeout: 10)
        ->screenshot('ssl-preview-loaded')
        ->click('Add Website')
        ->waitForText('Website added successfully')
        ->assertSee('My Test Site')
        ->screenshot('website-added-success');

    // Verify website appears in list
    expect(Website::where('name', 'My Test Site')->exists())->toBeTrue();
});

test('website ssl status updates in real-time', function () {
    $user = User::factory()->create();
    $website = Website::factory()->for($user)->create([
        'name' => 'Test Site',
        'ssl_status' => 'checking',
    ]);

    $page = visit('/login')
        ->loginAs($user)
        ->visit('/websites')
        ->assertSee('Test Site')
        ->assertSee('Checking...')
        ->screenshot('ssl-checking-status');

    // Simulate SSL check completion
    $website->update(['ssl_status' => 'valid']);

    $page->waitForText('Valid', timeout: 5)
        ->screenshot('ssl-valid-status');
});

test('responsive design works on mobile devices', function () {
    $user = User::factory()->create();

    $page = visit('/login')
        ->resize(375, 667) // iPhone 8 dimensions
        ->screenshot('mobile-login-page')
        ->loginAs($user)
        ->visit('/dashboard')
        ->screenshot('mobile-dashboard')
        ->click('[data-test="mobile-menu-toggle"]')
        ->assertSee('Websites')
        ->screenshot('mobile-menu-open')
        ->click('Websites')
        ->assertSee('Website Management')
        ->screenshot('mobile-websites-page');
});

test('visual regression - dashboard layout consistency', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->count(5)->for($user)->create();

    $page = visit('/login')
        ->loginAs($user)
        ->visit('/dashboard')
        ->screenshot('dashboard-with-websites');

    // Test dark mode
    $page->click('[data-test="theme-toggle"]')
        ->waitFor('[data-theme="dark"]')
        ->screenshot('dashboard-dark-mode');

    // Test light mode
    $page->click('[data-test="theme-toggle"]')
        ->waitFor('[data-theme="light"]')
        ->screenshot('dashboard-light-mode');
});
```

---

## 🔧 Test Configuration & Setup

### Pest Configuration
```php
<?php
// tests/Pest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Base test configuration
uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

// Global functions for common test operations
function loginAs(User $user = null): User
{
    $user = $user ?: User::factory()->create();
    test()->actingAs($user);
    return $user;
}

function createWebsite(array $attributes = []): \App\Models\Website
{
    return \App\Models\Website::factory()->create($attributes);
}

// Browser testing helpers
function visit(string $url): \Tests\Browser\Page
{
    return new \Tests\Browser\Page($url);
}
```

### Browser Testing Configuration
```php
<?php
// tests/Browser/BrowserTestCase.php

namespace Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class BrowserTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            '--window-size=1920,1080',
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->forget(3); // Remove headless option
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create screenshots directory
        if (!file_exists(storage_path('screenshots'))) {
            mkdir(storage_path('screenshots'), 0755, true);
        }
    }
}
```

### Database Testing Setup
```php
<?php
// tests/TestCase.php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Use SQLite in memory for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        // Disable queue processing during tests
        config(['queue.default' => 'sync']);

        // Disable mail sending during tests
        Mail::fake();

        // Mock external SSL certificate checks
        $this->mockSslCertificateChecks();
    }

    protected function mockSslCertificateChecks(): void
    {
        Http::fake([
            'https://*' => Http::response('OK', 200),
        ]);
    }
}
```

---

## 📈 Test Coverage Goals

### Coverage Targets
- **Overall Code Coverage**: >90%
- **Critical Business Logic**: 100%
- **Service Layer**: >95%
- **Controllers**: >85%
- **Models**: >90%

### Coverage Monitoring
```bash
# Generate coverage report
./vendor/bin/sail artisan test --coverage

# Generate HTML coverage report
./vendor/bin/sail artisan test --coverage-html=storage/coverage

# Coverage threshold enforcement
./vendor/bin/sail artisan test --coverage --min=90
```

### Critical Path Testing
**100% Coverage Required:**
- SSL certificate validation logic
- User authentication and authorization
- Payment processing (if applicable)
- Data encryption/decryption
- Security-related functions

**High Priority (>95% Coverage):**
- Website CRUD operations
- Team management functionality
- Email notification system
- Background job processing
- API endpoints

---

## 🎭 Test Data Management

### Factory Design
```php
<?php
// database/factories/WebsiteFactory.php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Website',
            'url' => $this->faker->url(),
            'user_id' => User::factory(),
            'ssl_status' => 'unknown',
            'last_ssl_check_at' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function withValidSsl(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'valid',
            'last_ssl_check_at' => now(),
        ]);
    }

    public function withExpiringSsl(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'expiring_soon',
            'last_ssl_check_at' => now(),
        ]);
    }

    public function withExpiredSsl(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssl_status' => 'expired',
            'last_ssl_check_at' => now(),
        ]);
    }
}
```

### Test Datasets
```php
<?php
// Use Pest datasets for comprehensive testing

dataset('ssl_statuses', [
    'valid' => ['valid', true, 30],
    'expiring_soon' => ['expiring_soon', true, 7],
    'expiring_warning' => ['expiring_warning', true, 14],
    'expired' => ['expired', false, -1],
    'invalid' => ['invalid', false, null],
    'error' => ['error', false, null],
]);

test('ssl status badge displays correct styling', function ($status, $isValid, $daysUntilExpiry) {
    $website = Website::factory()->create([
        'ssl_status' => $status,
        'days_until_expiry' => $daysUntilExpiry,
    ]);

    $component = new SslStatusBadge($website);

    expect($component->getStatusClass())->toContain($status)
        ->and($component->isValid())->toBe($isValid);
})->with('ssl_statuses');
```

---

## 🚀 Performance Testing

### Load Testing
```php
<?php
// tests/Performance/LoadTest.php

test('dashboard can handle concurrent users', function () {
    $users = User::factory()->count(50)->create();
    $websites = Website::factory()->count(200)->create();

    $startTime = microtime(true);

    // Simulate 50 concurrent dashboard requests
    $responses = collect($users)->map(function ($user) {
        return $this->actingAs($user)->get('/dashboard');
    });

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Assert all requests completed successfully
    $responses->each(function ($response) {
        expect($response->status())->toBe(200);
    });

    // Assert performance requirements
    expect($totalTime)->toBeLessThan(5.0); // All requests complete in <5 seconds
});

test('ssl checking can process multiple websites efficiently', function () {
    $websites = Website::factory()->count(100)->create();

    $startTime = microtime(true);

    foreach ($websites as $website) {
        CheckSslCertificateJob::dispatchSync($website);
    }

    $endTime = microtime(true);
    $averageTime = ($endTime - $startTime) / 100;

    expect($averageTime)->toBeLessThan(1.0); // <1 second per SSL check
});
```

### Memory Usage Testing
```php
test('large dataset processing stays within memory limits', function () {
    $initialMemory = memory_get_usage();

    // Process large dataset
    $websites = Website::factory()->count(1000)->create();
    $results = $websites->map(function ($website) {
        return app(SslCertificateChecker::class)->checkCertificate($website);
    });

    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;

    // Assert memory usage stays reasonable
    expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024); // <50MB increase
});
```

---

## 🔒 Security Testing

### Authentication Testing
```php
test('protected routes require authentication', function () {
    $protectedRoutes = [
        '/dashboard',
        '/websites',
        '/websites/create',
        '/settings/profile',
        '/settings/team',
    ];

    foreach ($protectedRoutes as $route) {
        $response = $this->get($route);
        expect($response->status())->toBe(302); // Redirect to login
    }
});

test('users cannot access other users data', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $website = Website::factory()->for($user1)->create();

    $response = $this->actingAs($user2)->get("/websites/{$website->id}");

    expect($response->status())->toBe(403);
});
```

### Input Validation Testing
```php
test('website creation prevents xss attacks', function () {
    $user = User::factory()->create();

    $maliciousInput = '<script>alert("xss")</script>';

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => $maliciousInput,
            'url' => 'https://example.com',
        ]);

    $website = Website::latest()->first();
    expect($website->name)->not->toContain('<script>');
});

test('sql injection prevention', function () {
    $user = User::factory()->create();

    $maliciousInput = "'; DROP TABLE websites; --";

    $response = $this->actingAs($user)
        ->get('/websites?search=' . urlencode($maliciousInput));

    // Ensure tables still exist
    expect(Website::count())->toBeGreaterThanOrEqual(0);
});
```

---

## 📊 Test Execution & CI/CD

### Local Test Execution
```bash
# Run all tests
./vendor/bin/sail artisan test

# Run specific test suites
./vendor/bin/sail artisan test --testsuite=Feature
./vendor/bin/sail artisan test --testsuite=Unit
./vendor/bin/sail artisan test tests/Browser/

# Run tests with coverage
./vendor/bin/sail artisan test --coverage --min=90

# Run tests in parallel
./vendor/bin/sail artisan test --parallel

# Run browser tests with UI (for development)
DUSK_HEADLESS_DISABLED=1 ./vendor/bin/sail artisan test tests/Browser/
```

### Continuous Integration Pipeline
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
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Install NPM dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Setup application
        run: |
          cp .env.testing .env
          php artisan key:generate

      - name: Run PHP tests
        run: php artisan test --coverage --min=90

      - name: Run JavaScript tests
        run: npm run test

      - name: Run browser tests
        run: php artisan dusk
```

---

This comprehensive testing strategy ensures SSL Monitor v3 maintains high quality, reliability, and user experience through automated testing at all levels of the application stack.