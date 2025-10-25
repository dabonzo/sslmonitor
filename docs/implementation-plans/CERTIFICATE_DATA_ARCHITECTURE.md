# Certificate Data Architecture - Implementation Plan

## Overview

**Problem**: Certificate Analysis extracts comprehensive SSL data (subject, SANs, issuer, serial, algorithm, key size, etc.) but this data is NOT saved to the database. Instead, we rely on monitor checks to extract a subset of this data, which is inefficient and creates gaps in production.

**Solution**: Make Certificate Analysis the "source of truth" for SSL certificate data. Save all certificate details when analysis runs, and use monitor checks only to detect changes (certificate renewal, expiration).

**Status**: 🔴 Not Started
**Priority**: **HIGH** (Fixes production issue)
**Estimated Time**: 4-6 hours
**Dependencies**: None

---

## Current Architecture Problems

### Issue 1: Certificate Data Not Persisted
```
User creates website → Certificate Analysis runs → Displays rich SSL data → Data LOST ❌
                                                                           ↓
                                                          Not saved to database
```

### Issue 2: Redundant Data Extraction
```
Certificate Analysis extracts:          Monitor Check tries to extract:
- Subject (CN + SANs) ✅                - Subject (via OpenSSL) ❌
- Issuer ✅                             - Issuer (from Spatie) ✅
- Serial Number ✅                      - Not extracted ❌
- Algorithm ✅                          - Not extracted ❌
- Key Size ✅                           - Not extracted ❌
- Validity Period ✅                    - Expiration only ✅
```

### Issue 3: Production Data Gap
- Website index needs certificate subject
- Monitor checks haven't populated data yet (17,591 records, 0 with certificate_subject)
- Certificate Analysis HAS the data but doesn't save it
- Result: Empty certificate subject in production UI

---

## Proposed Architecture

### New Flow
```
User creates website → Certificate Analysis runs → Save ALL SSL data to database ✅
                                ↓                           ↓
                        Display rich data              Store in latest_ssl_certificate
                                                                   ↓
Monitor checks run → Compare with saved data → Only update if changed ✅
        ↓
    No change? → Skip update
    Changed?   → New certificate detected → Update saved data
```

### Data Storage Strategy

**Option A: Add `latest_ssl_certificate` JSON column to `websites` table** (RECOMMENDED)
- Single column stores complete certificate analysis JSON
- Fast queries (no joins needed)
- Easy to update
- Supports all certificate fields without schema changes

**Option B: Create `ssl_certificates` table**
- Normalized structure
- Historical certificate tracking
- More complex queries
- Better for analytics

**Recommendation**: Start with Option A (JSON column), migrate to Option B later if needed.

---

## Implementation Plan

### Phase 1: Database Schema

**Task 1.1**: Add `latest_ssl_certificate` column to `websites` table

**Migration**: `database/migrations/YYYY_MM_DD_add_latest_ssl_certificate_to_websites.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->json('latest_ssl_certificate')->nullable()->after('uptime_monitoring_enabled');
            $table->timestamp('ssl_certificate_analyzed_at')->nullable()->after('latest_ssl_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn(['latest_ssl_certificate', 'ssl_certificate_analyzed_at']);
        });
    }
};
```

**Task 1.2**: Update `Website` model

**File**: `app/Models/Website.php`

```php
// Add to $casts array
protected $casts = [
    // ... existing casts ...
    'latest_ssl_certificate' => 'array',
    'ssl_certificate_analyzed_at' => 'datetime',
];

// Add accessor for certificate data
public function getCertificateAttribute(): ?array
{
    return $this->latest_ssl_certificate;
}

// Add helper method to check if certificate data is stale
public function isCertificateDataStale(): bool
{
    if (!$this->ssl_certificate_analyzed_at) {
        return true;
    }

    // Consider stale if older than 24 hours
    return $this->ssl_certificate_analyzed_at->lt(now()->subDay());
}
```

---

### Phase 2: Save Certificate Analysis Data

**Task 2.1**: Update `SslCertificateAnalysisService` to save data

**File**: `app/Services/SslCertificateAnalysisService.php`

**Add new method** after existing analysis method:

