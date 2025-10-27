# Historical Monitoring Data Implementation

## Issue
Historical monitoring data existed in the `monitoring_results` table (82 records) but was not displaying on website detail pages in production at monitor.intermedien.at.

## Root Cause
The application was missing:
1. Relationship between `Website` model and `MonitoringResult` model
2. API endpoints to fetch historical monitoring data
3. Data transformation layer (API Resources)
4. Integration with the `show()` method in WebsiteController

## Solution Implemented

### 1. Added Model Relationship
**File**: `/home/bonzo/code/ssl-monitor-v4/app/Models/Website.php`

Added `monitoringResults()` HasMany relationship to Website model:
```php
public function monitoringResults(): HasMany
{
    return $this->hasMany(MonitoringResult::class);
}
```

### 2. Created API Resource for Data Transformation
**File**: `/home/bonzo/code/ssl-monitor-v4/app/Http/Resources/MonitoringResultResource.php`

Created `MonitoringResultResource` to properly format historical data for frontend consumption:
- Transforms all monitoring result fields
- Formats timestamps to ISO8601
- Includes triggered user information when loaded
- Provides structured SSL, uptime, and content validation data

### 3. Added API Endpoints

**File**: `/home/bonzo/code/ssl-monitor-v4/app/Http/Controllers/WebsiteController.php`

#### a. History Endpoint - `GET /ssl/websites/{website}/history`
Returns paginated historical monitoring results with filtering capabilities:
- **Filters**: `check_type` (ssl, uptime, both), `status` (success, failed), `trigger_type` (scheduled, manual_immediate, manual_bulk), `days` (1-365)
- **Pagination**: Supports `limit` parameter (1-500, default 50)
- **Response**: JSON with data, meta, and filters

Example usage:
```bash
GET /ssl/websites/1/history?check_type=ssl&status=success&days=30&limit=100
```

#### b. Statistics Endpoint - `GET /ssl/websites/{website}/statistics`
Returns aggregated monitoring statistics:
- Total checks, successful/failed counts
- Success rate percentage
- Average response time for uptime checks
- Average SSL days until expiration
- Breakdown by check type (SSL vs uptime)
- Breakdown by trigger type (manual vs scheduled)

Example usage:
```bash
GET /ssl/websites/1/statistics?days=30
```

### 4. Updated WebsiteController show() Method
**File**: `/home/bonzo/code/ssl-monitor-v4/app/Http/Controllers/WebsiteController.php`

Enhanced the `show()` method to include:
- Last 30 days of monitoring history (limit 100 records)
- Basic statistics (total checks, success rate, avg response time)
- Loaded with `triggeredBy` relationship for user context

Frontend now receives:
```php
[
    // ... existing website data ...
    'monitoring_history' => MonitoringResultResource::collection($recentResults),
    'monitoring_stats' => [
        'total_checks' => 23,
        'successful_checks' => 23,
        'failed_checks' => 0,
        'success_rate' => 100.0,
        'avg_response_time_ms' => 234.5,
    ],
]
```

### 5. Added Routes
**File**: `/home/bonzo/code/ssl-monitor-v4/routes/web.php`

```php
// Historical monitoring data routes
Route::get('websites/{website}/history', [WebsiteController::class, 'history'])
    ->middleware('cache.api:60')
    ->name('ssl.websites.history');

Route::get('websites/{website}/statistics', [WebsiteController::class, 'statistics'])
    ->middleware('cache.api:120')
    ->name('ssl.websites.statistics');
```

Routes include caching middleware:
- History endpoint: 60-second cache
- Statistics endpoint: 120-second cache

### 6. Added Comprehensive Tests
**File**: `/home/bonzo/code/ssl-monitor-v4/tests/Feature/WebsiteHistoryTest.php`

Created 11 comprehensive tests covering:
- Relationship functionality
- History endpoint with various filters
- Statistics endpoint with date filtering
- Authentication requirements
- Authorization checks
- Integration with show() method

**Test Results**: All 11 tests pass (101 assertions)

## Verification

### Production Data Confirmed
```
Website: Office Manager Pro (https://omp.office-manager-pro.com)
Total monitoring results: 23
Recent checks successfully retrieved
```

### Full Test Suite
```
Tests: 575 passed, 13 skipped, 1 warning
Duration: 8.87s (parallel)
```

## API Endpoints Available for Frontend

### 1. Get Historical Data
```
GET /ssl/websites/{id}/history
Query Parameters:
  - limit: int (1-500, default 50)
  - check_type: ssl|uptime|both
  - status: success|failed
  - trigger_type: scheduled|manual_immediate|manual_bulk
  - days: int (1-365)
```

### 2. Get Statistics
```
GET /ssl/websites/{id}/statistics
Query Parameters:
  - days: int (1-365, default 30)
```

### 3. Website Detail Page (show)
```
GET /ssl/websites/{id}
Returns Inertia response with:
  - monitoring_history: Last 30 days (limit 100)
  - monitoring_stats: Aggregated statistics
```

## Deployment Notes

### Database
✅ No migrations required - uses existing `monitoring_results` table

### Dependencies
✅ No new dependencies added - uses Laravel's built-in features

### Performance
- History endpoint: Cached for 60 seconds
- Statistics endpoint: Cached for 120 seconds
- Optimized queries with proper relationships and eager loading
- Individual tests complete in < 0.24s

### Code Quality
✅ All code formatted with Laravel Pint
✅ Follows Laravel conventions and PSR-12
✅ Full test coverage with 101 assertions

## Next Steps for Frontend

The backend is now production-ready. Frontend needs to:

1. **Update Website Detail Page** (`resources/js/Pages/Ssl/Websites/Show.vue`):
   - Access `props.website.monitoring_history` for historical data
   - Access `props.website.monitoring_stats` for statistics
   - Display charts/graphs using the provided data

2. **Optional: Add Dedicated History Component**:
   - Use `/ssl/websites/{id}/history` endpoint for paginated browsing
   - Implement filters for check_type, status, trigger_type
   - Add date range selector using `days` parameter

3. **Optional: Add Statistics Dashboard**:
   - Use `/ssl/websites/{id}/statistics` endpoint
   - Display success rates, average response times
   - Show SSL certificate health trends

## Files Modified

1. `/home/bonzo/code/ssl-monitor-v4/app/Models/Website.php`
2. `/home/bonzo/code/ssl-monitor-v4/app/Http/Controllers/WebsiteController.php`
3. `/home/bonzo/code/ssl-monitor-v4/routes/web.php`

## Files Created

1. `/home/bonzo/code/ssl-monitor-v4/app/Http/Resources/MonitoringResultResource.php`
2. `/home/bonzo/code/ssl-monitor-v4/tests/Feature/WebsiteHistoryTest.php`

## Ready for Production Deployment

✅ All tests passing
✅ Code formatted and linted
✅ Production data verified
✅ API endpoints documented
✅ Performance optimized with caching

The implementation is complete and ready to be deployed to production. Frontend developers can now access historical monitoring data through the new API endpoints or the enhanced `show()` method.
