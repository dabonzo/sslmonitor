# SSL Monitor v4 - Monitoring Data Tracking Phase 2 Context

**Session Purpose**: Continue implementation of comprehensive monitoring data tracking system
**Current Phase**: Phase 2 - Data Capture Integration
**Previous Session**: October 14, 2025
**Document Version**: 1.1

---

## 🎯 **Current Status**

### **Phase 1 ✅ COMPLETED**
- Database schema (3 tables) implemented and tested
- Models (3) with comprehensive functionality created
- Events (4) with rich data capture implemented
- Code quality validated and formatted
- All migrations tested and ready to run

### **Phase 2 🔄 IN PROGRESS**
**Objective**: Integrate event system into existing monitoring jobs to capture real data

---

## 📋 **Immediate Tasks for Phase 2**

### **Priority 1: Modify Existing Jobs**

#### **1. CheckMonitorJob Integration**
**File**: `app/Jobs/CheckMonitorJob.php`
**Target Lines**: 85-120 (main check execution logic)

**Integration Points**:
```php
// BEFORE existing check execution
event(new MonitoringCheckStarted(
    monitor: $this->monitor,
    checkType: 'uptime', // or 'ssl_certificate'
    triggerType: 'scheduled',
    startedAt: now()
));

// AFTER successful check completion
event(new MonitoringCheckCompleted(
    monitor: $this->monitor,
    checkType: 'uptime',
    triggerType: 'scheduled',
    results: $results,
    completedAt: now(),
    startedAt: $startedAt
));

// IN catch blocks for failures
event(new MonitoringCheckFailed(
    monitor: $this->monitor,
    checkType: 'uptime',
    triggerType: 'scheduled',
    exception: $e,
    failedAt: now(),
    startedAt: $startedAt
));
```

**Key Requirements**:
- Maintain backward compatibility
- Capture timing data accurately
- Handle both SSL and uptime checks
- Don't impact existing functionality

#### **2. ImmediateWebsiteCheckJob Integration**
**File**: `app/Jobs/ImmediateWebsiteCheckJob.php`
**Target Lines**: 35-55 (immediate check handling)

**Integration Points**:
- Add `MonitoringCheckStarted` event with user attribution
- Add `MonitoringCheckCompleted` event with results
- Add `MonitoringCheckFailed` event for error handling
- Set `triggerType` to 'manual_immediate'
- Pass current user information

### **Priority 2: Create Event Listeners**

#### **1. RecordMonitoringResult Listener**
**Purpose**: Save monitoring results to database
**File**: `app/Listeners/RecordMonitoringResult.php`

**Core Logic**:
```php
public function handle(MonitoringCheckCompleted $event): void
{
    MonitoringResult::create([
        'uuid' => Str::uuid(),
        'monitor_id' => $event->monitor->id,
        'website_id' => $event->getWebsiteId(),
        'check_type' => $event->checkType,
        'trigger_type' => $event->triggerType,
        'triggered_by_user_id' => $event->getTriggeredUser()?->id,
        'started_at' => $event->startedAt,
        'completed_at' => $event->completedAt,
        'duration_ms' => $event->getDurationMs(),
        'status' => $event->getStatus(),
        // Map uptime and SSL results to appropriate fields
        // Handle both success and failure cases
    ]);
}
```

#### **2. UpdateMonitoringSummaries Listener**
**Purpose**: Calculate and update aggregated data
**File**: `app/Listeners/UpdateMonitoringSummaries.php`

**Core Logic**:
- Listen to `MonitoringBatchCompleted` events
- Calculate hourly/daily/weekly/monthly summaries
- Update `monitoring_check_summaries` table
- Handle edge cases for data consistency

#### **3. CheckAlertConditions Listener**
**Purpose**: Evaluate and create alerts based on monitoring results
**File**: `app/Listeners/CheckAlertConditions.php`

**Core Logic**:
- Listen to `MonitoringCheckCompleted` events
- Use `$event->shouldTriggerAlerts()` and `$event->getAlertType()`
- Create `MonitoringAlert` records
- Set appropriate severity levels
- Handle alert deduplication