```php
/**
 * Analyze certificate and save complete data to website record.
 *
 * @param Website $website
 * @return array Certificate analysis result
 */
public function analyzeAndSave(Website $website): array
{
    try {
        // Run existing analysis
        $analysis = $this->analyze($website->url);

        // Extract the complete certificate data structure
        $certificateData = [
            // Basic Info
            'subject' => $analysis['analysis']['basic_info']['subject'] ?? null,
            'issuer' => $analysis['analysis']['basic_info']['issuer'] ?? null,
            'serial_number' => $analysis['analysis']['basic_info']['serial_number'] ?? null,
            'signature_algorithm' => $analysis['analysis']['basic_info']['signature_algorithm'] ?? null,

            // Validity
            'valid_from' => $analysis['analysis']['validity']['valid_from'] ?? null,
            'valid_until' => $analysis['analysis']['validity']['valid_until'] ?? null,
            'days_remaining' => $analysis['analysis']['validity']['days_remaining'] ?? null,
            'is_expired' => $analysis['analysis']['validity']['is_expired'] ?? false,
            'expires_soon' => $analysis['analysis']['validity']['expires_soon'] ?? false,

            // Security
            'key_algorithm' => $analysis['analysis']['security']['key_algorithm'] ?? null,
            'key_size' => $analysis['analysis']['security']['key_size'] ?? null,
            'security_score' => $analysis['analysis']['security']['security_score'] ?? null,
            'risk_level' => $analysis['analysis']['security']['risk_level'] ?? null,

            // Domains
            'primary_domain' => $analysis['analysis']['domains']['primary_domain'] ?? null,
            'subject_alt_names' => $analysis['analysis']['domains']['subject_alt_names'] ?? [],
            'covers_www' => $analysis['analysis']['domains']['covers_www'] ?? false,
            'is_wildcard' => $analysis['analysis']['domains']['is_wildcard'] ?? false,

            // Chain
            'chain_length' => $analysis['analysis']['chain']['chain_length'] ?? 0,
            'chain_complete' => $analysis['analysis']['chain']['chain_complete'] ?? false,
            'intermediate_issuers' => $analysis['analysis']['chain']['intermediate_issuers'] ?? [],

            // Metadata
            'status' => $analysis['status'] ?? 'unknown',
            'analyzed_at' => now()->toIso8601String(),
        ];

        // Save to website record
        $website->update([
            'latest_ssl_certificate' => $certificateData,
            'ssl_certificate_analyzed_at' => now(),
        ]);

        AutomationLogger::info("Saved SSL certificate data for website: {$website->url}", [
            'website_id' => $website->id,
            'subject' => $certificateData['subject'],
            'days_remaining' => $certificateData['days_remaining'],
        ]);

        return $analysis;

    } catch (\Throwable $exception) {
        AutomationLogger::error(
            "Failed to analyze and save SSL certificate for website: {$website->url}",
            ['website_id' => $website->id],
            $exception
        );

        throw $exception;
    }
}
```

**Task 2.2**: Update `WebsiteController` to use `analyzeAndSave()`

**File**: `app/Http/Controllers/WebsiteController.php`

Find the `analyzeCertificate` method and update it:

```php
public function analyzeCertificate(Website $website)
{
    $this->authorize('view', $website);

    try {
        // Use analyzeAndSave instead of analyze
        $analysis = $this->sslService->analyzeAndSave($website);

        return response()->json($analysis);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to analyze certificate: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

### Phase 3: Update Website Index to Use Saved Data

**Task 3.1**: Modify `WebsiteController::index()` to use saved certificate data

**File**: `app/Http/Controllers/WebsiteController.php`

**Replace the certificate subject extraction logic** (lines 100-114 added in previous fix):

```php
// REMOVE THIS (old approach - fetching from monitoring_results):
$monitorIds = $monitors->pluck('id')->filter()->toArray();
$latestCertificateSubjects = \DB::table('monitoring_results')
    ->select('monitor_id', 'certificate_subject')
    ->whereIn('monitor_id', $monitorIds)
    ->whereNotNull('certificate_subject')
    ->whereIn('id', function ($query) use ($monitorIds) {
        $query->selectRaw('MAX(id)')
            ->from('monitoring_results')
            ->whereIn('monitor_id', $monitorIds)
            ->whereNotNull('certificate_subject')
            ->groupBy('monitor_id');
    })
    ->get()
    ->keyBy('monitor_id');
