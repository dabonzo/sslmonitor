<?php

use Illuminate\Support\Facades\Schema;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

test('monitoring_results table exists with all columns', function () {
    expect(Schema::hasTable('monitoring_results'))->toBeTrue();

    expect(Schema::hasColumns('monitoring_results', [
        'id', 'uuid', 'monitor_id', 'website_id', 'check_type', 'trigger_type',
        'triggered_by_user_id', 'started_at', 'completed_at', 'duration_ms',
        'status', 'error_message', 'uptime_status', 'http_status_code',
        'response_time_ms', 'ssl_status', 'certificate_issuer',
        'content_validation_enabled', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('monitoring_check_summaries table exists with all columns', function () {
    expect(Schema::hasTable('monitoring_check_summaries'))->toBeTrue();

    expect(Schema::hasColumns('monitoring_check_summaries', [
        'id', 'monitor_id', 'website_id', 'summary_period',
        'period_start', 'period_end', 'total_uptime_checks',
        'uptime_percentage', 'average_response_time_ms',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('monitoring_alerts table exists with all columns', function () {
    expect(Schema::hasTable('monitoring_alerts'))->toBeTrue();

    expect(Schema::hasColumns('monitoring_alerts', [
        'id', 'monitor_id', 'website_id', 'alert_type', 'alert_severity',
        'alert_title', 'first_detected_at', 'acknowledged_at',
        'resolved_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('monitoring_events table exists with correct columns', function () {
    expect(Schema::hasTable('monitoring_events'))->toBeTrue();

    expect(Schema::hasColumns('monitoring_events', [
        'id', 'monitor_id', 'website_id', 'user_id', 'event_type',
        'event_name', 'source', 'created_at',
    ]))->toBeTrue();

    // Verify updated_at does NOT exist
    expect(Schema::hasColumn('monitoring_events', 'updated_at'))->toBeFalse();
});

test('monitoring_results has foreign key constraints', function () {
    $foreignKeys = Schema::getForeignKeys('monitoring_results');

    $monitorFkExists = collect($foreignKeys)->contains(
        fn ($fk) => $fk['columns'] === ['monitor_id'] && $fk['foreign_table'] === 'monitors'
    );

    $websiteFkExists = collect($foreignKeys)->contains(
        fn ($fk) => $fk['columns'] === ['website_id'] && $fk['foreign_table'] === 'websites'
    );

    expect($monitorFkExists)->toBeTrue();
    expect($websiteFkExists)->toBeTrue();
});

test('monitoring_results has critical composite index', function () {
    $indexes = Schema::getIndexes('monitoring_results');

    $compositeIndexExists = collect($indexes)->contains(
        fn ($idx) => $idx['name'] === 'idx_monitor_website_time'
    );

    expect($compositeIndexExists)->toBeTrue();
});

test('monitoring_check_summaries has unique constraint on summary period', function () {
    $indexes = Schema::getIndexes('monitoring_check_summaries');

    $uniqueConstraintExists = collect($indexes)->contains(
        fn ($idx) => $idx['name'] === 'unique_summary'
    );

    expect($uniqueConstraintExists)->toBeTrue();
});
