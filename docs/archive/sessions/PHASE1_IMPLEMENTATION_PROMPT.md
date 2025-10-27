# Phase 1 Implementation Prompt - Historical Data Tracking

**Copy this entire prompt to start Phase 1 implementation**

---

## 🎯 Mission: Implement Phase 1 - Foundation (Week 1)

You are implementing **Phase 1: Foundation** of the Historical Data Tracking system for SSL Monitor v4. This phase creates the database schema and core models that will enable comprehensive historical tracking of all monitoring checks.

## 📚 Essential Context

**Project**: SSL Monitor v4 - Laravel 12 + Vue 3 + Inertia.js + MariaDB
**Current State**: 530 tests passing (13 skipped), 100% pass rate
**Branch**: `feature/historical-data-tracking` (create from current branch)
**Test Performance Requirement**: Maintain < 20s parallel test execution

**Documentation**:
- **Master Plan**: `docs/HISTORICAL_DATA_MASTER_PLAN.md` (complete implementation guide)
- **Quick Reference**: `docs/HISTORICAL_DATA_QUICK_START.md`
- **Testing Guide**: `docs/TESTING_INSIGHTS.md`
- **Development Primer**: `docs/DEVELOPMENT_PRIMER.md`

## 🎯 Phase 1 Goals

Create the foundation for historical data tracking:
1. ✅ 4 database migrations with optimized schema
2. ✅ 4 Eloquent models with relationships
3. ✅ Migration tests to verify schema
4. ✅ All tests passing (maintain < 20s)

## 📋 Detailed Implementation Steps

### Step 1: Create Feature Branch

```bash
# Create new branch from current state
git checkout -b feature/historical-data-tracking

# Verify starting point
git log --oneline -1
```

### Step 2: Create Database Migrations (4 files)

Create migrations in this exact order:

```bash
./vendor/bin/sail artisan make:migration create_monitoring_results_table
./vendor/bin/sail artisan make:migration create_monitoring_check_summaries_table
./vendor/bin/sail artisan make:migration create_monitoring_alerts_table
./vendor/bin/sail artisan make:migration create_monitoring_events_table
```

### Step 3: Implement Migration Schemas

**CRITICAL**: Copy the EXACT schemas from `docs/HISTORICAL_DATA_MASTER_PLAN.md` section "🗄️ Consolidated Database Schema".

Each migration should:
- Use the complete CREATE TABLE statement from the master plan
- Include ALL columns with proper types
- Include ALL indexes (especially the critical composite index `idx_monitor_website_time`)
- Include ALL foreign key constraints
- Use InnoDB engine with utf8mb4_unicode_ci collation
- Add inline comments from the master plan

