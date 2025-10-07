<?php

use App\Models\Monitor;

test('monitor can check if content validation is configured', function () {
    $monitor = Monitor::factory()->create([
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
    ]);

    expect($monitor->hasContentValidation())->toBeFalse();

    $monitor->update(['content_expected_strings' => ['Welcome']]);
    expect($monitor->fresh()->hasContentValidation())->toBeTrue();

    $monitor->update([
        'content_expected_strings' => null,
        'content_forbidden_strings' => ['Error 500']
    ]);
    expect($monitor->fresh()->hasContentValidation())->toBeTrue();

    $monitor->update([
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => ['/success/i']
    ]);
    expect($monitor->fresh()->hasContentValidation())->toBeTrue();
});

test('monitor can check if javascript is enabled', function () {
    $monitor = Monitor::factory()->create(['javascript_enabled' => false]);
    expect($monitor->hasJavaScriptEnabled())->toBeFalse();

    $monitor->update(['javascript_enabled' => true]);
    expect($monitor->fresh()->hasJavaScriptEnabled())->toBeTrue();
});

test('monitor returns correct javascript wait seconds with bounds', function () {
    $monitor = Monitor::factory()->create(['javascript_wait_seconds' => 5]);
    expect($monitor->getJavaScriptWaitSeconds())->toBe(5);

    // Test minimum bound
    $monitor->update(['javascript_wait_seconds' => 0]);
    expect($monitor->fresh()->getJavaScriptWaitSeconds())->toBe(1);

    // Test maximum bound
    $monitor->update(['javascript_wait_seconds' => 50]);
    expect($monitor->fresh()->getJavaScriptWaitSeconds())->toBe(30);

    // Test null value (default)
    $monitor->update(['javascript_wait_seconds' => null]);
    expect($monitor->fresh()->getJavaScriptWaitSeconds())->toBe(5);
});

test('monitor can add expected content strings', function () {
    $monitor = Monitor::factory()->create(['content_expected_strings' => null]);

    $monitor->addExpectedString('Welcome');
    expect($monitor->content_expected_strings)->toBe(['Welcome']);

    $monitor->addExpectedString('Home Page');
    expect($monitor->content_expected_strings)->toBe(['Welcome', 'Home Page']);

    // Test duplicate removal
    $monitor->addExpectedString('Welcome');
    expect($monitor->content_expected_strings)->toBe(['Welcome', 'Home Page']);

    // Test empty string filtering
    $monitor->addExpectedString('  ');
    expect($monitor->content_expected_strings)->toBe(['Welcome', 'Home Page']);
});

test('monitor can add forbidden content strings', function () {
    $monitor = Monitor::factory()->create(['content_forbidden_strings' => null]);

    $monitor->addForbiddenString('Error 500');
    expect($monitor->content_forbidden_strings)->toBe(['Error 500']);

    $monitor->addForbiddenString('Page Not Found');
    expect($monitor->content_forbidden_strings)->toBe(['Error 500', 'Page Not Found']);

    // Test duplicate removal
    $monitor->addForbiddenString('Error 500');
    expect($monitor->content_forbidden_strings)->toBe(['Error 500', 'Page Not Found']);
});

test('monitor can add regex patterns', function () {
    $monitor = Monitor::factory()->create(['content_regex_patterns' => null]);

    $monitor->addRegexPattern('/success/i');
    expect($monitor->content_regex_patterns)->toBe(['/success/i']);

    $monitor->addRegexPattern('/status.*ok/');
    expect($monitor->content_regex_patterns)->toBe(['/success/i', '/status.*ok/']);

    // Test duplicate removal
    $monitor->addRegexPattern('/success/i');
    expect($monitor->content_regex_patterns)->toBe(['/success/i', '/status.*ok/']);
});

