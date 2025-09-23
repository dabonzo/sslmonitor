# /test - Complete Testing Framework

## Overview
SSL Monitor v4 uses the **development database** for all testing with real seeded data. This ensures tests run against actual data and configurations.

## Testing Configuration

### Database Setup
- **Tests use DEVELOPMENT database** (`laravel`) - NOT a separate test database
- **Test user**: `bonzo@konjscina.com` (password: `to16ro12`)
- **Real test websites**:
  - `https://www.redgas.at`
  - `https://www.fairnando.at`
  - `https://omp.office-manager-pro.com`

### Configuration Files
- **`phpunit.xml`**: Line 28 sets `DB_DATABASE` to `laravel` (development database)
- **`tests/Pest.php`**: Configures test cleanup and seeded data management
- **Development database** contains real SSL monitoring data for testing

## Test Categories

### 1. Feature Tests (Backend Logic)
```bash
# Run all feature tests
./vendor/bin/sail artisan test tests/Feature/

# Run specific feature test categories
./vendor/bin/sail artisan test --filter="AlertSystemTest"
./vendor/bin/sail artisan test --filter="Settings"
./vendor/bin/sail artisan test --filter="Auth"
```

### 2. Browser Tests (UI Interactions)
```bash
# Run all browser tests
./vendor/bin/sail artisan test tests/Browser/

# Run specific browser tests
./vendor/bin/sail artisan test --filter="AlertsConfigurationBrowserTest"
./vendor/bin/sail artisan test --filter="ReactiveWebsiteFilteringTest"
```

### 3. Unit Tests (Individual Components)
```bash
# Run unit tests
./vendor/bin/sail artisan test tests/Unit/
```

## Complete Test Suite Commands

### 🚀 Quick Test Suite (Recommended)
```bash
# Run all feature tests (most reliable)
./vendor/bin/sail artisan test tests/Feature/ --stop-on-failure

# Run essential browser tests
./vendor/bin/sail artisan test --filter="displays alert configuration page with statistics and data"
./vendor/bin/sail artisan test --filter="shows live filtering indicator when enabled"
```

### 🔄 Full Test Suite (All Tests)
```bash
# Complete test suite (may take 5-10 minutes)
./vendor/bin/sail artisan test --stop-on-failure

# Complete test suite without stopping on failures
./vendor/bin/sail artisan test
```

### 🎯 Targeted Testing (Specific Features)
```bash
# Alert system tests
./vendor/bin/sail artisan test --filter="Alert" --stop-on-failure

# Settings functionality tests
./vendor/bin/sail artisan test --filter="Settings" --stop-on-failure

# Authentication tests
./vendor/bin/sail artisan test --filter="Auth" --stop-on-failure

# SSL monitoring tests
./vendor/bin/sail artisan test --filter="SslMonitoring" --stop-on-failure
```

## Database Management for Testing

### Fresh Database Setup (When Needed)
```bash
# Create fresh database with seeded data
./vendor/bin/sail artisan migrate:fresh --seed

# Clear caches after database changes
./vendor/bin/sail artisan cache:clear && ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan view:clear && ./vendor/bin/sail artisan route:clear
```

### Test Data Verification
```bash
# Verify test user exists
./vendor/bin/sail artisan tinker
>>> App\Models\User::where('email', 'bonzo@konjscina.com')->first();

# Verify test websites exist
>>> App\Models\Website::whereIn('url', ['https://www.redgas.at', 'https://www.fairnando.at', 'https://omp.office-manager-pro.com'])->get();
```

## Test Environment Details

### Pest Configuration (tests/Pest.php)
- **beforeEach**: Ensures clean state while preserving real test data
- **Real data preservation**: Keeps test user and websites between tests
- **Table cleanup**: Safely truncates only temporary test data
- **Error handling**: Graceful handling of missing tables/data

### Test User & Data
```php
// Test user (always available)
$testUser = User::where('email', 'bonzo@konjscina.com')->first();

// Real test websites (always available)
$realWebsites = Website::whereIn('url', [
    'https://www.redgas.at',
    'https://www.fairnando.at',
    'https://omp.office-manager-pro.com'
])->get();

// Test team (always available)
$testTeam = Team::where('name', 'Intermedien')->first();
```

## Expected Test Results

### ✅ Should Always Pass
- **AlertSystemTest**: 19 tests - Alert configuration and service logic
- **Settings Tests**: Profile, password, two-factor, alerts
- **Authentication Tests**: Login, registration, password reset
- **SslMonitoringTest**: SSL certificate analysis and monitoring

### ⚠️ Known Browser Test Issues
- Some browser tests skip form submission validation (feature tests cover this)
- Advanced browser testing methods may not be available
- Core UI functionality verified, edge cases may be skipped

## Troubleshooting

### Common Issues & Solutions

**1. Database Table Not Found**
```bash
# Solution: Run migrations
./vendor/bin/sail artisan migrate --force
```

**2. Test User Missing**
```bash
# Solution: Run seeder
./vendor/bin/sail artisan db:seed --class=TestUserSeeder
```

**3. Duplicate Email Errors**
- Tests properly use development database
- Unique timestamps prevent conflicts
- Real test user preserved between tests

**4. Cache Issues**
```bash
# Solution: Clear all caches
./vendor/bin/sail artisan cache:clear && ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan view:clear && ./vendor/bin/sail artisan route:clear
```

### Performance Tips
- Use `--stop-on-failure` for development
- Run feature tests first (most reliable)
- Skip problematic browser tests during development
- Use filters to test specific functionality

## Test Coverage

### Current Coverage Status
- ✅ **Alert Configuration**: Complete CRUD and validation
- ✅ **Reactive Filtering**: Debounced search functionality
- ✅ **Team Management**: Full team operations
- ✅ **Settings Navigation**: All settings pages
- ✅ **Authentication**: Complete auth flow
- ✅ **SSL Monitoring**: Certificate analysis and monitoring
- ✅ **Database Integration**: Real data testing

### Test Statistics
- **Feature Tests**: 35+ tests covering backend logic
- **Browser Tests**: 15+ tests covering UI interactions
- **Unit Tests**: Component-level testing
- **Total Coverage**: All major functionality validated

## Usage Examples

### Development Workflow
```bash
# 1. After making changes, run targeted tests
./vendor/bin/sail artisan test --filter="AlertsController" --stop-on-failure

# 2. Before committing, run feature test suite
./vendor/bin/sail artisan test tests/Feature/ --stop-on-failure

# 3. Before deployment, run complete suite
./vendor/bin/sail artisan test --stop-on-failure
```

### Debugging Failed Tests
```bash
# Run single test with verbose output
./vendor/bin/sail artisan test --filter="specific test name" -v

# Check test database state
./vendor/bin/sail artisan tinker
>>> User::count(); // Should include test user
>>> Website::count(); // Should include test websites
```

---

## Key Principles

1. **Always use development database** - Never create separate test database
2. **Preserve real test data** - Don't truncate seeded user/websites
3. **Clean temporary data** - Remove only test-generated content
4. **Feature tests first** - Most reliable validation of functionality
5. **Browser tests supplement** - UI validation when possible

This configuration ensures consistent, reliable testing with real data! 🧪✅