**For `create_monitoring_results_table.php`**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_results', function (Blueprint $table) {
            // Primary Key & UUID
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID for external API references');

            // Relationships (CRITICAL: Both monitor_id AND website_id)
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();

            // Check Classification
            $table->enum('check_type', ['uptime', 'ssl_certificate', 'both'])->default('both');
            $table->enum('trigger_type', ['scheduled', 'manual_immediate', 'manual_bulk', 'system']);
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Check Timing (MILLISECOND PRECISION)
            $table->timestamp('started_at', 3)->comment('High-precision start time');
            $table->timestamp('completed_at', 3)->nullable()->comment('High-precision completion time');
            $table->unsignedInteger('duration_ms')->nullable()->comment('Check duration in milliseconds');

            // Overall Status
            $table->enum('status', ['success', 'failed', 'timeout', 'error']);
            $table->text('error_message')->nullable();

            // ==================== UPTIME-SPECIFIC DATA ====================
            $table->enum('uptime_status', ['up', 'down'])->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('response_body_size_bytes')->nullable();
            $table->unsignedTinyInteger('redirect_count')->nullable()->default(0);
            $table->string('final_url', 2048)->nullable();

            // ==================== SSL CERTIFICATE-SPECIFIC DATA ====================
            $table->enum('ssl_status', ['valid', 'invalid', 'expired', 'expires_soon', 'self_signed'])->nullable();
            $table->string('certificate_issuer')->nullable();
            $table->string('certificate_subject')->nullable();
            $table->timestamp('certificate_expiration_date')->nullable();
            $table->timestamp('certificate_valid_from_date')->nullable();
            $table->integer('days_until_expiration')->nullable();
            $table->json('certificate_chain')->nullable()->comment('Full certificate chain data');

            // ==================== CONTENT VALIDATION DATA ====================
            $table->boolean('content_validation_enabled')->default(false);
            $table->enum('content_validation_status', ['passed', 'failed', 'not_checked'])->nullable();
            $table->json('expected_strings_found')->nullable();
            $table->json('forbidden_strings_found')->nullable();
            $table->json('regex_matches')->nullable();
            $table->boolean('javascript_rendered')->default(false);
            $table->unsignedTinyInteger('javascript_wait_seconds')->nullable();
            $table->string('content_hash', 64)->nullable()->comment('SHA-256 hash for change detection');

            // ==================== TECHNICAL DETAILS ====================
            $table->string('check_method', 20)->default('GET');
            $table->string('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->string('ip_address', 45)->nullable()->comment('Server IP (IPv4/IPv6)');
            $table->string('server_software')->nullable();

            // ==================== MONITORING CONTEXT ====================
            $table->json('monitor_config')->nullable()->comment('Monitor config at check time');
            $table->unsignedSmallInteger('check_interval_minutes')->nullable();

            // Timestamps
            $table->timestamps();

            // ==================== INDEXES FOR PERFORMANCE ====================
            // CRITICAL: Composite index for dashboard queries (90%+ improvement)
            $table->index(['monitor_id', 'website_id', 'started_at'], 'idx_monitor_website_time');

            // Additional indexes
            $table->index('monitor_id');
            $table->index('website_id');
            $table->index(['check_type', 'status', 'started_at'], 'idx_check_type_status');
            $table->index(['trigger_type', 'started_at'], 'idx_trigger_type');
            $table->index(['status', 'started_at'], 'idx_status_time');
            $table->index(['certificate_expiration_date', 'ssl_status'], 'idx_ssl_expiration');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_results');
    }
};
```

**Important**: Repeat this pattern for the other 3 migrations, copying schemas from the master plan.

### Step 4: Create Eloquent Models (4 files)

```bash
./vendor/bin/sail artisan make:model MonitoringResult
./vendor/bin/sail artisan make:model MonitoringCheckSummary
./vendor/bin/sail artisan make:model MonitoringAlert
./vendor/bin/sail artisan make:model MonitoringEvent
```

**For each model**, copy the complete code from `docs/HISTORICAL_DATA_MASTER_PLAN.md` section "📦 Laravel Components Specification".

Each model must include:
- ✅ All `$fillable` fields
- ✅ All `$casts` (especially datetime with milliseconds: `'datetime:Y-m-d H:i:s.v'`)
- ✅ All relationships (`belongsTo`)
- ✅ All scopes (e.g., `scopeSuccessful`, `scopeManual`)
- ✅ UUID generation in `boot()` method for MonitoringResult

### Step 5: Run Migrations

```bash
# Run migrations
./vendor/bin/sail artisan migrate

# Verify tables exist
./vendor/bin/sail artisan tinker
>>> Schema::hasTable('monitoring_results')  // Should be true
>>> Schema::hasTable('monitoring_check_summaries')  // Should be true
>>> Schema::hasTable('monitoring_alerts')  // Should be true
>>> Schema::hasTable('monitoring_events')  // Should be true
```

### Step 6: Write Migration Tests

Create `tests/Feature/MigrationTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