### **Priority 3: Register Event Listeners**

**File**: `app/Providers/EventServiceProvider.php`

**Registration**:
```php
protected $listen = [
    MonitoringCheckCompleted::class => [
        RecordMonitoringResult::class,
        CheckAlertConditions::class,
    ],
    MonitoringBatchCompleted::class => [
        UpdateMonitoringSummaries::class,
    ],
    // Add other event listeners as needed
];
```

---

## 🔧 **Integration Strategy**

### **Data Flow Integration**
1. **Existing Flow**: `Scheduler → CheckMonitorJob → Monitor Model → Results`
2. **New Flow**: `Scheduler → CheckMonitorJob → Events → Listeners → Database + Models`

### **Key Integration Points**
- **SSL Certificate Data**: `$monitor->checkCertificate()` method
- **Uptime Data**: `$monitor->collection->checkUptime()` method
- **User Context**: Available in `ImmediateWebsiteCheckJob`
- **Timing Data**: Capture before/after check execution

### **Backward Compatibility Requirements**
- Existing monitoring functionality must continue unchanged
- No breaking changes to public APIs
- Graceful error handling for event failures
- Fallback to existing behavior if event system fails

---

## 📊 **Data Capture Requirements**

### **Essential Fields to Capture**
From `MonitoringCheckCompleted` events:
- `monitor_id`, `website_id` - Relationships
- `check_type`, `trigger_type` - Classification
- `started_at`, `completed_at`, `duration_ms` - Timing
- `status` - Success/failure status

### **Uptime-Specific Data**
- `uptime_status`, `http_status_code`, `response_time_ms`
- `response_body_size_bytes`, `redirect_count`, `final_url`
- `ip_address`, `server_software`, `response_headers`

### **SSL-Specific Data**
- `ssl_status`, `certificate_issuer`, `certificate_subject`
- `certificate_expiration_date`, `days_until_expiration`
- `certificate_chain`, `certificate_valid_from_date`

### **Context Data**
- `monitor_config` - Configuration snapshot at check time
- `check_interval_minutes` - Current monitoring interval
- `triggered_by_user_id` - User who triggered manual checks

---

## 🧪 **Testing Strategy for Phase 2**

### **Integration Tests Required**
1. **CheckMonitorJob Integration**
   - Verify events fire correctly during normal operation
   - Confirm no performance degradation
   - Test error handling scenarios

2. **ImmediateWebsiteCheckJob Integration**
   - Verify manual check event firing
   - Test user attribution
   - Confirm backward compatibility

3. **Event Listener Testing**
   - Test data recording accuracy
   - Verify summary calculations
   - Test alert condition evaluation

4. **End-to-End Testing**
   - Complete monitoring workflow
   - Data consistency validation
   - Performance benchmarking

### **Performance Requirements**
- No measurable impact on existing check execution time
- Event processing should complete within 1 second
- Database writes should not block monitoring jobs
- Queue processing should handle high volume efficiently

---

## 🚨 **Risk Mitigation for Phase 2**

### **High-Risk Areas**
1. **Existing Job Modification** - Risk of breaking current functionality
   - **Mitigation**: Add events without removing existing code
   - **Fallback**: Wrap event firing in try-catch blocks

2. **Performance Impact** - Risk of slowing down monitoring
   - **Mitigation**: Use asynchronous queue for event processing
   - **Monitoring**: Track job execution times before/after

3. **Data Consistency** - Risk of partial data recording
   - **Mitigation**: Robust error handling in listeners
   - **Validation**: Data integrity checks

### **Rollback Strategy**
- Keep original job code intact
- Use feature flags if needed for gradual rollout
- Database migrations are additive (no destructive changes)
- Event system can be disabled if issues arise

---

## 📈 **Success Criteria for Phase 2**

### **Functional Requirements**
- [ ] All monitoring checks generate appropriate events
- [ ] Monitoring results recorded in database with 100% accuracy
- [ ] Manual vs automatic checks properly distinguished
- [ ] No performance degradation in existing monitoring