```

**Replace with** (new approach - use saved certificate data):

```php
// NEW APPROACH: Certificate data is already loaded with websites via eager loading
// We'll use $website->latest_ssl_certificate directly in the transform loop
```

**Task 3.2**: Update the transform loop to use saved certificate data

**Find this code** (around line 117):

```php
$websites->through(function ($website) use ($monitors, $latestCertificateSubjects) {
```

**Replace with**:

```php
$websites->through(function ($website) use ($monitors) {
```

**Find the SSL data construction** (around lines 146-161):

```php
// Get certificate subject from latest monitoring result
$certificateSubject = null;
if ($monitor->id && isset($latestCertificateSubjects[$monitor->id])) {
    $certificateSubject = $latestCertificateSubjects[$monitor->id]->certificate_subject;
}

$sslData = [
    'status' => $monitor->certificate_status,
    'expires_at' => $monitor->certificate_expiration_date,
    'days_remaining' => $daysRemaining ? (int) $daysRemaining : null,
    'urgency_level' => $urgencyLevel,
    'issuer' => $monitor->certificate_issuer,
    'subject' => $certificateSubject,
    'is_valid' => $monitor->certificate_status === 'valid',
    'last_checked' => $monitor->updated_at,
];
```

**Replace with** (use saved certificate data):

```php
// Use saved certificate analysis data as primary source
$certificate = $website->latest_ssl_certificate;

$sslData = [
    'status' => $monitor->certificate_status,
    'expires_at' => $monitor->certificate_expiration_date,
    'days_remaining' => $daysRemaining ? (int) $daysRemaining : null,
    'urgency_level' => $urgencyLevel,

    // Use saved certificate data (richer than monitor data)
    'issuer' => $certificate['issuer'] ?? $monitor->certificate_issuer,
    'subject' => $certificate['subject'] ?? null,
    'serial_number' => $certificate['serial_number'] ?? null,
    'algorithm' => $certificate['signature_algorithm'] ?? null,
    'key_size' => $certificate['key_size'] ?? null,
    'subject_alt_names' => $certificate['subject_alt_names'] ?? [],

    'is_valid' => $monitor->certificate_status === 'valid',
    'last_checked' => $monitor->updated_at,
    'last_analyzed' => $website->ssl_certificate_analyzed_at,
];
```

---

### Phase 4: Trigger Analysis on Website Creation

**Task 4.1**: Update `WebsiteObserver` to run certificate analysis

**File**: `app/Observers/WebsiteObserver.php`

**Add to the `created` method**:

```php
public function created(Website $website): void
{
    // ... existing monitor creation code ...

    // Automatically analyze SSL certificate if SSL monitoring is enabled
    if ($website->ssl_monitoring_enabled) {
        dispatch(new \App\Jobs\AnalyzeSslCertificateJob($website))
            ->onQueue('monitoring-analysis')
            ->delay(now()->addSeconds(5)); // Small delay to ensure monitor is created
    }
}
```

**Task 4.2**: Create `AnalyzeSslCertificateJob`

**File**: `app/Jobs/AnalyzeSslCertificateJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use App\Services\AutomationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Website $website
    ) {}

    public function handle(SslCertificateAnalysisService $service): void
    {
        try {
            AutomationLogger::info("Starting SSL certificate analysis for: {$this->website->url}", [
                'website_id' => $this->website->id,
            ]);

            $service->analyzeAndSave($this->website);

            AutomationLogger::info("Completed SSL certificate analysis for: {$this->website->url}", [
                'website_id' => $this->website->id,
            ]);

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "Failed to analyze SSL certificate for: {$this->website->url}",
                ['website_id' => $this->website->id],
                $exception
            );

            throw $exception;
        }
    }

    public function retryUntil(): \Carbon\Carbon
    {
        return now()->addMinutes(5);
    }

    public function failed(\Throwable $exception): void
    {
        AutomationLogger::jobFailed(self::class, $exception, [
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
        ]);
    }
}
```

---

### Phase 5: Update Monitor Checks (Lightweight Updates)

**Task 5.1**: Modify `CheckMonitorJob` to detect certificate changes

**File**: `app/Jobs/CheckMonitorJob.php`

**Add new method**:

```php
/**
 * Check if SSL certificate has changed (new certificate issued).
 *
 * @return bool True if certificate changed
 */
private function hasCertificateChanged(): bool
{
    $website = \App\Models\Website::where('url', (string) $this->monitor->url)->first();

    if (!$website || !$website->latest_ssl_certificate) {
        return true; // No saved data, consider changed
    }

    $savedCertificate = $website->latest_ssl_certificate;
    $currentSerialNumber = $this->monitor->certificate_serial_number ?? null;
    $savedSerialNumber = $savedCertificate['serial_number'] ?? null;

    // If serial numbers differ, certificate was renewed
    if ($currentSerialNumber && $savedSerialNumber && $currentSerialNumber !== $savedSerialNumber) {
        AutomationLogger::info("Certificate renewed detected for: {$this->monitor->url}", [
            'old_serial' => $savedSerialNumber,
            'new_serial' => $currentSerialNumber,
        ]);

        return true;
    }

    // If expiration date changed significantly (more than 1 day difference)
    $savedExpiration = isset($savedCertificate['valid_until'])
        ? \Carbon\Carbon::parse($savedCertificate['valid_until'])
        : null;

    if ($this->monitor->certificate_expiration_date && $savedExpiration) {
        $daysDiff = abs($this->monitor->certificate_expiration_date->diffInDays($savedExpiration));

        if ($daysDiff > 1) {
            return true;
        }
    }

    return false;
}
```

**Task 5.2**: Trigger re-analysis when certificate changes

**In `checkSsl()` method**, add after certificate check:

```php
// After checking certificate and before returning result
if ($this->hasCertificateChanged()) {
    // Certificate changed - trigger re-analysis to update saved data
    $website = \App\Models\Website::where('url', (string) $this->monitor->url)->first();

    if ($website) {
        dispatch(new AnalyzeSslCertificateJob($website))
            ->onQueue('monitoring-analysis');
    }
}
```

---

### Phase 6: Frontend Updates

**Task 6.1**: Update `Index.vue` to use new certificate data structure

**File**: `resources/js/pages/Ssl/Websites/Index.vue`

**The SSL data structure now includes**:

```typescript
ssl_data: {
    status: string;
    expires_at: string;
    days_remaining: number | null;
    urgency_level: string;
    issuer: string;
    subject: string;              // ✅ Now populated from saved data
    serial_number: string;        // ✅ NEW
    algorithm: string;            // ✅ NEW
    key_size: number;             // ✅ NEW
    subject_alt_names: string[];  // ✅ NEW
    is_valid: boolean;
    last_checked: string;
    last_analyzed: string;        // ✅ NEW - when certificate was analyzed
}
```

**Task 6.2**: Update quick view modal to show additional certificate info

**Find the SSL Certificate section** (around line 1360) and enhance it:

```vue
<div v-if="selectedWebsite.ssl_data" class="space-y-2">
  <div class="flex justify-between items-start">
    <span class="text-sm text-muted-foreground">Issuer:</span>
    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl_data.issuer }}</span>
  </div>

  <div class="flex justify-between items-start">
    <span class="text-sm text-muted-foreground">Subject:</span>
    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl_data.subject || 'N/A' }}</span>
  </div>

  <!-- NEW: Show additional certificate info -->
  <div v-if="selectedWebsite.ssl_data.algorithm" class="flex justify-between items-start">
    <span class="text-sm text-muted-foreground">Algorithm:</span>
    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl_data.algorithm }}</span>
  </div>

  <div v-if="selectedWebsite.ssl_data.key_size" class="flex justify-between items-start">
    <span class="text-sm text-muted-foreground">Key Size:</span>
    <span class="text-sm text-foreground text-right">{{ selectedWebsite.ssl_data.key_size }} bits</span>
  </div>

  <div class="flex justify-between items-start">
    <span class="text-sm text-muted-foreground">Expires:</span>
    <span class="text-sm text-foreground text-right">{{ formatDate(selectedWebsite.ssl_data.expires_at) }}</span>
  </div>

  <!-- NEW: Show when certificate was last analyzed -->
  <div v-if="selectedWebsite.ssl_data.last_analyzed" class="flex justify-between items-start pt-2 border-t border-border">
    <span class="text-xs text-muted-foreground">Last Analyzed:</span>
    <span class="text-xs text-muted-foreground text-right">{{ formatDate(selectedWebsite.ssl_data.last_analyzed) }}</span>
  </div>
