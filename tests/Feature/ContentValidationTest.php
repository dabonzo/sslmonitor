<?php

use App\Models\Monitor;
use App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Content Validation System', function () {
    beforeEach(function () {
        $this->checker = new EnhancedContentChecker;
        $this->monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'javascript_enabled' => false,
        ]);
    });

    describe('Basic Look For String (Backward Compatibility)', function () {
        test('validates when look_for_string is found', function () {
            $this->monitor->update(['look_for_string' => 'success']);
            $response = new Response(200, [], 'This is a success page');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('fails when look_for_string is not found', function () {
            $this->monitor->update(['look_for_string' => 'success']);
            $response = new Response(200, [], 'This is an error page');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toBe('String `success` was not found on the response.');
        });

        test('passes when look_for_string is empty', function () {
            $this->monitor->update(['look_for_string' => '']);
            $response = new Response(200, [], 'Any content');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });
    });

    describe('Expected Strings Validation', function () {
        test('validates when all expected strings are present', function () {
            $this->monitor->update([
                'content_expected_strings' => ['Welcome', 'Dashboard', 'User'],
            ]);
            $response = new Response(200, [], 'Welcome to the Dashboard, User');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('fails when expected string is missing', function () {
            $this->monitor->update([
                'content_expected_strings' => ['Welcome', 'Dashboard', 'Missing'],
            ]);
            $response = new Response(200, [], 'Welcome to the Dashboard');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toBe('Expected word `Missing` was not found in response (uses word boundary matching).');
        });

        test('passes when expected strings array is empty', function () {
            $this->monitor->update(['content_expected_strings' => []]);
            $response = new Response(200, [], 'Any content');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });
    });

    describe('Forbidden Strings Validation', function () {
        test('validates when no forbidden strings are present', function () {
            $this->monitor->update([
                'content_forbidden_strings' => ['Error', '404', 'Not Found'],
            ]);
            $response = new Response(200, [], 'Welcome to our site');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('fails when forbidden string is found', function () {
            $this->monitor->update([
                'content_forbidden_strings' => ['Error', '404', 'Not Found'],
            ]);
            $response = new Response(200, [], 'Error 500: Internal server error');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toBe('Forbidden string `Error` was found in response.');
        });

        test('passes when forbidden strings array is empty', function () {
            $this->monitor->update(['content_forbidden_strings' => []]);
            $response = new Response(200, [], 'Error content');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });
    });

    describe('Regex Pattern Validation', function () {
        test('validates when all regex patterns match', function () {
            $this->monitor->update([
                'content_regex_patterns' => ['/\d{4}/', '/[A-Z][a-z]+/'],
            ]);
            $response = new Response(200, [], 'Year 2024 Welcome');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('fails when regex pattern does not match', function () {
            $this->monitor->update([
                'content_regex_patterns' => ['/\d{4}/', '/[A-Z][a-z]+/'],
            ]);
            $response = new Response(200, [], 'no numbers or capitals');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toContain('Regex pattern `/\d{4}/` did not match response content.');
        });

        test('passes when regex patterns array is empty', function () {
            $this->monitor->update(['content_regex_patterns' => []]);
            $response = new Response(200, [], 'Any content');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });
    });

    describe('Combined Validation', function () {
        test('validates when all conditions are met', function () {
            $this->monitor->update([
                'look_for_string' => 'base',
                'content_expected_strings' => ['Welcome', 'User'],
                'content_forbidden_strings' => ['Error', '404'],
                'content_regex_patterns' => ['/\d{4}/'],
            ]);
            $response = new Response(200, [], 'Welcome User to our base system in 2024');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('fails when basic look_for_string fails', function () {
            $this->monitor->update([
                'look_for_string' => 'missing',
                'content_expected_strings' => ['Welcome'],
                'content_forbidden_strings' => ['Error'],
            ]);
            $response = new Response(200, [], 'Welcome to our site');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toBe('String `missing` was not found on the response.');
        });

        test('fails when enhanced validation fails even if basic passes', function () {
            $this->monitor->update([
                'look_for_string' => 'Welcome',
                'content_forbidden_strings' => ['Error'],
            ]);
            $response = new Response(200, [], 'Welcome! Error 500 occurred');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
            expect($this->checker->getFailureReason($response, $this->monitor))
                ->toBe('Forbidden string `Error` was found in response.');
        });
    });

    describe('Edge Cases', function () {
        test('handles null content validation fields', function () {
            $this->monitor->update([
                'content_expected_strings' => null,
                'content_forbidden_strings' => null,
                'content_regex_patterns' => null,
            ]);
            $response = new Response(200, [], 'Any content');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeTrue();
        });

        test('handles empty string response', function () {
            $this->monitor->update([
                'content_expected_strings' => ['text'],
            ]);
            $response = new Response(200, [], '');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
        });

        test('handles case sensitive string matching', function () {
            $this->monitor->update([
                'content_expected_strings' => ['Welcome'],
            ]);
            $response = new Response(200, [], 'welcome to our site');

            expect($this->checker->isValidResponse($response, $this->monitor))->toBeFalse();
        });

        test('returns appropriate failure reason when no specific reason found', function () {
            $response = new Response(200, [], 'Content without issues');

            // Mock a scenario where validation fails but no specific reason is identified
            $reflector = new ReflectionClass($this->checker);
            $method = $reflector->getMethod('getEnhancedValidationFailureReason');
            $method->setAccessible(true);

            $reason = $method->invoke($this->checker, 'Content without issues', $this->monitor);
            expect($reason)->toBe('');

            // Test the main getFailureReason method when basic look_for_string fails
            $this->monitor->update(['look_for_string' => 'missing']);
            $result = $this->checker->getFailureReason($response, $this->monitor);
            expect($result)->toBe('String `missing` was not found on the response.');

            // Test fallback when validation fails but basic passes
            $this->monitor->update([
                'look_for_string' => 'Content',
                'content_expected_strings' => ['NotPresent'],
            ]);
            $result = $this->checker->getFailureReason($response, $this->monitor);
            expect($result)->toBe('Expected word `NotPresent` was not found in response (uses word boundary matching).');
        });
    });
});

describe('BrowserShot JavaScript Content Fetching Integration', function () {
    test('skips browsershot fetching when javascript disabled', function () {
        $monitor = Monitor::factory()->create([
            'javascript_enabled' => false,
            'content_expected_strings' => ['Welcome'],
        ]);
        $checker = new EnhancedContentChecker;
        $response = new Response(200, [], 'Welcome to our site');

        expect($checker->isValidResponse($response, $monitor))->toBeTrue();
    });

    test('monitor tracks browsershot configuration correctly', function () {
        $monitor = Monitor::factory()->create([
            'javascript_enabled' => true,
            'javascript_wait_seconds' => 10,
        ]);

        expect($monitor->hasJavaScriptEnabled())->toBeTrue();
        expect($monitor->getJavaScriptWaitSeconds())->toBe(10);
    });

    test('browsershot wait seconds are bounded correctly', function () {
        $monitor = Monitor::factory()->create([
            'javascript_wait_seconds' => 0,
        ]);
        expect($monitor->getJavaScriptWaitSeconds())->toBe(1);

        $monitor->update(['javascript_wait_seconds' => 100]);
        expect($monitor->getJavaScriptWaitSeconds())->toBe(30);

        // Test default value when creating new monitor without specifying wait time
        $newMonitor = Monitor::factory()->create();
        expect($newMonitor->getJavaScriptWaitSeconds())->toBe(5);
    });
});
