# Comprehensive Debug Menu Implementation Plan

## Overview
Create a generic, comprehensive debug menu system for multiple debugging scenarios including SSL overrides, following strict test-driven development with real data (no mocking).

**Target User**: `bonzo@konjscina.com` (with extensible multi-user support)
**Development Approach**: Test-Driven Development with Pest 4
**Data Strategy**: Real data only - no mocking for accurate testing

## Prerequisites & Required Reading

### 1. Start With Foundation Documents
**FIRST**: Read `/home/bonzo/code/ssl-monitor-v4/DEVELOPMENT_PRIMER.md` - Complete application understanding
**THEN**: Read `/home/bonzo/code/ssl-monitor-v4/docs/DEBUG_LOGGING_ENHANCEMENT.md` - Existing debug infrastructure plans

### 2. Key Application Knowledge Required
- **Laravel Sail Development**: All commands use `./vendor/bin/sail` prefix
- **Authentication System**: User data in `auth.user` via Inertia middleware
- **Navigation Configuration**: `resources/js/config/navigation.ts`
- **Alert System**: `app/Services/AlertService.php` with real email testing via Mailpit
- **Monitor Model**: Custom `app/Models/Monitor.php` extending Spatie's base model
- **Testing Infrastructure**: Pest 4 with 494 existing tests, use `MocksSslCertificateAnalysis` trait for SSL operations

## Phase 1: Branch Setup & TDD Foundation

### 1.1 Branch Management
```bash
# Create new feature branch
git checkout -b feature/debug-menu-system

# Work on feature, then push to both remotes
git push github feature/debug-menu-system && git push origin feature/debug-menu-system
```

### 1.2 Test-Driven Development Strategy
- **Real Data**: Use actual websites, SSL certificates, and database records
- **No Mocking**: Test real functionality, not mocked behavior
- **Pest 4**: Leverage existing test infrastructure (494 tests, 98% success rate)
- **Coverage**: Aim for 100% test coverage of debug functionality
- **Mailpit Testing**: Real email verification via http://localhost:8025

### 1.3 Environment Setup
```bash
# Ensure development environment is running
composer run dev

# Verify Mailpit is accessible
curl http://localhost:8025
```

## Phase 2: Generic Debug Framework Architecture

### 2.1 Extensible Debug Module System
```php
debug/
├── ssl-overrides          # SSL certificate expiry overrides
├── monitor-inspector      # Monitor status and timing analysis
├── queue-monitor         # Queue job inspection and manual triggers
├── scheduler-viewer      # Scheduled task analysis
├── log-viewer           # Real-time log streaming
├── system-health        # Database, Redis, system metrics
└── alert-tester         # Manual alert triggering with scenarios
```

### 2.2 Security Model
```env
# .env configuration
DEBUG_MENU_ENABLED=true                    # Enable debug menu system
DEBUG_MENU_USERS=bonzo@konjscina.com       # Comma-separated user emails
DEBUG_MENU_ROLES=OWNER,ADMIN               # Fallback role-based access
DEBUG_MENU_AUDIT=true                      # Log all debug actions
```

### 2.3 Database Schema Design
```php
// Generic debug overrides table
Schema::create('debug_overrides', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('module_type'); // 'ssl_expiry', 'monitor_status', etc.
    $table->morphs('targetable'); // website, monitor, etc.
    $table->json('override_data'); // Flexible override configuration
    $table->boolean('is_active')->default(true);
    $table->timestamp('expires_at')->nullable(); // Auto-expire overrides
    $table->timestamps();

    $table->index(['user_id', 'module_type', 'is_active']);
});
```

## Phase 3: Test-First Implementation Strategy

### 3.1 Pest 4 Test Structure (Dual Suites)

#### Main Test Suite (Fast - SQLite)
```
tests/Feature/Debug/
├── DebugMenuAccessTest.php          # Security and access control (mocked)
├── SslOverridesLogicTest.php        # SSL override logic (factories)
└── DebugModuleTest.php             # Module framework (unit tests)
```

#### Debug Integration Suite (Real - MariaDB)
```
tests/Integration/Debug/
├── SslOverridesIntegrationTest.php  # Real SSL override functionality
├── MonitorInspectorIntegrationTest.php # Real monitor analysis
├── QueueMonitorIntegrationTest.php  # Real queue inspection
├── AlertTesterIntegrationTest.php   # Real alert scenarios
├── EmailSendingIntegrationTest.php  # Real Mailpit email testing
└── DebugMenuFullIntegrationTest.php # Complete workflow testing
```

