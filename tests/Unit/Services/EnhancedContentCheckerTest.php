<?php

use App\Models\Monitor;
use App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->checker = new EnhancedContentChecker();
});

test('enhanced content checker validates basic look_for_string functionality', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => 'Welcome',
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    $response = new Response(200, [], 'Welcome to our website');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    $response = new Response(200, [], 'Home page content');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
});

test('enhanced content checker returns correct failure reason for look_for_string', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => 'Welcome',
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    $response = new Response(200, [], 'Home page content');
    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('String `Welcome` was not found on the response.');
});

test('enhanced content checker validates expected strings', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => ['Welcome', 'Home Page'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // All expected strings present - should pass
    $response = new Response(200, [], 'Welcome to our Home Page');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Missing one expected string - should fail
    $response = new Response(200, [], 'Welcome to our website');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Expected string `Home Page` was not found in response.');
});

test('enhanced content checker validates forbidden strings', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => null,
        'content_forbidden_strings' => ['Error 500', 'Page Not Found'],
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // No forbidden strings present - should pass
    $response = new Response(200, [], 'Welcome to our website');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Forbidden string present - should fail
    $response = new Response(200, [], 'Error 500: Server error occurred');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Forbidden string `Error 500` was found in response.');
});

test('enhanced content checker validates regex patterns', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => ['/status.*ok/i', '/welcome/'],
        'javascript_enabled' => false,
    ]);

    // All regex patterns match - should pass
    $response = new Response(200, [], 'Status is OK and welcome to our site');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // One regex pattern doesn't match - should fail
    $response = new Response(200, [], 'Status is OK but missing the other word');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Regex pattern `/welcome/` did not match response content.');
});

test('enhanced content checker handles combined validation rules', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => 'Success',
        'content_expected_strings' => ['Welcome', 'Login'],
        'content_forbidden_strings' => ['Error', 'Maintenance'],
        'content_regex_patterns' => ['/status.*ok/i'],
        'javascript_enabled' => false,
    ]);

    // All validations pass
    $response = new Response(200, [], 'Success: Welcome! Please Login. Status is OK.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Basic look_for_string fails
    $response = new Response(200, [], 'Welcome! Please Login. Status is OK.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('String `Success` was not found on the response.');

    // Expected string missing
    $response = new Response(200, [], 'Success: Welcome! Status is OK.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Expected string `Login` was not found in response.');

    // Forbidden string present
    $response = new Response(200, [], 'Success: Welcome! Please Login. Error occurred. Status is OK.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Forbidden string `Error` was found in response.');

    // Regex pattern doesn't match
    $response = new Response(200, [], 'Success: Welcome! Please Login. Status is failed.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Regex pattern `/status.*ok/i` did not match response content.');
});

test('enhanced content checker handles empty validation arrays', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => [],
        'content_forbidden_strings' => [],
        'content_regex_patterns' => [],
        'javascript_enabled' => false,
    ]);

    // Empty arrays should allow any content to pass
    $response = new Response(200, [], 'Any content should pass');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    $response = new Response(200, [], 'Error 500 should also pass since no forbidden strings');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('enhanced content checker handles null validation arrays', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Null arrays should allow any content to pass
    $response = new Response(200, [], 'Any content should pass');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('enhanced content checker handles invalid regex patterns gracefully', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => null,
        'content_forbidden_strings' => null,
        'content_regex_patterns' => ['/valid_pattern/', '/[invalid_pattern'],
        'javascript_enabled' => false,
    ]);

    $response = new Response(200, [], 'valid_pattern content here');

    // Should handle invalid regex gracefully and still check valid ones
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
});

test('enhanced content checker handles case sensitivity properly', function () {
    $monitor = Monitor::factory()->create([
        'look_for_string' => 'Welcome',
        'content_expected_strings' => ['HOME PAGE'],
        'content_forbidden_strings' => ['error'],
        'content_regex_patterns' => ['/success/i'], // Case insensitive
        'javascript_enabled' => false,
    ]);

    // Case sensitive string matching
    $response = new Response(200, [], 'welcome home page SUCCESS no Error');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse(); // 'Welcome' != 'welcome'

    $response = new Response(200, [], 'Welcome HOME PAGE SUCCESS no Error');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue(); // All match correctly

    $response = new Response(200, [], 'Welcome HOME PAGE SUCCESS Error occurred');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse(); // 'Error' contains 'error'
});

test('enhanced content checker properly filters and validates arrays', function () {
    // Test that empty strings are handled properly in arrays
    $monitor = Monitor::factory()->create([
        'look_for_string' => null,
        'content_expected_strings' => ['Welcome', '', 'Home'],
        'content_forbidden_strings' => ['Error', '', 'Maintenance'],
        'content_regex_patterns' => ['/test/', '', '/demo/'],
        'javascript_enabled' => false,
    ]);

    $response = new Response(200, [], 'Welcome Home test demo content');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});