test('monitor can remove expected content strings', function () {
    $monitor = Monitor::factory()->create([
        'content_expected_strings' => ['Welcome', 'Home Page', 'Contact']
    ]);

    $monitor->removeExpectedString('Home Page');
    expect($monitor->content_expected_strings)->toBe(['Welcome', 'Contact']);

    $monitor->removeExpectedString('Welcome');
    expect($monitor->content_expected_strings)->toBe(['Contact']);

    // Test removing non-existent string
    $monitor->removeExpectedString('Non-existent');
    expect($monitor->content_expected_strings)->toBe(['Contact']);
});

test('monitor can remove forbidden content strings', function () {
    $monitor = Monitor::factory()->create([
        'content_forbidden_strings' => ['Error 500', 'Error 404', 'Maintenance']
    ]);

    $monitor->removeForbiddenString('Error 404');
    expect($monitor->content_forbidden_strings)->toBe(['Error 500', 'Maintenance']);

    $monitor->removeForbiddenString('Error 500');
    expect($monitor->content_forbidden_strings)->toBe(['Maintenance']);
});

test('monitor can remove regex patterns', function () {
    $monitor = Monitor::factory()->create([
        'content_regex_patterns' => ['/success/i', '/status.*ok/', '/welcome/']
    ]);

    $monitor->removeRegexPattern('/status.*ok/');
    expect($monitor->content_regex_patterns)->toBe(['/success/i', '/welcome/']);

    $monitor->removeRegexPattern('/success/i');
    expect($monitor->content_regex_patterns)->toBe(['/welcome/']);
});

test('monitor casts content validation arrays correctly', function () {
    $monitor = Monitor::factory()->create([
        'content_expected_strings' => ['Welcome', 'Home'],
        'content_forbidden_strings' => ['Error', 'Maintenance'],
        'content_regex_patterns' => ['/test/', '/demo/'],
        'javascript_enabled' => true,
        'javascript_wait_seconds' => 10,
    ]);

    expect($monitor->content_expected_strings)->toBeArray()
        ->and($monitor->content_expected_strings)->toBe(['Welcome', 'Home'])
        ->and($monitor->content_forbidden_strings)->toBeArray()
        ->and($monitor->content_forbidden_strings)->toBe(['Error', 'Maintenance'])
        ->and($monitor->content_regex_patterns)->toBeArray()
        ->and($monitor->content_regex_patterns)->toBe(['/test/', '/demo/'])
        ->and($monitor->javascript_enabled)->toBeTrue()
        ->and($monitor->javascript_wait_seconds)->toBe(10);
});

test('monitor stores content validation failure reason', function () {
    $monitor = Monitor::factory()->create();

    // Test that content validation failure reason is stored
    $monitor->uptimeCheckFailed('Expected string `Welcome` was not found in response.');
    expect($monitor->content_validation_failure_reason)
        ->toBe('Expected string `Welcome` was not found in response.');

    // Test that non-content validation failure doesn't override
    $monitor->uptimeCheckFailed('Connection timeout');
    expect($monitor->content_validation_failure_reason)
        ->toBe('Expected string `Welcome` was not found in response.');

    // Test forbidden string failure
    $monitor->uptimeCheckFailed('Forbidden string `Error 500` was found in response.');
    expect($monitor->content_validation_failure_reason)
        ->toBe('Forbidden string `Error 500` was found in response.');

    // Test regex pattern failure
    $monitor->uptimeCheckFailed('Regex pattern `/success/i` did not match response content.');
    expect($monitor->content_validation_failure_reason)
        ->toBe('Regex pattern `/success/i` did not match response content.');
});

test('monitor clears content validation failure reason on success', function () {
    $monitor = Monitor::factory()->create([
        'content_validation_failure_reason' => 'Some previous error'
    ]);

    // Mock a response interface
    $response = new class {
        public function getBody() {
            return 'success content';
        }
    };

    $monitor->uptimeRequestSucceeded($response);
    expect($monitor->content_validation_failure_reason)->toBeNull();
});