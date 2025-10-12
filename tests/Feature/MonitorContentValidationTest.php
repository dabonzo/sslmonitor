<?php

use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Monitor Content Validation Methods', function () {
    beforeEach(function () {
        $this->monitor = Monitor::factory()->create();
    });

    describe('Content Validation Detection', function () {
        test('detects content validation when expected strings are set', function () {
            $this->monitor->update(['content_expected_strings' => ['Welcome']]);
            expect($this->monitor->hasContentValidation())->toBeTrue();
        });

        test('detects content validation when forbidden strings are set', function () {
            $this->monitor->update(['content_forbidden_strings' => ['Error']]);
            expect($this->monitor->hasContentValidation())->toBeTrue();
        });

        test('detects content validation when regex patterns are set', function () {
            $this->monitor->update(['content_regex_patterns' => ['/\d+/']]);
            expect($this->monitor->hasContentValidation())->toBeTrue();
        });

        test('returns false when no content validation is configured', function () {
            $this->monitor->update([
                'content_expected_strings' => null,
                'content_forbidden_strings' => null,
                'content_regex_patterns' => null
            ]);
            expect($this->monitor->hasContentValidation())->toBeFalse();
        });

        test('returns false when all arrays are empty', function () {
            $this->monitor->update([
                'content_expected_strings' => [],
                'content_forbidden_strings' => [],
                'content_regex_patterns' => []
            ]);
            expect($this->monitor->hasContentValidation())->toBeFalse();
        });
    });

    describe('JavaScript Configuration', function () {
        test('detects javascript when enabled', function () {
            $this->monitor->update(['javascript_enabled' => true]);
            expect($this->monitor->hasJavaScriptEnabled())->toBeTrue();
        });

        test('returns false when javascript is disabled', function () {
            $this->monitor->update(['javascript_enabled' => false]);
            expect($this->monitor->hasJavaScriptEnabled())->toBeFalse();
        });

        test('returns correct wait seconds', function () {
            $this->monitor->update(['javascript_wait_seconds' => 10]);
            expect($this->monitor->getJavaScriptWaitSeconds())->toBe(10);
        });

        test('enforces minimum wait seconds', function () {
            $this->monitor->update(['javascript_wait_seconds' => 0]);
            expect($this->monitor->getJavaScriptWaitSeconds())->toBe(1);

            $this->monitor->update(['javascript_wait_seconds' => -5]);
            expect($this->monitor->getJavaScriptWaitSeconds())->toBe(1);
        });

        test('enforces maximum wait seconds', function () {
            $this->monitor->update(['javascript_wait_seconds' => 100]);
            expect($this->monitor->getJavaScriptWaitSeconds())->toBe(30);
        });
    });

    describe('Expected Strings Management', function () {
        test('adds expected string correctly', function () {
            $this->monitor->addExpectedString('Welcome');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome']);
        });

        test('adds multiple expected strings', function () {
            $this->monitor->addExpectedString('Welcome');
            $this->monitor->addExpectedString('Dashboard');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome', 'Dashboard']);
        });

        test('trims whitespace from expected strings', function () {
            $this->monitor->addExpectedString('  Welcome  ');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome']);
        });

        test('prevents duplicate expected strings', function () {
            $this->monitor->addExpectedString('Welcome');
            $this->monitor->addExpectedString('Welcome');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome']);
        });

        test('filters out empty expected strings', function () {
            $this->monitor->addExpectedString('Welcome');
            $this->monitor->addExpectedString('');
            $this->monitor->addExpectedString('  ');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome']);
        });

        test('removes expected string correctly', function () {
            $this->monitor->update(['content_expected_strings' => ['Welcome', 'Dashboard']]);
            $this->monitor->removeExpectedString('Welcome');
            expect($this->monitor->content_expected_strings)->toBe(['Dashboard']);
        });

        test('handles removing non-existent expected string', function () {
            $this->monitor->update(['content_expected_strings' => ['Welcome']]);
            $this->monitor->removeExpectedString('NotThere');
            expect($this->monitor->content_expected_strings)->toBe(['Welcome']);
        });
    });

    describe('Forbidden Strings Management', function () {
        test('adds forbidden string correctly', function () {
            $this->monitor->addForbiddenString('Error');
            expect($this->monitor->content_forbidden_strings)->toBe(['Error']);
        });

        test('adds multiple forbidden strings', function () {
            $this->monitor->addForbiddenString('Error');
            $this->monitor->addForbiddenString('404');
            expect($this->monitor->content_forbidden_strings)->toBe(['Error', '404']);
        });

        test('trims whitespace from forbidden strings', function () {
            $this->monitor->addForbiddenString('  Error  ');
            expect($this->monitor->content_forbidden_strings)->toBe(['Error']);
        });

        test('prevents duplicate forbidden strings', function () {
            $this->monitor->addForbiddenString('Error');
            $this->monitor->addForbiddenString('Error');
            expect($this->monitor->content_forbidden_strings)->toBe(['Error']);
        });

        test('removes forbidden string correctly', function () {
            $this->monitor->update(['content_forbidden_strings' => ['Error', '404']]);
            $this->monitor->removeForbiddenString('Error');
            expect($this->monitor->content_forbidden_strings)->toBe(['404']);
        });
    });

    describe('Regex Patterns Management', function () {
        test('adds regex pattern correctly', function () {
            $this->monitor->addRegexPattern('/\d+/');
            expect($this->monitor->content_regex_patterns)->toBe(['/\d+/']);
        });

        test('adds multiple regex patterns', function () {
            $this->monitor->addRegexPattern('/\d+/');
            $this->monitor->addRegexPattern('/[A-Z]+/');
            expect($this->monitor->content_regex_patterns)->toBe(['/\d+/', '/[A-Z]+/']);
        });

        test('trims whitespace from regex patterns', function () {
            $this->monitor->addRegexPattern('  /\d+/  ');
            expect($this->monitor->content_regex_patterns)->toBe(['/\d+/']);
        });

        test('prevents duplicate regex patterns', function () {
            $this->monitor->addRegexPattern('/\d+/');
            $this->monitor->addRegexPattern('/\d+/');
            expect($this->monitor->content_regex_patterns)->toBe(['/\d+/']);
        });

        test('removes regex pattern correctly', function () {
            $this->monitor->update(['content_regex_patterns' => ['/\d+/', '/[A-Z]+/']]);
            $this->monitor->removeRegexPattern('/\d+/');
            expect($this->monitor->content_regex_patterns)->toBe(['/[A-Z]+/']);
        });
    });

    describe('Model Casting', function () {
        test('casts content arrays correctly', function () {
            $this->monitor->update([
                'content_expected_strings' => ['Welcome', 'Dashboard'],
                'content_forbidden_strings' => ['Error', '404'],
                'content_regex_patterns' => ['/\d+/', '/[A-Z]+/']
            ]);

            expect($this->monitor->content_expected_strings)->toBeArray();
            expect($this->monitor->content_forbidden_strings)->toBeArray();
            expect($this->monitor->content_regex_patterns)->toBeArray();
        });

        test('casts javascript settings correctly', function () {
            $this->monitor->update([
                'javascript_enabled' => true,
                'javascript_wait_seconds' => 10
            ]);

            expect($this->monitor->javascript_enabled)->toBeBool();
            expect($this->monitor->javascript_wait_seconds)->toBeInt();
        });
    });

    describe('Response Time and Failure Tracking', function () {
        test('clears response time on failure', function () {
            $this->monitor->update(['uptime_check_response_time_in_ms' => 200]);
            $this->monitor->uptimeCheckFailed('Connection failed');
            expect($this->monitor->uptime_check_response_time_in_ms)->toBeNull();
        });

        test('stores content validation failure reason', function () {
            $this->monitor->uptimeCheckFailed('Expected string `Welcome` was not found in response.');
            expect($this->monitor->content_validation_failure_reason)
                ->toBe('Expected string `Welcome` was not found in response.');
        });

        test('stores forbidden string failure reason', function () {
            $this->monitor->uptimeCheckFailed('Forbidden string `Error` was found in response.');
            expect($this->monitor->content_validation_failure_reason)
                ->toBe('Forbidden string `Error` was found in response.');
        });

        test('stores regex pattern failure reason', function () {
            $this->monitor->uptimeCheckFailed('Regex pattern `/\d+/` did not match response content.');
            expect($this->monitor->content_validation_failure_reason)
                ->toBe('Regex pattern `/\d+/` did not match response content.');
        });

        test('does not store non-content validation failures', function () {
            $this->monitor->uptimeCheckFailed('Connection timeout');
            expect($this->monitor->content_validation_failure_reason)->toBeNull();
        });

        test('clears content validation failure reason on success', function () {
            $this->monitor->update(['content_validation_failure_reason' => 'Some error']);
            $response = new \GuzzleHttp\Psr7\Response(200, [], 'Success');
            $this->monitor->uptimeRequestSucceeded($response);
            expect($this->monitor->content_validation_failure_reason)->toBeNull();
        });
    });
});