</div>
```

---

### Phase 7: Backfill Existing Websites

**Task 7.1**: Create Artisan command to backfill certificate data

**File**: `app/Console/Commands/BackfillCertificateData.php`

```php
<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeSslCertificateJob;
use App\Models\Website;
use Illuminate\Console\Command;

class BackfillCertificateData extends Command
{
    protected $signature = 'ssl:backfill-certificates
                            {--limit=10 : Number of websites to process}
                            {--force : Process all websites regardless of existing data}';

    protected $description = 'Backfill SSL certificate data for existing websites';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = Website::where('ssl_monitoring_enabled', true);

        if (!$force) {
            $query->whereNull('ssl_certificate_analyzed_at');
        }

        $websites = $query->limit($limit)->get();

        if ($websites->isEmpty()) {
            $this->info('No websites need certificate analysis.');
            return 0;
        }

        $this->info("Processing {$websites->count()} websites...");

        $bar = $this->output->createProgressBar($websites->count());

        foreach ($websites as $website) {
            $this->info("\nAnalyzing: {$website->url}");

            dispatch(new AnalyzeSslCertificateJob($website))
                ->onQueue('monitoring-analysis');

            $bar->advance();

            // Small delay to avoid overwhelming the queue
            usleep(500000); // 0.5 seconds
        }

