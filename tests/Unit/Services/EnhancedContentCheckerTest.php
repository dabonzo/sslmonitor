<?php

use App\Models\Monitor;
use App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->checker = new EnhancedContentChecker;
});

test('enhanced content checker validates basic look_for_string functionality', function () {
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    expect($reason)->toContain('Expected word `Home Page` was not found');
});

test('enhanced content checker validates forbidden strings', function () {
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    expect($reason)->toContain('Expected word `Login` was not found');

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
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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
    $monitor = Monitor::factory()->make([
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

    // Forbidden strings are case-sensitive: 'error' != 'Error'
    $response = new Response(200, [], 'Welcome HOME PAGE SUCCESS error occurred');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse(); // 'error' found (forbidden)
});

test('enhanced content checker properly filters and validates arrays', function () {
    // Test that empty strings are handled properly in arrays
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['Welcome', '', 'Home'],
        'content_forbidden_strings' => ['Error', '', 'Maintenance'],
        'content_regex_patterns' => ['/test/', '', '/demo/'],
        'javascript_enabled' => false,
    ]);

    $response = new Response(200, [], 'Welcome Home test demo content');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});

// ============================================================================
// Word Boundary Matching Tests (for Expected Strings)
// ============================================================================

test('expected strings use word boundary matching to prevent partial matches', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['Erdgasversorger'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Should FAIL: "Erdgasversorger1" contains "Erdgasversorger" as substring but not as whole word
    $response = new Response(200, [], 'Der beste Erdgasversorger1 ist hier.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toContain('Expected word `Erdgasversorger` was not found')
        ->and($reason)->toContain('word boundary matching');
});

test('expected strings match whole words correctly', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['Erdgasversorger1'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Should PASS: Exact word match
    $response = new Response(200, [], 'Der beste Erdgasversorger1 ist hier.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('expected strings match words at different positions', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['test'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Word at beginning
    $response = new Response(200, [], 'test at start');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Word in middle
    $response = new Response(200, [], 'word test middle');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Word at end
    $response = new Response(200, [], 'at end test');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Standalone word
    $response = new Response(200, [], 'test');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // NOT a whole word (part of "testing")
    $response = new Response(200, [], 'testing phase');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
});

test('expected strings respect word boundaries with punctuation', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['hello'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Should match with various punctuation
    $response = new Response(200, [], 'hello, world!');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    $response = new Response(200, [], 'Say: "hello" to everyone.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    $response = new Response(200, [], 'hello! How are you?');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Should NOT match when part of another word
    $response = new Response(200, [], 'othello is a play');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
});

test('expected strings with special characters are properly escaped', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['test_variable'], // Underscore is a word character, tests escaping
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Should match when word with underscore is present
    $response = new Response(200, [], 'Set test_variable to true');
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();

    // Should not match partial matches
    $response = new Response(200, [], 'Set test_variable_name to true');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    // Should not match when missing
    $response = new Response(200, [], 'Set testvariable to true');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();
});

// ============================================================================
// Combination Tests (Word Boundary Matching + Forbidden + Regex)
// ============================================================================

test('all validation types work together with word boundaries', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['beste', 'Erdgasversorger1', 'hier'],
        'content_forbidden_strings' => ['error', 'fehler', '404'],
        'content_regex_patterns' => ['/Erdgasversorger\d+/', '/Der beste.*ist hier/', '/<!DOCTYPE html>/'],
        'javascript_enabled' => false,
    ]);

    // ALL conditions met - should PASS
    $htmlContent = '<!DOCTYPE html><html><body><p>Der beste <strong>Erdgasversorger1</strong> ist hier.</p></body></html>';
    $response = new Response(200, [], $htmlContent);
    expect($this->checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('combination fails when expected word boundary not matched', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['Erdgasversorger'], // Without the "1"
        'content_forbidden_strings' => ['error'],
        'content_regex_patterns' => ['/Erdgasversorger\d+/'],
        'javascript_enabled' => false,
    ]);

    // Expected word "Erdgasversorger" not found (only "Erdgasversorger1" exists)
    $response = new Response(200, [], 'Der beste Erdgasversorger1 ist hier.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toContain('Expected word `Erdgasversorger` was not found');
});

test('combination fails when forbidden substring is found', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['beste', 'hier'],
        'content_forbidden_strings' => ['Erdgas'], // Substring of "Erdgasversorger1"
        'content_regex_patterns' => ['/Der beste/'],
        'javascript_enabled' => false,
    ]);

    // Forbidden substring "Erdgas" found in "Erdgasversorger1"
    $response = new Response(200, [], 'Der beste Erdgasversorger1 ist hier.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Forbidden string `Erdgas` was found in response.');
});

test('combination fails when regex pattern does not match', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['beste', 'hier'],
        'content_forbidden_strings' => ['error'],
        'content_regex_patterns' => ['/\d{5,}/'], // Needs 5+ digits, only has 1
        'javascript_enabled' => false,
    ]);

    // Regex doesn't match
    $response = new Response(200, [], 'Der beste Erdgasversorger1 ist hier.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Regex pattern `/\d{5,}/` did not match response content.');
});

test('validates expected with word boundaries while forbidden uses substring matching', function () {
    $monitor = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['test'],
        'content_forbidden_strings' => ['test'],
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Word "test" found (expected passes with word boundary)
    // BUT "test" substring also found (forbidden fails with substring)
    // Result: Should FAIL because forbidden string found
    $response = new Response(200, [], 'This is a test.');
    expect($this->checker->isValidResponse($response, $monitor))->toBeFalse();

    $reason = $this->checker->getFailureReason($response, $monitor);
    expect($reason)->toBe('Forbidden string `test` was found in response.');
});

test('expected word boundaries vs forbidden substring matching behavior', function () {
    $monitor1 = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => ['test'],
        'content_forbidden_strings' => null,
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    $monitor2 = Monitor::factory()->make([
        'look_for_string' => null,
        'content_expected_strings' => null,
        'content_forbidden_strings' => ['test'],
        'content_regex_patterns' => null,
        'javascript_enabled' => false,
    ]);

    // Expected: word boundary - "testing" does NOT contain word "test"
    $response = new Response(200, [], 'testing phase');
    expect($this->checker->isValidResponse($response, $monitor1))->toBeFalse();

    // Forbidden: substring - "testing" DOES contain substring "test"
    $response = new Response(200, [], 'testing phase');
    expect($this->checker->isValidResponse($response, $monitor2))->toBeFalse();
});
