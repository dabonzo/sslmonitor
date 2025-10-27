# Historical Monitoring Data API Testing Guide

## Production Environment
Base URL: `https://monitor.intermedien.at`

## Authentication
All endpoints require authentication. You need to be logged in as the website owner or have team access.

## Available Endpoints

### 1. Website Detail with Historical Data (Inertia)
```bash
GET /ssl/websites/{website_id}
```

**Returns**: Inertia response with website details + historical data

**Response Structure**:
```json
{
  "website": {
    "id": 1,
    "name": "Office Manager Pro",
    "url": "https://omp.office-manager-pro.com",
    "monitoring_history": [
      {
        "id": 80,
        "uuid": "...",
        "check_type": "both",
        "status": "success",
        "started_at": "2025-10-19T18:00:00Z",
        "completed_at": "2025-10-19T18:00:02Z",
        "duration_ms": 2000,
        "uptime_status": "up",
        "http_status_code": 200,
        "response_time_ms": 234,
        "ssl_status": "valid",
        "certificate_expiration_date": "2026-01-15T00:00:00Z",
        "days_until_expiration": 88
      }
    ],
    "monitoring_stats": {
      "total_checks": 23,
      "successful_checks": 23,
      "failed_checks": 0,
      "success_rate": 100.0,
      "avg_response_time_ms": 234.5
    }
  }
}
```

### 2. Get Historical Monitoring Results (JSON API)
```bash
GET /ssl/websites/{website_id}/history
```

**Query Parameters**:
- `limit` (optional): Number of results per page (1-500, default: 50)
- `check_type` (optional): Filter by type - `ssl`, `uptime`, or `both`
- `status` (optional): Filter by status - `success` or `failed`
- `trigger_type` (optional): Filter by trigger - `scheduled`, `manual_immediate`, `manual_bulk`
- `days` (optional): Only show results from last N days (1-365)

**Example Requests**:

Get last 100 checks:
```bash
curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?limit=100" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

Get SSL checks from last 7 days:
```bash
curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?check_type=ssl&days=7" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

Get only failed checks:
```bash
curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?status=failed" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

**Response Structure**:
```json
{
  "data": [
    {
      "id": 80,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "check_type": "both",
      "trigger_type": "scheduled",
      "status": "success",
      "started_at": "2025-10-19T18:00:00.000000Z",
      "completed_at": "2025-10-19T18:00:02.500000Z",
      "duration_ms": 2500,
      "error_message": null,
      "uptime_status": "up",
      "http_status_code": 200,
      "response_time_ms": 234,
      "response_body_size_bytes": 45678,
      "redirect_count": 0,
      "final_url": "https://omp.office-manager-pro.com",
      "ssl_status": "valid",
      "certificate_issuer": "Let's Encrypt",
      "certificate_subject": "omp.office-manager-pro.com",
      "certificate_expiration_date": "2026-01-15T00:00:00.000000Z",
      "certificate_valid_from_date": "2025-10-15T00:00:00.000000Z",
      "days_until_expiration": 88,
      "content_validation_enabled": false,
      "content_validation_status": null,
      "expected_strings_found": null,
      "forbidden_strings_found": null,
      "regex_matches": null,
      "javascript_rendered": false,
      "javascript_wait_seconds": null,
      "content_hash": "abc123...",
      "check_method": "automated",
      "user_agent": "SSL Monitor Bot",
      "ip_address": "1.2.3.4",
      "server_software": "nginx",
      "triggered_by_user_id": null,
      "triggered_by": null
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 50,
    "to": 23,
    "total": 23
  },
  "filters": {
    "check_type": null,
    "status": null,
    "trigger_type": null,
    "days": null
  }
}
```

### 3. Get Monitoring Statistics (JSON API)
```bash
GET /ssl/websites/{website_id}/statistics
```

**Query Parameters**:
- `days` (optional): Calculate stats for last N days (1-365, default: 30)

**Example Requests**:

Get last 30 days statistics:
```bash
curl -X GET "https://monitor.intermedien.at/ssl/websites/1/statistics" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

Get last 7 days statistics:
```bash
curl -X GET "https://monitor.intermedien.at/ssl/websites/1/statistics?days=7" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie"
```

**Response Structure**:
```json
{
  "website_id": 1,
  "website_name": "Office Manager Pro",
  "website_url": "https://omp.office-manager-pro.com",
  "period_days": 30,
  "statistics": {
    "total_checks": 23,
    "successful_checks": 23,
    "failed_checks": 0,
    "avg_response_time_ms": 234.5,
    "avg_ssl_days_until_expiration": 88.0,
    "ssl_checks": 0,
    "uptime_checks": 0,
    "manual_checks": 3,
    "scheduled_checks": 20,
    "success_rate": 100.0
  }
}
```

## Production Testing Checklist

### Before Deployment
- [x] All tests passing (575 tests)
- [x] Code formatted with Laravel Pint
- [x] Routes registered correctly
- [x] Relationships working with production data
- [x] API resources properly transforming data
- [x] Caching middleware applied

### After Deployment
Test these endpoints on production:

1. **Test Authentication**:
   - Visit https://monitor.intermedien.at/ssl/websites/1
   - Verify you see `monitoring_history` and `monitoring_stats` in Inertia props

2. **Test History Endpoint**:
   ```bash
   curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?limit=10"
   ```
   Expected: 200 OK with JSON response containing historical data

3. **Test Statistics Endpoint**:
   ```bash
   curl -X GET "https://monitor.intermedien.at/ssl/websites/1/statistics?days=7"
   ```
   Expected: 200 OK with JSON response containing aggregated statistics

4. **Test Filters**:
   ```bash
   curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?status=success&days=7"
   ```
   Expected: 200 OK with filtered results

5. **Test Pagination**:
   ```bash
   curl -X GET "https://monitor.intermedien.at/ssl/websites/1/history?limit=5"
   ```
   Expected: 200 OK with meta.total showing full count, data showing 5 items

## Caching Information

Both endpoints use response caching:
- **History endpoint**: Cached for 60 seconds
- **Statistics endpoint**: Cached for 120 seconds

To clear cache:
```bash
php artisan cache:clear
```

## Error Handling

### 401 Unauthorized
User not logged in. Redirect to login page.

### 403 Forbidden
User doesn't have permission to view this website. Check team membership or ownership.

### 404 Not Found
Website with the given ID doesn't exist.

### 422 Unprocessable Entity
Invalid query parameters. Check parameter values match allowed types.

## Production Verification

Expected data for production database:
- 82 total monitoring results across 4 websites
- Website IDs 1-4 each have 19-23 historical checks
- All checks should have `check_type` of "both" (SSL + uptime combined)
- Success rate should be 100%

## Performance Metrics

- Individual tests: < 0.24s
- Full test suite: 8.87s (parallel, 575 tests)
- API response time: < 100ms (with cache)
- Database queries: Optimized with eager loading

## Support

If historical data is not displaying:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database has `monitoring_results` records
3. Clear cache: `php artisan cache:clear`
4. Check browser console for frontend errors
5. Test API endpoints directly with curl

## Related Files

- Implementation: `/home/bonzo/code/ssl-monitor-v4/HISTORICAL_DATA_IMPLEMENTATION.md`
- Tests: `/home/bonzo/code/ssl-monitor-v4/tests/Feature/WebsiteHistoryTest.php`
- Controller: `/home/bonzo/code/ssl-monitor-v4/app/Http/Controllers/WebsiteController.php`
- Resource: `/home/bonzo/code/ssl-monitor-v4/app/Http/Resources/MonitoringResultResource.php`