### 3.2 Real Data Test Example
```php
// tests/Feature/Debug/SslOverridesTest.php
test('user can override ssl expiry and see real email alerts', function () {
    // Arrange: Create real website and monitor
    $user = User::where('email', 'bonzo@konjscina.com')->first();
    $website = Website::factory()->create(['user_id' => $user->id]);
    $monitor = Monitor::factory()->create([
        'url' => $website->url,
        'certificate_expiration_date' => now()->addDays(30),
    ]);

    // Enable alerts for this website
    AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'threshold_days' => 7,
        'enabled' => true,
    ]);

    // Act: Override SSL expiry to 7 days from now
    $response = $this->actingAs($user)
        ->post('/debug/ssl-overrides', [
            'website_id' => $website->id,
            'override_expiry_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

    // Assert: Check database and real alert triggering
    $response->assertRedirect();
    $this->assertDatabaseHas('debug_overrides', [
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
    ]);

    // Test real alert triggering with override
    $alertService = app(AlertService::class);
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    expect($triggeredAlerts)->toHaveCount(1);
    expect($triggeredAlerts[0]['type'])->toBe('ssl_expiry');
    expect($triggeredAlerts[0]['level'])->toBe('urgent');

    // Verify real email was sent to Mailpit
    $this->assertMailSent('ssl-certificate-expiry-alert', [$user->email]);
});
```

## Phase 4: SSL Overrides Module (First Implementation)

### 4.1 Test Cases - Write First
```php
// tests/Feature/Debug/SslOverridesTest.php
test('ssl override menu only visible to authorized users');
test('ssl override affects days remaining calculation');
test('ssl override triggers real email alerts');
test('ssl override works with different alert thresholds (7, 3, 1, 0 days)');
test('ssl override expiration date calculations');
test('ssl override cleanup and restoration');
test('ssl override with let\'s encrypt detection');
test('ssl override security - user isolation');
test('ssl override audit trail logging');
test('ssl override with expired certificates');
test('bulk ssl override operations');
test('ssl override auto-expiration functionality');
```

### 4.2 Implementation - After Tests Pass

#### Core Debug Classes
```php
// app/Debug/Modules/SslOverridesModule.php
class SslOverridesModule extends DebugModule
{
    public function getName(): string { return 'SSL Overrides'; }
    public function getDescription(): string { return 'Override SSL certificate expiry dates for testing'; }

    public function getRoutes(): array
    {
        return [
            'GET /debug/ssl-overrides' => 'index',
            'POST /debug/ssl-overrides' => 'store',
            'PUT /debug/ssl-overrides/{id}' => 'update',
            'DELETE /debug/ssl-overrides/{id}' => 'destroy',
            'POST /debug/ssl-overrides/bulk' => 'bulkStore',
            'DELETE /debug/ssl-overrides/bulk' => 'bulkDestroy',
        ];
    }
}

// app/Http/Controllers/Debug/SslOverridesController.php
class SslOverridesController extends Controller
{
    public function index(Request $request)
    {
        // Real data: User's websites with actual SSL status
        $websites = Website::with(['monitors', 'alertConfigurations'])
            ->where('user_id', $request->user()->id)
            ->where('ssl_monitoring_enabled', true)
            ->get();

        return inertia('Debug/SslOverrides', [
            'websites' => $websites->map(fn($w) => $this->formatWebsiteData($w)),
            'overrideStats' => $this->getOverrideStats($request->user()),
            'alertScenarios' => $this->getAlertScenarios(),
        ]);
    }

    private function formatWebsiteData(Website $website): array
    {
        $monitor = $website->getSpatieMonitor();
        $override = $website->getDebugOverride('ssl_expiry');

        return [
            ...$website->toArray(),
            'real_expiry_date' => $monitor?->certificate_expiration_date,
            'override' => $override,
            'effective_expiry' => $website->getEffectiveSslExpiryDate(),
            'days_remaining' => $website->getDaysRemaining(),
            'alert_status' => $this->getAlertStatus($website),
            'can_override' => $monitor && $website->ssl_monitoring_enabled,
        ];
    }
}
```