test('monitoring_results table exists with all columns', function () {
    expect(Schema::hasTable('monitoring_results'))->toBeTrue();

    expect(Schema::hasColumns('monitoring_results', [
        'id', 'uuid', 'monitor_id', 'website_id', 'check_type', 'trigger_type',
        'triggered_by_user_id', 'started_at', 'completed_at', 'duration_ms',
        'status', 'error_message', 'uptime_status', 'http_status_code',
        'response_time_ms', 'ssl_status', 'certificate_issuer',
        'created_at', 'updated_at'
    ]))->toBeTrue();
});

test('monitoring_check_summaries table exists', function () {
    expect(Schema::hasTable('monitoring_check_summaries'))->toBeTrue();
});

test('monitoring_alerts table exists', function () {
    expect(Schema::hasTable('monitoring_alerts'))->toBeTrue();
});

test('monitoring_events table exists', function () {
    expect(Schema::hasTable('monitoring_events'))->toBeTrue();
});

test('monitoring_results has foreign key constraints', function () {
    $foreignKeys = Schema::getForeignKeys('monitoring_results');

    $monitorFkExists = collect($foreignKeys)->contains(
        fn($fk) => $fk['columns'] === ['monitor_id'] && $fk['foreign_table'] === 'monitors'
    );

    $websiteFkExists = collect($foreignKeys)->contains(
        fn($fk) => $fk['columns'] === ['website_id'] && $fk['foreign_table'] === 'websites'
    );

    expect($monitorFkExists)->toBeTrue();
    expect($websiteFkExists)->toBeTrue();
});

test('monitoring_results has critical composite index', function () {
    $indexes = Schema::getIndexes('monitoring_results');

    $compositeIndexExists = collect($indexes)->contains(
        fn($idx) => $idx['name'] === 'idx_monitor_website_time'
    );

    expect($compositeIndexExists)->toBeTrue();
});
```

### Step 7: Write Model Tests

Create `tests/Feature/MonitoringModelsTest.php`:

```php
<?php

use App\Models\MonitoringResult;
use App\Models\Monitor;
use App\Models\Website;
use App\Models\User;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

test('MonitoringResult can be created with basic data', function () {
    $monitor = Monitor::factory()->create();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'success',
    ]);

    expect($result)->toBeInstanceOf(MonitoringResult::class);
    expect($result->uuid)->not->toBeNull();
    expect($result->monitor_id)->toBe($monitor->id);
});

test('MonitoringResult generates UUID automatically', function () {
    $monitor = Monitor::factory()->create();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->uuid)->not->toBeNull();
    expect(strlen($result->uuid))->toBe(36);
});

test('MonitoringResult belongs to monitor', function () {
    $monitor = Monitor::factory()->create();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->monitor)->toBeInstanceOf(Monitor::class);
    expect($result->monitor->id)->toBe($monitor->id);
});

test('MonitoringResult belongs to website', function () {
    $monitor = Monitor::factory()->create();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->website)->toBeInstanceOf(Website::class);
    expect($result->website->id)->toBe($monitor->website_id);
});

test('MonitoringResult successful scope works', function () {
    $monitor = Monitor::factory()->create();

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'failed',
    ]);

    expect(MonitoringResult::successful()->count())->toBe(1);
});

test('MonitoringResult manual scope works', function () {
    $monitor = Monitor::factory()->create();

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'manual_immediate',
        'started_at' => now(),
        'status' => 'success',
    ]);

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $monitor->website_id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect(MonitoringResult::manual()->count())->toBe(1);
});
```

### Step 8: Run All Tests

```bash
# Run tests with performance monitoring
time ./vendor/bin/sail artisan test --parallel

# MUST meet these requirements:
# - All tests passing (530+ tests)
# - Execution time < 20 seconds
# - No failures
```

### Step 9: Verify Database Schema

```bash
./vendor/bin/sail artisan tinker

# Test MonitoringResult creation
>>> $monitor = Monitor::first();
>>> $result = MonitoringResult::create([
...   'monitor_id' => $monitor->id,
...   'website_id' => $monitor->website_id,
...   'check_type' => 'both',
...   'trigger_type' => 'scheduled',
...   'started_at' => now(),
...   'completed_at' => now(),
...   'status' => 'success',
...   'response_time_ms' => 150,
... ]);
>>> $result->uuid  // Should have a UUID
>>> $result->monitor  // Should return Monitor instance
>>> $result->website  // Should return Website instance