        $bar->finish();

        $this->info("\n\nQueued {$websites->count()} certificate analysis jobs.");
        $this->info('Check Horizon dashboard to monitor progress.');

        return 0;
    }
}
```

**Task 7.2**: Run backfill command after deployment

```bash
# Process 10 websites at a time
php artisan ssl:backfill-certificates --limit=10

# Or process all websites with SSL monitoring
php artisan ssl:backfill-certificates --limit=100 --force
```

---

## Testing Strategy

### Unit Tests

**File**: `tests/Unit/Models/WebsiteTest.php`

```php
test('website stores and retrieves certificate data', function () {
    $website = Website::factory()->create([
        'latest_ssl_certificate' => [
            'subject' => 'example.com, www.example.com',
            'issuer' => 'Let\'s Encrypt',
            'serial_number' => '0x123456789',
            'key_size' => 2048,
        ],
        'ssl_certificate_analyzed_at' => now(),
    ]);

    expect($website->latest_ssl_certificate)->toBeArray();
    expect($website->latest_ssl_certificate['subject'])->toBe('example.com, www.example.com');
    expect($website->certificate)->toBe($website->latest_ssl_certificate);
});

test('website detects stale certificate data', function () {
    $website = Website::factory()->create([
        'ssl_certificate_analyzed_at' => now()->subDays(2),
    ]);

    expect($website->isCertificateDataStale())->toBeTrue();

    $website->ssl_certificate_analyzed_at = now();
    expect($website->isCertificateDataStale())->toBeFalse();
});
```

### Feature Tests

**File**: `tests/Feature/Services/SslCertificateAnalysisServiceTest.php`

```php
test('analyzeAndSave stores certificate data to website', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $service = app(SslCertificateAnalysisService::class);
    $result = $service->analyzeAndSave($website);

    $website->refresh();

    expect($website->latest_ssl_certificate)->not->toBeNull();
    expect($website->latest_ssl_certificate)->toHaveKeys([
        'subject', 'issuer', 'serial_number', 'key_size',
        'valid_from', 'valid_until', 'days_remaining'
    ]);
    expect($website->ssl_certificate_analyzed_at)->not->toBeNull();
});
```

**File**: `tests/Feature/Jobs/AnalyzeSslCertificateJobTest.php`

```php
test('job analyzes and saves certificate data', function () {
    $website = Website::factory()->create(['url' => 'https://example.com']);

    $job = new AnalyzeSslCertificateJob($website);
    $job->handle(app(SslCertificateAnalysisService::class));

    $website->refresh();

    expect($website->latest_ssl_certificate)->not->toBeNull();
    expect($website->ssl_certificate_analyzed_at)->not->toBeNull();
});
```

---

## Agent Usage

### 1. laravel-backend-specialist
**Use for**:
- Creating migration for `latest_ssl_certificate` column
- Updating `Website` model with casts and accessors
- Modifying `SslCertificateAnalysisService` to add `analyzeAndSave()` method
- Creating `AnalyzeSslCertificateJob`
- Updating `WebsiteObserver` to trigger analysis on creation
- Modifying `CheckMonitorJob` to detect certificate changes
- Creating backfill Artisan command

### 2. testing-specialist
**Use for**:
- Writing unit tests for Website model certificate methods
- Creating feature tests for `analyzeAndSave()` method
- Testing `AnalyzeSslCertificateJob`
- Testing certificate change detection in `CheckMonitorJob`
- Ensuring all tests pass in < 1 second
- Using proper mocking traits

### 3. vue-component-builder
**Use for**:
- Updating `Index.vue` TypeScript interfaces for new SSL data structure
- Enhancing quick view modal to display additional certificate info
- Ensuring semantic color tokens are used correctly
- Testing component rendering with new data structure

### 4. documentation-writer
**Use for**:
- Updating CLAUDE.md with new architecture
- Creating user-facing documentation for certificate data
- Documenting the backfill command
- Adding inline code documentation for new methods

---

## Verification Checklist

After implementation, verify:

- [ ] Migration runs successfully: `php artisan migrate`
- [ ] `latest_ssl_certificate` column exists in `websites` table
- [ ] `Website` model casts certificate data as array
- [ ] Certificate Analysis saves data: Run analysis and check database
- [ ] New websites automatically get certificate analysis
- [ ] Website index shows certificate subject (not empty)
- [ ] Quick view modal displays subject, algorithm, key size
- [ ] Monitor checks detect certificate changes (test with renewed cert)
- [ ] Backfill command works: `php artisan ssl:backfill-certificates --limit=5`
- [ ] All tests passing: `./vendor/bin/sail artisan test --parallel`
- [ ] Test suite < 20 seconds execution time
- [ ] No N+1 queries in website index (check with Debugbar)
- [ ] Production deployment successful
- [ ] Production certificate subjects populated

---

## Success Criteria

1. **Functional**:
   - Certificate subject displays in production website index ✅
   - All certificate data (subject, issuer, serial, algorithm, key size) available ✅
   - New websites get immediate certificate analysis ✅
   - Monitor checks only update when certificate changes ✅

2. **Performance**:
   - Website index query count unchanged or improved (no N+1 queries)
   - Certificate analysis happens asynchronously (doesn't block website creation)
   - Test suite < 20 seconds

3. **Data Quality**:
   - All existing websites have certificate data after backfill
   - Certificate data includes all fields from Certificate Analysis
   - Data stays in sync with actual certificates

4. **User Experience**:
   - Certificate subject visible immediately in UI
   - Rich certificate details in quick view modal
   - Clear indication when certificate was last analyzed
   - Certificate Analysis modal still works as before

---

## Migration Path for Production

1. **Deploy code** with new migration
2. **Run migration**: `php artisan migrate`
3. **Run backfill command** in batches:
   ```bash
   # Process 10 websites at a time, monitor Horizon
   php artisan ssl:backfill-certificates --limit=10

   # Wait for jobs to complete, then repeat
   php artisan ssl:backfill-certificates --limit=10
   ```
4. **Verify** certificate subjects appear in UI
5. **Monitor** Horizon for any failed jobs
6. **Clear caches**: `php artisan cache:clear && php artisan config:cache`

---

## Rollback Plan

If issues occur:

1. **Rollback migration**:
   ```bash
   php artisan migrate:rollback
   ```

2. **Revert to previous code** using Deployer:
   ```bash
   dep rollback production
   ```

3. **Clear caches**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

---

## Future Enhancements (Out of Scope)

- **Historical certificate tracking**: Store all certificates in `ssl_certificates` table
- **Certificate renewal alerts**: Notify when certificate changes detected
- **Certificate comparison**: Show diff between old and new certificates
- **Automated re-analysis**: Schedule regular certificate re-analysis (weekly)
- **Certificate export**: Export certificate data to CSV/JSON

---

## References

- **Current Implementation**: `app/Services/SslCertificateAnalysisService.php`
- **Website Model**: `app/Models/Website.php`
- **Monitor Check Job**: `app/Jobs/CheckMonitorJob.php`
- **Website Observer**: `app/Observers/WebsiteObserver.php`
- **Frontend Index**: `resources/js/pages/Ssl/Websites/Index.vue`
- **Laravel JSON Columns**: https://laravel.com/docs/12.x/eloquent-mutators#array-and-json-casting

---

## Notes

- This architecture makes Certificate Analysis the single source of truth for SSL data
- Monitor checks become lightweight (only detect changes, not full analysis)
- Immediate SSL data on website creation improves UX
- Reduces redundant SSL certificate parsing
- Eliminates dependency on monitor check completion for certificate display
- Fixes production issue where certificate_subject is empty