#### Model Integration
```php
// app/Models/Website.php - Add methods
public function getDebugOverride(string $moduleType): ?DebugOverride
{
    return $this->debugOverrides()
        ->where('module_type', $moduleType)
        ->where('user_id', auth()->id())
        ->where('is_active', true)
        ->first();
}

public function getEffectiveSslExpiryDate(): ?Carbon
{
    $override = $this->getDebugOverride('ssl_expiry');

    if ($override && $override->override_data['expiry_date']) {
        return Carbon::parse($override->override_data['expiry_date']);
    }

    $monitor = $this->getSpatieMonitor();
    return $monitor?->certificate_expiration_date
        ? Carbon::parse($monitor->certificate_expiration_date)
        : null;
}

public function getDaysRemaining(): int
{
    $expiry = $this->getEffectiveSslExpiryDate();
    return $expiry ? (int) $expiry->diffInDays(now(), false) : 0;
}
```

## Phase 5: Frontend Implementation

### 5.1 Navigation Integration
```typescript
// resources/js/config/navigation.ts
import { Bug } from 'lucide-vue-next'

// Add conditional debug menu
export const addDebugMenu = (auth: any, config: any): MenuItem[] => {
    if (!auth.user?.email || !config.debugUsers?.includes(auth.user.email)) {
        return [];
    }

    return [{
        key: 'debug',
        title: 'Debug',
        icon: Bug,
        href: '/debug',
        description: 'Development & testing tools',
        children: [
            { title: 'SSL Overrides', href: '/debug/ssl-overrides' },
            { title: 'Monitor Inspector', href: '/debug/monitor-inspector' },
            { title: 'Queue Monitor', href: '/debug/queue' },
            { title: 'Alert Tester', href: '/debug/alert-tester' },
        ]
    }];
};
```

