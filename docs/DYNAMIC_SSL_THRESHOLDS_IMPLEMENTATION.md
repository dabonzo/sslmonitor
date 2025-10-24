# Dynamic SSL Expiration Threshold Implementation

## Overview

Implement intelligent, dynamic SSL certificate expiration status determination based on certificate validity period percentage rather than hardcoded day thresholds.

**Problem**: Current system marks certificates as "expires_soon" when < 30 days remaining, regardless of certificate type:
- Let's Encrypt (90-day): 73 days remaining = 81% of lifetime = **HEALTHY** ✅ (wrongly marked expires_soon)
- 1-year commercial (365-day): 73 days remaining = 20% of lifetime = **CRITICAL** ⚠️ (correctly marked)
- 2-year commercial (730-day): 73 days remaining = 10% of lifetime = **VERY CRITICAL** 🚨 (correctly marked)

**Solution**: Calculate expiration status based on percentage of total certificate validity remaining, with sensible minimum thresholds.

## Current Implementation

### File: `app/Jobs/CheckMonitorJob.php`

**Lines 263-270** - Hardcoded SSL status logic:
```php
// Determine status based on Spatie's certificate data
$status = 'valid';
if ($this->monitor->certificate_status === 'invalid') {
    $status = 'invalid';
} elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->isPast()) {
    $status = 'expired';
} elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->diffInDays() <= 30) {
    $status = 'expires_soon';  // ❌ HARDCODED 30 DAYS
}
```

**Lines 353-360** - Duplicate logic in `getLastSslResult()`:
```php
$status = 'valid';
if ($this->monitor->certificate_status === 'invalid') {
    $status = 'invalid';
} elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->isPast()) {
    $status = 'expired';
} elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->diffInDays() <= 30) {
    $status = 'expires_soon';  // ❌ DUPLICATE HARDCODED LOGIC
}
```

## Requirements

### Functional Requirements

1. **Extract Certificate Valid From Date**
   - Modify `extractCertificateSubject()` or create companion method to extract `validFrom_s` from OpenSSL certificate
   - Store as Carbon instance for easy date calculations

2. **Dynamic Threshold Calculation**
   - Calculate total certificate validity period (days)
   - Calculate percentage of validity remaining
   - Apply intelligent thresholds:
     - `expires_soon`: < 33% remaining **OR** < 30 days (whichever is more conservative)
     - `critical` (optional): < 10% remaining **OR** < 7 days

3. **Backward Compatibility**
   - Must work with existing monitoring results that don't have `valid_from` data (use fallback logic)
   - Existing tests should continue to pass with minimal modifications

4. **Store Metadata**
   - Add `certificate_valid_from_date` to monitoring results
   - Optionally store calculated values for transparency:
     - `validity_days_total`
     - `validity_percent_remaining`

### Technical Requirements

1. **Performance**: No additional network calls (extract from existing SSL connection)
2. **Error Handling**: Graceful fallback to 30-day threshold if `valid_from` unavailable
3. **Testing**: Comprehensive unit tests for all certificate validity scenarios
4. **Documentation**: Update inline docs and create user-facing documentation

## Implementation Plan

### Phase 1: Extract Certificate Valid From Date

**File**: `app/Jobs/CheckMonitorJob.php`

**Task 1.1**: Modify `extractCertificateSubject()` to also extract and return `valid_from` date

```php
/**
 * Extract certificate subject (CN + SANs) and validity dates.
 *
 * @return array{subject: ?string, valid_from: ?Carbon, expires_at: ?Carbon}
 */
private function extractCertificateData(): array
{
    try {
        // ... existing stream_socket_client code ...

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

        // Extract subject (existing logic)
        $domains = [];
        if (isset($cert['subject']['CN'])) {
            $domains[] = $cert['subject']['CN'];
        }
        // ... existing SAN extraction ...

        // Extract validity dates
        $validFrom = isset($cert['validFrom_time_t'])
            ? Carbon::createFromTimestamp($cert['validFrom_time_t'])
            : null;

        $expiresAt = isset($cert['validTo_time_t'])
            ? Carbon::createFromTimestamp($cert['validTo_time_t'])
            : null;

        return [
            'subject' => !empty($domains) ? implode(', ', $domains) : null,
            'valid_from' => $validFrom,
            'expires_at' => $expiresAt,
        ];

    } catch (\Throwable $exception) {
        AutomationLogger::error(
            "Failed to extract certificate data for monitor: {$this->monitor->url}",
            ['monitor_id' => $this->monitor->id],
            $exception
        );

        return [
            'subject' => null,
            'valid_from' => null,
            'expires_at' => null,
        ];
    }
}
```

