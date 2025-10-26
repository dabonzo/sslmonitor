#!/bin/bash

# Bulk optimization script for remaining test files
# This script applies the proven optimization pattern to all test files that create Website models

echo "Starting bulk test optimization..."

# Array of files to optimize
files=(
    "tests/Feature/SslMonitoringTest.php"
    "tests/Feature/Automation/AutomationWorkflowTest.php"
    "tests/Feature/Controllers/SslDashboardControllerTest.php"
    "tests/Feature/Controllers/WebsiteControllerImmediateCheckTest.php"
    "tests/Feature/Controllers/WebsiteControllerTest.php"
    "tests/Feature/Jobs/JobFailureAndRetryTest.php"
    "tests/Feature/Jobs/AnalyzeSslCertificateJobTest.php"
    "tests/Feature/Monitoring/ResponseTimeTrackingTest.php"
    "tests/Feature/Observers/WebsiteObserverTest.php"
    "tests/Feature/Console/Commands/BackfillCertificateDataTest.php"
    "tests/Feature/Services/SslCertificateAnalysisServiceTest.php"
    "tests/Feature/WebsiteControllerTest.php"
    "tests/Feature/API/MonitorHistoryApiTest.php"
    "tests/Feature/WebsiteManagementTest.php"
    "tests/Feature/TeamTransferWorkflowTest.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "Processing: $file"

        # Count Website factory calls before
        before_count=$(grep -c "Website::factory()->create" "$file" 2>/dev/null || echo "0")
        echo "  - Found $before_count Website::factory()->create calls"

        if [ "$before_count" -gt "0" ]; then
            # Backup file
            cp "$file" "${file}.backup"

            # Add MocksSslCertificateAnalysis import if not present
            if ! grep -q "use Tests\\\\Traits\\\\MocksSslCertificateAnalysis" "$file"; then
                echo "  - Adding MocksSslCertificateAnalysis import"
                sed -i '/^use Tests\\Traits\\UsesCleanDatabase;/a use Tests\\Traits\\MocksSslCertificateAnalysis;' "$file"
            fi

            # Add trait to uses() if not present
            if grep -q "uses(UsesCleanDatabase::class);" "$file"; then
                echo "  - Adding MocksSslCertificateAnalysis to uses()"
                sed -i 's/uses(UsesCleanDatabase::class);/uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);/' "$file"
            elif grep -q "uses(RefreshDatabase::class);" "$file"; then
                echo "  - Adding MocksSslCertificateAnalysis to uses() with RefreshDatabase"
                sed -i 's/uses(RefreshDatabase::class);/uses(RefreshDatabase::class, MocksSslCertificateAnalysis::class);/' "$file"
            fi

            echo "  - File prepared for optimization (manual step required for MonitorIntegrationService mock and withoutEvents wrapping)"
        else
            echo "  - Skipping (no Website factory calls found)"
        fi
    else
        echo "File not found: $file"
    fi
done

echo ""
echo "Bulk optimization preparation complete!"
echo "Next steps:"
echo "1. Review modified files"
echo "2. Add MonitorIntegrationService mock to beforeEach in each file"
echo "3. Wrap Website::factory()->create() calls with Website::withoutEvents(fn() => ...)"
echo "4. Run tests to verify"