### 5.2 Vue Components
```vue
<!-- resources/js/pages/Debug/SslOverrides.vue -->
<template>
  <div class="debug-ssl-overrides">
    <!-- Header with stats -->
    <div class="header">
      <h1>SSL Certificate Overrides</h1>
      <div class="stats">
        <span>Total Websites: {{ websites.length }}</span>
        <span>Active Overrides: {{ activeOverrides }}</span>
        <span>Alerts Pending: {{ pendingAlerts }}</span>
      </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="quick-actions">
      <button @click="setBulkOverrides(7)" class="btn-7days">Set All 7 Days</button>
      <button @click="setBulkOverrides(3)" class="btn-3days">Set All 3 Days</button>
      <button @click="setBulkOverrides(1)" class="btn-1day">Set All 1 Day</button>
      <button @click="clearAllOverrides()" class="btn-clear">Clear All</button>
      <button @click="testAllAlerts()" class="btn-test">Test All Alerts</button>
    </div>

    <!-- Websites List -->
    <div class="websites-list">
      <div v-for="website in websites" :key="website.id" class="website-row">
        <div class="website-info">
          <h3>{{ website.name }}</h3>
          <p>{{ website.url }}</p>
          <div class="ssl-status">
            <span class="real-expiry">
              Real: {{ formatDate(website.real_expiry_date) }}
            </span>
            <span v-if="website.override" class="override-expiry">
              Override: {{ formatDate(website.override.override_data.expiry_date) }}
            </span>
            <span class="days-remaining" :class="getDaysClass(website.days_remaining)">
              {{ website.days_remaining }} days
            </span>
          </div>
        </div>

        <div class="override-controls">
          <div class="quick-buttons">
            <button @click="setOverride(website.id, 7)"
                    :disabled="!website.can_override"
                    class="btn-7days">7d</button>
            <button @click="setOverride(website.id, 3)"
                    :disabled="!website.can_override"
                    class="btn-3days">3d</button>
            <button @click="setOverride(website.id, 1)"
                    :disabled="!website.can_override"
                    class="btn-1day">1d</button>
            <button @click="setOverride(website.id, 0)"
                    :disabled="!website.can_override"
                    class="btn-0day">0d</button>
            <button @click="setOverride(website.id, -1)"
                    :disabled="!website.can_override"
                    class="btn-expired">Exp</button>
          </div>

          <div class="custom-controls">
            <input type="datetime-local"
                   v-model="customDates[website.id]"
                   class="custom-date-input">
            <button @click="setCustomOverride(website.id)"
                    :disabled="!customDates[website.id]"
                    class="btn-custom">Set Custom</button>
          </div>

          <div class="actions">
            <button v-if="website.override"
                    @click="clearOverride(website.id)"
                    class="btn-clear">Clear</button>
            <button @click="testAlerts(website.id)"
                    :disabled="!website.can_override"
                    class="btn-test">Test Alerts</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

## Phase 6: Additional Debug Modules (Future Phases)

### 6.1 Monitor Inspector Module
- **Real Data**: Actual monitor statuses, check intervals, last check times
- **Functionality**: Manual checks, interval override, status analysis
- **Test Cases**: Real monitor execution, timing analysis, queue integration

### 6.2 Queue Monitor Module
- **Real Data**: Actual queue jobs from Horizon/Redis
- **Functionality**: View pending jobs, retry failed jobs, manual triggers
- **Test Cases**: Real job dispatching, error handling, performance testing

### 6.3 Alert Tester Module
- **Real Data**: User's actual websites and alert configurations
- **Functionality**: Test different alert scenarios with real emails to Mailpit
- **Test Cases**: Real email sending, alert triggering logic, template testing

## Phase 7: Security & Integration

### 7.1 Access Control Middleware
```php
// app/Http/Middleware/DebugMenuAccess.php
class DebugMenuAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('debug.menu_enabled')) {
            abort(403, 'Debug menu is disabled');
        }

        $user = $request->user();
        $allowedUsers = explode(',', config('debug.menu_users', ''));
        $allowedRoles = explode(',', config('debug.menu_roles', ''));

        if (!in_array($user->email, $allowedUsers) &&
            !in_array($user->primary_role, $allowedRoles)) {
            abort(403, 'Access denied');
        }

        // Log access
        if (config('debug.menu_audit')) {
            Log::info('Debug menu accessed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
```

### 7.2 Configuration Updates
```php
// config/app.php - Add debug configuration
'debug' => [
    'menu_enabled' => env('DEBUG_MENU_ENABLED', false),
    'menu_users' => env('DEBUG_MENU_USERS', ''),
    'menu_roles' => env('DEBUG_MENU_ROLES', 'OWNER,ADMIN'),
    'menu_audit' => env('DEBUG_MENU_AUDIT', true),
    'overrides_expire_hours' => env('DEBUG_OVERRIDES_EXPIRE_HOURS', 24),
],
```

## Phase 8: Testing Strategy (Real Data Only)

### 8.1 Separate Test Suites Strategy

**Main Test Suite** (`./vendor/bin/sail artisan test`):
- **Location**: `tests/Unit/`, `tests/Feature/`, `tests/Browser/`
- **Data**: Uses SQLite in-memory database, mocks, factories
- **Speed**: Fast (< 20 seconds parallel)
- **Purpose**: Core application functionality
- **Debug Tests**: NOT included

**Debug Integration Test Suite** (separate):
- **Location**: `tests/Integration/Debug/`
- **Data**: Uses real MariaDB database, real SSL data
- **Speed**: Slower (expected 1-5 minutes)
- **Purpose**: Debug functionality with real data
- **Command**: `./vendor/bin/sail artisan test --testsuite=Debug`

### 8.2 Test Environment Setup

#### Main Test Suite (Fast)
```php
// tests/Pest.php - Existing setup remains unchanged
beforeEach(function () {
    $this->setUpCleanDatabase();
    // ... existing test setup
});
```

#### Debug Integration Tests (Real Data)
```php
// tests/Integration/Debug/Pest.php - Debug test setup
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Use REAL MariaDB database for debug tests
    $this->refreshDatabase();

    // Ensure debug user exists in real database
    $this->debugUser = User::firstOrCreate(
        ['email' => 'bonzo@konjscina.com'],
        [
            'name' => 'Debug User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]
    );

    // Clean up debug overrides from previous runs
    DebugOverride::where('user_id', $this->debugUser->id)->delete();

    // Ensure Mailpit is ready for email testing
    $this->assertTrue($this->isMailpitReady(), 'Mailpit must be running for debug tests');
});
```

### 8.3 PHPUnit Configuration

#### phpunit.xml - Add Debug Test Suite
```xml
<!-- Add to existing phpunit.xml -->
<testsuites>
    <!-- Existing test suites remain unchanged -->
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Browser">
        <directory>tests/Browser</directory>
    </testsuite>

    <!-- NEW: Debug Integration Test Suite -->
    <testsuite name="Debug">
        <directory>tests/Integration/Debug</directory>
        <exclude>./tests/Integration/Debug/*Test.php</exclude>
    </testsuite>
</testsuites>

<!-- Add separate environment variables for debug tests -->
<php>
    <env name="DB_CONNECTION" value="mariadb"/>
    <env name="DB_DATABASE" value="laravel"/>

    <!-- Debug-specific settings -->
    <env name="DEBUG_MENU_ENABLED" value="true"/>
    <env name="DEBUG_MENU_USERS" value="bonzo@konjscina.com"/>
    <env name="MAIL_MAILER" value="smtp"/>
    <env name="MAIL_HOST" value="mailpit"/>
    <env name="MAIL_PORT" value="1025"/>
</php>
```

### 8.4 Real Data Testing Rules
1. **Actual Users**: Use real `bonzo@konjscina.com` account
2. **Real Websites**: Test with actual monitored websites
3. **Real SSL**: Use actual certificate data from monitors
4. **Real Emails**: Send to actual Mailpit instance at localhost:8025
5. **Real Queues**: Use actual Redis/Horizon setup
6. **Real Database**: Use MariaDB (same as development)
7. **No Mocking**: Test real system behavior and integration

### 8.5 Test Execution Commands

#### Main Test Suite (Fast)
```bash
# Regular development testing - excludes debug tests
./vendor/bin/sail artisan test --parallel

# With coverage
./vendor/bin/sail artisan test --parallel --coverage
```

#### Debug Integration Tests (Slow, Real Data)
```bash
# Only debug integration tests
./vendor/bin/sail artisan test --testsuite=Debug

# Verbose output for debugging
./vendor/bin/sail artisan test --testsuite=Debug --verbose

# Single debug test
./vendor/bin/sail artisan test tests/Integration/Debug/SslOverridesIntegrationTest.php --verbose
```

#### Development Workflow
```bash
# During development - run main suite to ensure no regressions
./vendor/bin/sail artisan test --parallel

# Run debug tests when working on debug features
./vendor/bin/sail artisan test --testsuite=Debug

# Before committing - run both (but main suite first)
./vendor/bin/sail artisan test --parallel && \
./vendor/bin/sail artisan test --testsuite=Debug
```

## Implementation Checklist

### Phase 1: Foundation
- [ ] Create feature branch
- [ ] Read DEVELOPMENT_PRIMER.md thoroughly
- [ ] Read DEBUG_LOGGING_ENHANCEMENT.md
- [ ] Set up development environment
- [ ] Verify Mailpit is running

### Phase 2: Core Infrastructure
- [ ] Create DebugOverride migration
- [ ] Create DebugModule abstract class
- [ ] Create DebugMenuAccess middleware
- [ ] Update configuration files
- [ ] Write security tests first

### Phase 3: SSL Overrides (TDD)
- [ ] Write all SSL override tests
- [ ] Implement SslOverridesModule class
- [ ] Create SslOverridesController
- [ ] Add Website model methods
- [ ] Create Vue components
- [ ] Update navigation
- [ ] Test with real data and Mailpit

### Phase 4: Integration & Polish
- [ ] Update HandleInertiaRequests middleware
- [ ] Add audit logging
- [ ] Update documentation
- [ ] Performance testing
- [ ] Security verification
- [ ] Full integration testing

## Files to Create/Modify

### New Files
1. `database/migrations/2025_01_13_create_debug_overrides_table.php`
2. `app/Debug/Modules/DebugModule.php` (abstract)
3. `app/Debug/Modules/SslOverridesModule.php`
4. `app/Models/DebugOverride.php`
5. `app/Http/Middleware/DebugMenuAccess.php`
6. `app/Http/Controllers/Debug/SslOverridesController.php`
7. `tests/Feature/Debug/SslOverridesTest.php`
8. `tests/Feature/Debug/DebugMenuAccessTest.php`
9. `resources/js/pages/Debug/SslOverrides.vue`

### Modified Files
1. `routes/web.php` - Add debug routes with middleware
2. `resources/js/config/navigation.ts` - Conditional debug menu
3. `app/Http/Middleware/HandleInertiaRequests.php` - Debug config sharing
4. `app/Models/Website.php` - Override methods
5. `app/Services/AlertService.php` - Use effective expiry dates
6. `config/app.php` - Debug configuration
7. `.env.example` - Debug environment variables

## Next Steps

1. **Start New Session**: Begin with reading foundation documents
2. **Create Branch**: Set up feature branch for development
3. **TDD Approach**: Write tests before implementation
4. **Real Data**: Use actual websites, SSL data, and Mailpit
5. **Security First**: Implement access control before functionality
6. **Iterative**: Build SSL overrides first, then expand to other modules

This comprehensive plan provides a solid foundation for building a powerful, secure debug system that enhances the existing SSL Monitor v4 application while maintaining code quality and security standards.