**Task 1.2**: Update `checkSsl()` to call new method and store dates

```php
// Extract certificate data (subject + validity dates)
$certificateData = $this->extractCertificateData();

$result = [
    'status' => $status,
    'expires_at' => $this->monitor->certificate_expiration_date?->toISOString(),
    'issuer' => $this->monitor->certificate_issuer ?? 'Unknown',
    'certificate_status' => $this->monitor->certificate_status,
    'certificate_subject' => $certificateData['subject'],
    'certificate_valid_from' => $certificateData['valid_from']?->toISOString(),
    'failure_reason' => $this->monitor->certificate_check_failure_reason,
    'checked_at' => Carbon::now()->toISOString(),
    'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
];
```

**Task 1.3**: Update `$checkResults` array to include `certificate_valid_from_date`

```php
// Gather check results for historical tracking
$checkResults = [
    'check_type' => $this->checkType === 'ssl' ? 'ssl_certificate' : $this->checkType,
    'status' => $this->determineOverallStatus($results),
    'uptime_status' => $results['uptime']['status'] ?? null,
    'http_status_code' => $results['uptime']['status_code'] ?? null,
    'ssl_status' => $results['ssl']['status'] ?? null,
    'certificate_subject' => $results['ssl']['certificate_subject'] ?? null,
    'certificate_valid_from_date' => $results['ssl']['certificate_valid_from'] ?? null,
    'days_until_expiration' => $this->calculateDaysUntilExpiration(),
];
```

### Phase 2: Implement Dynamic Status Determination

**File**: `app/Jobs/CheckMonitorJob.php`

**Task 2.1**: Create new method `determineSslStatus()`

```php
/**
 * Determine SSL certificate status based on dynamic thresholds.
 *
 * Uses percentage-based thresholds that adapt to certificate validity period:
 * - Short-lived certs (e.g., Let's Encrypt 90-day): 33% = 30 days
 * - Long-lived certs (e.g., 1-year commercial): 33% = 120 days
 *
 * @param Carbon|null $expiresAt Certificate expiration date
 * @param Carbon|null $validFrom Certificate issue date (optional for backward compatibility)
 * @param string $certificateStatus Spatie's certificate status (valid/invalid)
 * @return string Status: 'valid', 'expires_soon', 'expired', 'invalid'
 */
private function determineSslStatus(
    ?Carbon $expiresAt,
    ?Carbon $validFrom,
    string $certificateStatus
): string {
    // Handle invalid certificate
    if ($certificateStatus === 'invalid') {
        return 'invalid';
    }

    // Handle missing expiration date
    if (!$expiresAt) {
        return 'valid';
    }

    // Handle expired certificate
    if ($expiresAt->isPast()) {
        return 'expired';
    }

    $daysRemaining = now()->diffInDays($expiresAt, false);

    // If we have valid_from date, use percentage-based thresholds
    if ($validFrom && $validFrom->isBefore($expiresAt)) {
        $totalValidityDays = $validFrom->diffInDays($expiresAt);
        $percentRemaining = ($daysRemaining / $totalValidityDays) * 100;

        // Expires soon if:
        // - Less than 33% of validity period remaining
        // - OR less than 30 days (minimum threshold for any certificate)
        if ($percentRemaining < 33 || $daysRemaining < 30) {
            return 'expires_soon';
        }

        return 'valid';
    }

    // Fallback to legacy 30-day threshold if valid_from unavailable
    if ($daysRemaining <= 30) {
        return 'expires_soon';
    }

    return 'valid';
}
```

**Task 2.2**: Update `checkSsl()` method to use dynamic status

**Replace lines 263-270** with:

```php
// Determine status using dynamic thresholds
$status = $this->determineSslStatus(
    expiresAt: $this->monitor->certificate_expiration_date,
    validFrom: $certificateData['valid_from'],
    certificateStatus: $this->monitor->certificate_status
);
```

**Task 2.3**: Update `getLastSslResult()` method (lines 350-372)

**Replace lines 353-360** with:

```php
// Determine status using dynamic thresholds
// Note: We don't have valid_from from cache, so will use fallback logic
$status = $this->determineSslStatus(
    expiresAt: $this->monitor->certificate_expiration_date,
    validFrom: null,  // Not available from cached data
    certificateStatus: $this->monitor->certificate_status
);
```

### Phase 3: Database Schema Update

**Task 3.1**: Verify `monitoring_results.certificate_valid_from_date` column exists

**Check migration**: `database/migrations/*_create_monitoring_results_table.php`

The column should already exist from Phase 2 implementation. If not, create migration:

```php
Schema::table('monitoring_results', function (Blueprint $table) {
    $table->timestamp('certificate_valid_from_date')->nullable()->after('certificate_expiration_date');
});
```

