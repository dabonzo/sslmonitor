<?php

use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringEvent;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('MonitoringResult can be created with basic data', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
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
    $monitor = Monitor::first();
    $website = Website::first();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->uuid)->not->toBeNull();
    expect(strlen($result->uuid))->toBe(36);
});

test('MonitoringResult belongs to monitor', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->monitor)->toBeInstanceOf(Monitor::class);
    expect($result->monitor->id)->toBe($monitor->id);
});

test('MonitoringResult belongs to website', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $result = MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect($result->website)->toBeInstanceOf(Website::class);
    expect($result->website->id)->toBe($website->id);
});

test('MonitoringResult successful scope works', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'failed',
    ]);

    expect(MonitoringResult::successful()->count())->toBe(1);
});

test('MonitoringResult manual scope works', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'manual_immediate',
        'started_at' => now(),
        'status' => 'success',
    ]);

    MonitoringResult::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'check_type' => 'both',
        'trigger_type' => 'scheduled',
        'started_at' => now(),
        'status' => 'success',
    ]);

    expect(MonitoringResult::manual()->count())->toBe(1);
});

test('MonitoringCheckSummary can be created with period data', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $summary = MonitoringCheckSummary::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'summary_period' => 'hourly',
        'period_start' => now()->startOfHour(),
        'period_end' => now()->endOfHour(),
        'total_uptime_checks' => 10,
        'successful_uptime_checks' => 9,
        'uptime_percentage' => 90.00,
    ]);

    expect($summary)->toBeInstanceOf(MonitoringCheckSummary::class);
    expect($summary->uptime_percentage)->toBe('90.00');
});

test('MonitoringAlert can be acknowledged', function () {
    $user = User::first();
    $monitor = Monitor::first();
    $website = Website::first();

    $alert = MonitoringAlert::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Certificate Expiring Soon',
        'first_detected_at' => now(),
    ]);

    $alert->acknowledge($user, 'Working on renewal');

    expect($alert->acknowledged_at)->not->toBeNull();
    expect($alert->acknowledged_by_user_id)->toBe($user->id);
    expect($alert->acknowledgment_note)->toBe('Working on renewal');
});

test('MonitoringAlert can be resolved', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $alert = MonitoringAlert::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'alert_type' => 'uptime_down',
        'alert_severity' => 'critical',
        'alert_title' => 'Website Down',
        'first_detected_at' => now(),
    ]);

    $alert->resolve();

    expect($alert->resolved_at)->not->toBeNull();
});

test('MonitoringAlert unresolved scope works', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    MonitoringAlert::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Expiring',
        'first_detected_at' => now(),
    ]);

    MonitoringAlert::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'alert_type' => 'uptime_down',
        'alert_severity' => 'critical',
        'alert_title' => 'Website Down',
        'first_detected_at' => now(),
        'resolved_at' => now(),
    ]);

    expect(MonitoringAlert::unresolved()->count())->toBe(1);
});

test('MonitoringEvent does not have updated_at column', function () {
    $monitor = Monitor::first();

    $event = MonitoringEvent::create([
        'monitor_id' => $monitor->id,
        'event_type' => 'monitor_created',
        'event_name' => 'Monitor Created',
        'source' => 'system',
    ]);

    expect(isset($event->updated_at))->toBeFalse();
    expect(MonitoringEvent::UPDATED_AT)->toBeNull();
});

test('MonitoringEvent user actions scope works', function () {
    $user = User::first();
    $monitor = Monitor::first();

    MonitoringEvent::create([
        'monitor_id' => $monitor->id,
        'user_id' => $user->id,
        'event_type' => 'monitor_updated',
        'event_name' => 'Monitor Updated',
        'source' => 'user',
    ]);

    MonitoringEvent::create([
        'monitor_id' => $monitor->id,
        'event_type' => 'monitor_created',
        'event_name' => 'Monitor Created',
        'source' => 'system',
    ]);

    expect(MonitoringEvent::userActions()->count())->toBe(1);
});
