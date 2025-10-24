#!/usr/bin/env php
<?php

/**
 * Test script to verify RecentChecksTimeline component fixes
 */

echo "🔍 Testing RecentChecksTimeline Component Fixes\n";
echo "===============================================\n\n";

// 1. Check if the component file exists and is readable
$componentPath = __DIR__ . '/resources/js/Components/Monitoring/RecentChecksTimeline.vue';
if (!file_exists($componentPath)) {
    echo "❌ Component file not found: $componentPath\n";
    exit(1);
}

echo "✅ Component file exists\n";

// 2. Check if the component has been properly modified
$componentContent = file_get_contents($componentPath);

// Check for our fixes
$fixes = [
    'Array.isArray(checks.value)' => 'Fixed array checking',
    '!Array.isArray(data?.data)' => 'Fixed API response validation',
    'getStatusConfig(status: string | undefined | null)' => 'Fixed TypeScript type safety',
    'formatTimeAgo(timestamp: string | undefined | null)' => 'Fixed timestamp formatting',
    'getCheckTypeIcon(checkType: string | undefined | null)' => 'Fixed check type handling',
    'checks.value = []' => 'Initialized checks as empty array',
    'key={`check-${check.id || check.uuid || index}`}' => 'Fixed key binding stability'
];

foreach ($fixes as $pattern => $description) {
    if (strpos($componentContent, $pattern) !== false) {
        echo "✅ $description\n";
    } else {
        echo "❌ Missing fix: $description\n";
    }
}

// 3. Check if there are any obvious syntax errors
echo "\n🔍 Checking for syntax errors...\n";

// Basic syntax checks
$syntaxChecks = [
    '/const safeChecks = computed\(\(\) => \{/' => 'Safe checks computed property',
    '/if \(!Array\.isArray\(checks\.value\)\)/' => 'Array.isArray validation',
    '/const newData = Array\.isArray\(data\?\.data\) \? data\.data : \[\];/' => 'API response array validation',
    '/return \[\];' => 'Returns empty array fallback'
];

foreach ($syntaxChecks as $pattern => $description) {
    if (preg_match($pattern, $componentContent)) {
        echo "✅ $description\n";
    } else {
        echo "❌ Missing pattern: $description\n";
    }
}

// 4. Check if the component imports are correct
echo "\n🔍 Checking component imports...\n";

$importChecks = [
    'import { ref, computed, onMounted, watch, nextTick }' => 'Vue composition API imports',
    'import { CheckCircle, XCircle, AlertTriangle' => 'Lucide icon imports'
];

foreach ($importChecks as $pattern => $description) {
    if (strpos($componentContent, $pattern) !== false) {
        echo "✅ $description\n";
    } else {
        echo "❌ Missing import: $description\n";
    }
}

// 5. Check if the build succeeds
echo "\n🔍 Testing frontend build...\n";
$buildOutput = [];
$buildResult = 0;
exec('npm run build 2>&1', $buildOutput, $buildResult);

if ($buildResult === 0) {
    echo "✅ Frontend build successful\n";
} else {
    echo "❌ Frontend build failed\n";
    echo "Build output:\n";
    echo implode("\n", array_slice($buildOutput, -10)) . "\n";
}

// 6. Check if tests pass
echo "\n🔍 Running tests...\n";
$testOutput = [];
$testResult = 0;
exec('./vendor/bin/sail artisan test --filter="Show" --stop-on-failure 2>&1', $testOutput, $testResult);

if ($testResult === 0) {
    echo "✅ Tests passing\n";
} else {
    echo "❌ Tests failing\n";
    echo "Test output:\n";
    echo implode("\n", array_slice($testOutput, -5)) . "\n";
}

echo "\n🎉 RecentChecksTimeline component verification complete!\n";
echo "If all checks pass, the component should now work correctly.\n";