**Task 3.2**: Update `RecordMonitoringResult` listener if needed

Verify line 52 in `app/Listeners/RecordMonitoringResult.php` handles the new field:

```php
'certificate_valid_from_date' => $results['certificate_valid_from_date'] ?? null,
```

### Phase 4: Testing

**File**: `tests/Feature/Jobs/CheckMonitorJobTest.php` (or create new test file)

**Task 4.1**: Create test class `tests/Feature/Jobs/DynamicSslThresholdsTest.php`

```php
<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

describe('Dynamic SSL Expiration Thresholds', function () {

    it('marks Let\'s Encrypt certificate with 73 days remaining as valid', function () {
        // 90-day Let's Encrypt cert with 73 days remaining = 81% of lifetime
        $monitor = Monitor::factory()->create([
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(73),
        ]);

        // Mock extractCertificateData to return 90-day validity period
        $validFrom = now()->subDays(17); // 90 - 73 = 17 days ago

        // Test the determineSslStatus method via reflection
        $job = new CheckMonitorJob($monitor, 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        expect($status)->toBe('valid');
    });

    it('marks 1-year certificate with 73 days remaining as expires_soon', function () {
        // 365-day commercial cert with 73 days remaining = 20% of lifetime
        $monitor = Monitor::factory()->create([
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(73),
        ]);

        $validFrom = now()->subDays(292); // 365 - 73 = 292 days ago

        $job = new CheckMonitorJob($monitor, 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        expect($status)->toBe('expires_soon');
    });

    it('marks 2-year certificate with 73 days remaining as expires_soon', function () {
        // 730-day commercial cert with 73 days remaining = 10% of lifetime
        $validFrom = now()->subDays(657); // 730 - 73 = 657 days ago

        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(73),
            $validFrom,
            'valid'
        );

        expect($status)->toBe('expires_soon');
    });

    it('uses 30-day fallback when valid_from is null', function () {
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        // 31 days remaining, no valid_from = should be valid
        $status = $method->invoke($job, now()->addDays(31), null, 'valid');
        expect($status)->toBe('valid');

        // 29 days remaining, no valid_from = should be expires_soon
        $status = $method->invoke($job, now()->addDays(29), null, 'valid');
        expect($status)->toBe('expires_soon');
    });

    it('applies minimum 30-day threshold even for long-lived certificates', function () {
        // Edge case: 10-year certificate with 25 days remaining
        // Percentage would be 0.68%, but should still trigger expires_soon due to 30-day minimum
        $validFrom = now()->subDays(3625); // 10 years ago

        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(25),
            $validFrom,
            'valid'
        );

        expect($status)->toBe('expires_soon');
    });

    it('marks expired certificates regardless of thresholds', function () {
        $validFrom = now()->subDays(90);

        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->subDays(1), // Expired yesterday
            $validFrom,
            'valid'
        );

        expect($status)->toBe('expired');
    });

    it('marks invalid certificates regardless of expiration', function () {
        $job = new CheckMonitorJob(Monitor::factory()->make(), 'ssl');
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineSslStatus');
        $method->setAccessible(true);

        $status = $method->invoke(
            $job,
            now()->addDays(100),
            now()->subDays(90),
            'invalid' // Certificate status is invalid
        );

        expect($status)->toBe('invalid');
    });
});
```

**Task 4.2**: Run tests and verify performance

```bash
./vendor/bin/sail artisan test --filter=DynamicSslThresholdsTest
time ./vendor/bin/sail artisan test --parallel
```

### Phase 5: Documentation

**Task 5.1**: Update inline documentation

Add comprehensive docblocks to all new/modified methods explaining:
- Dynamic threshold algorithm
- Percentage calculations
- Fallback behavior
- Example scenarios

**Task 5.2**: Create user-facing documentation

**File**: `docs/SSL_CERTIFICATE_MONITORING.md`