### **Data Quality Requirements**
- [ ] All required fields captured for both SSL and uptime checks
- [ ] Timestamps accurate to millisecond precision
- [ ] User attribution working for manual checks
- [ ] Error handling preserves partial results when possible

### **Technical Requirements**
- [ ] Event processing completes within SLA
- [ ] Database performance remains acceptable
- [ ] Error handling doesn't break monitoring
- [ ] Queue processing handles expected volume

---

## 🎯 **Next Steps After Phase 2**

Once Phase 2 is complete and tested:

1. **Phase 3**: Enhanced data capture (certificate chains, content validation, JavaScript rendering)
2. **Phase 4**: Summary calculation and analytics integration
3. **Phase 5**: Advanced features (API endpoints, reporting, performance optimization)

---

---

## 📚 **Essential Documentation References**

### **Primary Documentation**
1. **`docs/comprehensive-monitoring-data-tracking-plan.md`** - Complete implementation plan with Phase 1 status and Phase 2 requirements
2. **`docs/DEVELOPMENT_PRIMER.md`** - Essential codebase understanding and development workflow
3. **`CLAUDE.md`** - Project-specific guidelines and development commands

### **Coding Standards**
4. **`~/.claude/laravel-php-guidelines.md`** - Laravel & PHP coding standards (PSR-1, PSR-2, PSR-12)
   - Follow Laravel conventions first
   - Use typed properties over docblocks
   - Specify return types including `void`
   - Use short nullable syntax: `?Type` not `Type|null`
   - Constructor property promotion when all properties can be promoted

### **Styling Guidelines**
5. **`docs/STYLING_GUIDE.md`** - Application styling guide and standards
   - Semantic color system (use `text-foreground`, `bg-card` vs hardcoded colors)
   - Component patterns and layout structure
   - Dark mode consistency and accessibility requirements
   - Typography and utility classes
   - Vue 3 + Inertia.js + TailwindCSS v4 stack

### **Testing Standards**
6. **From DEVELOPMENT_PRIMER**: Performance testing requirements
   - Individual tests MUST complete in < 1 second
   - Use `MocksSslCertificateAnalysis` trait for SSL operations
   - Use `MocksJavaScriptContentFetcher` trait for JS content
   - Never make real network calls in tests

### **MCP Tools Available**
7. **Laravel Boost MCP** - For backend verification:
   - `mcp__laravel-boost__application-info` - App overview
   - `mcp__laravel-boost__database-schema` - Database structure (use `database: "mariadb"`)
   - `mcp__laravel-boost__list-routes` - Route inspection
   - `mcp__laravel-boost__tinker` - PHP code testing

8. **Playwright Extension MCP** - For frontend verification:
   - `mcp__playwright-extension__browser_navigate` - Page navigation
   - `mcp__playwright-extension__browser_snapshot` - Page state inspection
   - `mcp__playwright-extension__browser_console_messages` - JS error checking

### **Essential Development Commands**
```bash
# Environment
./vendor/bin/sail up -d
./vendor/bin/sail composer install && ./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Testing
./vendor/bin/sail artisan test --parallel
./vendor/bin/sail artisan test --filter=TestName

# Code Quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint
./vendor/bin/sail npm run lint

# Cache Management (CRITICAL after changes)
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail artisan route:clear
```

### **Critical Architecture Notes**
- **Always use `App\Models\Monitor`**, never `Spatie\UptimeMonitor\Models\Monitor`
- **Environment variables** in `.env` override hardcoded values
- **Cache management** critical after frontend/CSS changes
- **Team-aware** website management
- **Performance testing** must avoid real network calls

**Continue with**: Modify `app/Jobs/CheckMonitorJob.php` to integrate `MonitoringCheckStarted` event at the beginning of the `handle()` method, maintaining backward compatibility while capturing timing data.

**Context**: All foundation infrastructure (database, models, events) is complete and tested. Ready for seamless integration into existing monitoring system with full documentation references available.