# Verify indexes
>>> Schema::getIndexes('monitoring_results');

# Check for composite index
>>> collect(Schema::getIndexes('monitoring_results'))->pluck('name')->toArray()
// Should include 'idx_monitor_website_time'
```

## ✅ Phase 1 Completion Checklist

Before marking Phase 1 complete, verify:

- [ ] 4 database migrations created and run successfully
- [ ] All 4 tables exist in database
- [ ] Critical composite index `idx_monitor_website_time` exists on monitoring_results
- [ ] All foreign key constraints are in place
- [ ] 4 Eloquent models created with all relationships
- [ ] UUID generation works on MonitoringResult
- [ ] All model casts are configured (especially datetime with milliseconds)
- [ ] All scopes implemented and tested
- [ ] Migration tests created and passing
- [ ] Model relationship tests created and passing
- [ ] Full test suite passing (530+ tests)
- [ ] Test execution time < 20 seconds
- [ ] Can create MonitoringResult via tinker
- [ ] Models can query relationships

## 📊 Success Criteria

**Database**:
- ✅ 4 new tables in database
- ✅ No migration errors
- ✅ All indexes created
- ✅ Foreign keys enforced

**Models**:
- ✅ All relationships work
- ✅ UUIDs auto-generate
- ✅ Casts work correctly
- ✅ Scopes filter properly

**Tests**:
- ✅ All existing tests still pass
- ✅ New migration tests pass
- ✅ New model tests pass
- ✅ Performance maintained (< 20s)

## 🚀 After Phase 1 Completion

Once Phase 1 is complete and verified:

1. **Commit your work**:
```bash
git add -A
git commit -m "feat: implement Phase 1 - historical data tracking foundation

- Create 4 database tables with optimized schema
- Implement 4 Eloquent models with relationships
- Add migration and model tests
- All 530+ tests passing in < 20s

Phase 1 of historical data tracking complete.
Tables: monitoring_results, monitoring_check_summaries,
monitoring_alerts, monitoring_events"
```

2. **Proceed to Phase 2** using the prompt in `docs/PHASE2_IMPLEMENTATION_PROMPT.md` (will be created after Phase 1)

## ⚠️ Common Issues & Solutions

**Issue**: Migration fails with "Table already exists"
**Solution**:
```bash
./vendor/bin/sail artisan migrate:rollback
./vendor/bin/sail artisan migrate
```

**Issue**: Foreign key constraint fails
**Solution**: Ensure `monitors` and `websites` tables exist first. Check migration order.

**Issue**: Tests fail with "Class not found"
**Solution**:
```bash
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail artisan clear-compiled
```

**Issue**: UUID not generating
**Solution**: Verify `boot()` method in MonitoringResult model includes:
```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (! $model->uuid) {
            $model->uuid = (string) Str::uuid();
        }
    });
}
```

**Issue**: Test suite takes > 20s
**Solution**: Ensure you're using `--parallel` flag and have proper database configuration.

## 📚 Reference Materials

During implementation, refer to:
- `docs/HISTORICAL_DATA_MASTER_PLAN.md` - Complete schemas and code
- `docs/HISTORICAL_DATA_QUICK_START.md` - Quick commands
- `docs/TESTING_INSIGHTS.md` - Testing patterns
- `docs/DEVELOPMENT_PRIMER.md` - Development workflow

## 🎯 Ready to Start?

Copy this entire prompt and use it to begin Phase 1 implementation. Follow each step carefully and verify at each checkpoint.

**Estimated Time**: 4-6 hours for complete Phase 1 implementation

**Next Phase**: After Phase 1 completion, Phase 2 will implement the event system and data capture integration.

---

Good luck with Phase 1! 🚀