```markdown
# SSL Certificate Monitoring

## Dynamic Expiration Thresholds

SSL Monitor v4 uses intelligent, adaptive thresholds to determine when certificates are approaching expiration. Unlike traditional monitoring systems that use fixed day-based thresholds, our system calculates expiration status based on the percentage of the certificate's total validity period remaining.

### How It Works

**The system considers both:**
1. **Percentage-based threshold**: Certificate enters "expires_soon" status when less than 33% of its validity period remains
2. **Minimum day threshold**: Certificates always show "expires_soon" when less than 30 days remain (safety net)

The system uses whichever threshold is more conservative.

### Examples

| Certificate Type | Total Validity | 33% Threshold | 73 Days Remaining | Status |
|-----------------|----------------|---------------|-------------------|--------|
| Let's Encrypt | 90 days | 30 days | 81% of lifetime | ✅ **Valid** |
| 1-Year Commercial | 365 days | 121 days | 20% of lifetime | ⚠️ **Expires Soon** |
| 2-Year Commercial | 730 days | 241 days | 10% of lifetime | ⚠️ **Expires Soon** |

### Certificate Status Levels

- **✅ Valid**: Certificate is healthy, more than 33% of validity period remaining
- **⚠️ Expires Soon**: Certificate should be renewed (< 33% remaining OR < 30 days)
- **❌ Expired**: Certificate expiration date has passed
- **🚫 Invalid**: Certificate has validation errors

### Benefits

1. **Appropriate alerts for short-lived certificates**: Let's Encrypt certificates (90 days) won't trigger premature warnings
2. **Earlier warnings for long-lived certificates**: Commercial certificates get alerts with sufficient renewal time
3. **Consistent monitoring approach**: All certificates evaluated using same percentage-based logic
4. **Safety net**: 30-day minimum ensures no certificate is missed

### Technical Details

The system extracts the certificate's "Not Valid Before" (`valid_from`) date using OpenSSL during SSL checks. This date, combined with the expiration date, establishes the total validity period.

For backward compatibility, if the `valid_from` date is unavailable (older monitoring results), the system falls back to the traditional 30-day threshold.
```

**Task 5.3**: Update CLAUDE.md with new feature description

Add to the **Core Architecture** section:

```markdown
- **Dynamic SSL Thresholds**: Percentage-based expiration detection adapts to certificate validity period (Let's Encrypt vs commercial certificates)
```

## Agent Usage

Use the following specialized agents for this implementation:

### 1. laravel-backend-specialist
**Use for**:
- Implementing `determineSslStatus()` method
- Modifying `extractCertificateSubject()` → `extractCertificateData()`
- Updating `checkSsl()` method
- Modifying `RecordMonitoringResult` listener if needed

### 2. testing-specialist
**Use for**:
- Creating `DynamicSslThresholdsTest.php`
- Writing comprehensive test cases for all scenarios
- Ensuring performance standards (< 1 second per test)
- Using proper mocking with `MocksSslCertificateAnalysis` trait

### 3. documentation-writer
**Use for**:
- Creating `SSL_CERTIFICATE_MONITORING.md`
- Updating inline docblocks with examples
- Updating CLAUDE.md
- Creating user-friendly explanation of the feature

## Verification Checklist

- [ ] `extractCertificateData()` method extracts both subject and validity dates
- [ ] `determineSslStatus()` implements percentage-based logic with 33% threshold
- [ ] 30-day minimum threshold applies to all certificates
- [ ] Fallback to 30-day threshold when `valid_from` unavailable
- [ ] `checkSsl()` and `getLastSslResult()` use new dynamic method
- [ ] `certificate_valid_from_date` stored in monitoring results
- [ ] All 7 test scenarios pass
- [ ] Test suite completes in < 20 seconds (parallel)
- [ ] No new deprecation warnings
- [ ] Documentation complete and accurate
- [ ] Let's Encrypt certificate with 73 days shows as **valid**
- [ ] 1-year certificate with 73 days shows as **expires_soon**
- [ ] UI reflects new status correctly

## Success Criteria

1. **Functional**: Let's Encrypt certificate (90 days) with 73 days remaining shows **valid** status
2. **Functional**: Commercial certificate (365 days) with 73 days remaining shows **expires_soon** status
3. **Performance**: All tests pass in < 1 second each
4. **Backward Compatible**: Existing monitoring results without `valid_from` work with 30-day fallback
5. **User Experience**: Clear documentation explains the dynamic threshold behavior
6. **Code Quality**: Laravel conventions followed, comprehensive docblocks, no hardcoded values

## References

- **Current Implementation**: `app/Jobs/CheckMonitorJob.php:263-270, 353-360`
- **Monitor Model**: `app/Models/Monitor.php` (extended from Spatie)
- **Monitoring Results**: `app/Models/MonitoringResult.php`
- **Existing Tests**: `tests/Feature/Jobs/CheckMonitorJobTest.php`
- **Laravel Carbon Documentation**: https://carbon.nesbot.com/docs/
- **OpenSSL Certificate Parsing**: PHP `openssl_x509_parse()` function

## Notes

- The `validFrom_time_t` and `validTo_time_t` fields from `openssl_x509_parse()` provide Unix timestamps
- Use Carbon for all date calculations for consistency with Laravel conventions
- The 33% threshold was chosen as a balance between early warning and avoiding false alarms
- The 30-day minimum ensures even very long-lived certificates get timely renewal warnings
