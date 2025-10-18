<?php

namespace App\Services\UptimeMonitor\ResponseCheckers;

use App\Services\UptimeMonitor\JavaScriptContentFetcher;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Helpers\UptimeResponseCheckers\UptimeResponseChecker;
use Spatie\UptimeMonitor\Models\Monitor;

class EnhancedContentChecker implements UptimeResponseChecker
{
    public function isValidResponse(ResponseInterface $response, Monitor $monitor): bool
    {
        $responseBody = (string) $response->getBody();

        // If JavaScript is enabled, fetch content using headless browser
        if ($this->isJavaScriptEnabled($monitor)) {
            try {
                $jsFetcher = new JavaScriptContentFetcher;
                $waitSeconds = $this->getJavaScriptWaitSeconds($monitor);
                $jsContent = $jsFetcher->fetchContent($monitor->url, $waitSeconds);
                $jsFetcher->cleanup();

                // If JavaScript content was successfully fetched, use it for validation
                if (! empty($jsContent)) {
                    $responseBody = $jsContent;
                }
            } catch (\Exception $e) {
                // Log error but continue with regular content validation
                \Illuminate\Support\Facades\Log::warning('JavaScript content fetching failed, falling back to regular content', [
                    'monitor_id' => $monitor->id,
                    'url' => $monitor->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // First, run the basic look_for_string check for backward compatibility
        if (! $this->checkBasicLookForString($responseBody, $monitor)) {
            return false;
        }

        // Then run enhanced content validation
        return $this->validateEnhancedContent($responseBody, $monitor);
    }

    public function getFailureReason(ResponseInterface $response, Monitor $monitor): string
    {
        $responseBody = (string) $response->getBody();

        // If JavaScript is enabled, fetch content using headless browser
        if ($this->isJavaScriptEnabled($monitor)) {
            try {
                $jsFetcher = new JavaScriptContentFetcher;
                $waitSeconds = $this->getJavaScriptWaitSeconds($monitor);
                $jsContent = $jsFetcher->fetchContent($monitor->url, $waitSeconds);
                $jsFetcher->cleanup();

                // If JavaScript content was successfully fetched, use it for validation
                if (! empty($jsContent)) {
                    $responseBody = $jsContent;
                }
            } catch (\Exception $e) {
                // Continue with regular content for failure reason
            }
        }

        // Check basic look_for_string first
        if (! $this->checkBasicLookForString($responseBody, $monitor)) {
            return "String `{$monitor->look_for_string}` was not found on the response.";
        }

        // Check enhanced content validation
        $failureReason = $this->getEnhancedValidationFailureReason($responseBody, $monitor);

        return $failureReason ?: 'Content validation failed for unknown reason.';
    }

    /**
     * Check basic look_for_string for backward compatibility
     */
    private function checkBasicLookForString(string $responseBody, Monitor $monitor): bool
    {
        if (empty($monitor->look_for_string)) {
            return true;
        }

        return Str::contains($responseBody, $monitor->look_for_string);
    }

    /**
     * Validate enhanced content using the new fields
     */
    private function validateEnhancedContent(string $responseBody, Monitor $monitor): bool
    {
        // Check expected strings - all must be present
        if (! $this->validateExpectedStrings($responseBody, $monitor)) {
            return false;
        }

        // Check forbidden strings - none should be present
        if (! $this->validateForbiddenStrings($responseBody, $monitor)) {
            return false;
        }

        // Check regex patterns - all must match
        if (! $this->validateRegexPatterns($responseBody, $monitor)) {
            return false;
        }

        return true;
    }

    /**
     * Get detailed failure reason for enhanced validation
     */
    private function getEnhancedValidationFailureReason(string $responseBody, Monitor $monitor): string
    {
        // Check expected strings
        $expectedStrings = $this->getExpectedStrings($monitor);
        if (! empty($expectedStrings)) {
            foreach ($expectedStrings as $expectedString) {
                if (! $this->containsWord($responseBody, $expectedString)) {
                    return "Expected word `{$expectedString}` was not found in response (uses word boundary matching).";
                }
            }
        }

        // Check forbidden strings
        $forbiddenStrings = $this->getForbiddenStrings($monitor);
        if (! empty($forbiddenStrings)) {
            foreach ($forbiddenStrings as $forbiddenString) {
                if (Str::contains($responseBody, $forbiddenString)) {
                    return "Forbidden string `{$forbiddenString}` was found in response.";
                }
            }
        }

        // Check regex patterns
        $regexPatterns = $this->getRegexPatterns($monitor);
        if (! empty($regexPatterns)) {
            foreach ($regexPatterns as $pattern) {
                if (! preg_match($pattern, $responseBody)) {
                    return "Regex pattern `{$pattern}` did not match response content.";
                }
            }
        }

        return '';
    }

    /**
     * Validate that all expected strings are present
     * Uses word boundary matching to ensure exact word matches
     */
    private function validateExpectedStrings(string $responseBody, Monitor $monitor): bool
    {
        $expectedStrings = $this->getExpectedStrings($monitor);

        if (empty($expectedStrings)) {
            return true;
        }

        foreach ($expectedStrings as $expectedString) {
            if (! $this->containsWord($responseBody, $expectedString)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that no forbidden strings are present
     */
    private function validateForbiddenStrings(string $responseBody, Monitor $monitor): bool
    {
        $forbiddenStrings = $this->getForbiddenStrings($monitor);

        if (empty($forbiddenStrings)) {
            return true;
        }

        foreach ($forbiddenStrings as $forbiddenString) {
            if (Str::contains($responseBody, $forbiddenString)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that all regex patterns match
     */
    private function validateRegexPatterns(string $responseBody, Monitor $monitor): bool
    {
        $regexPatterns = $this->getRegexPatterns($monitor);

        if (empty($regexPatterns)) {
            return true;
        }

        foreach ($regexPatterns as $pattern) {
            if (! preg_match($pattern, $responseBody)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get expected strings from monitor
     */
    private function getExpectedStrings(Monitor $monitor): array
    {
        if (empty($monitor->content_expected_strings)) {
            return [];
        }

        // Handle both JSON strings and arrays (due to model casting)
        if (is_array($monitor->content_expected_strings)) {
            return array_filter($monitor->content_expected_strings);
        }

        $decoded = json_decode($monitor->content_expected_strings, true);

        return is_array($decoded) ? array_filter($decoded) : [];
    }

    /**
     * Get forbidden strings from monitor
     */
    private function getForbiddenStrings(Monitor $monitor): array
    {
        if (empty($monitor->content_forbidden_strings)) {
            return [];
        }

        // Handle both JSON strings and arrays (due to model casting)
        if (is_array($monitor->content_forbidden_strings)) {
            return array_filter($monitor->content_forbidden_strings);
        }

        $decoded = json_decode($monitor->content_forbidden_strings, true);

        return is_array($decoded) ? array_filter($decoded) : [];
    }

    /**
     * Get regex patterns from monitor
     */
    private function getRegexPatterns(Monitor $monitor): array
    {
        if (empty($monitor->content_regex_patterns)) {
            return [];
        }

        // Handle both JSON strings and arrays (due to model casting)
        if (is_array($monitor->content_regex_patterns)) {
            return array_filter($monitor->content_regex_patterns);
        }

        $decoded = json_decode($monitor->content_regex_patterns, true);

        return is_array($decoded) ? array_filter($decoded) : [];
    }

    /**
     * Check if JavaScript is enabled for the monitor
     */
    private function isJavaScriptEnabled(Monitor $monitor): bool
    {
        return (bool) $monitor->javascript_enabled;
    }

    /**
     * Get JavaScript wait seconds, with bounds checking
     */
    private function getJavaScriptWaitSeconds(Monitor $monitor): int
    {
        $waitSeconds = (int) ($monitor->javascript_wait_seconds ?? 5);

        // Bound the wait time between 1 and 30 seconds
        return max(1, min(30, $waitSeconds));
    }

    /**
     * Check if response body contains a word using word boundary matching
     * This ensures "Erdgasversorger" won't match "Erdgasversorger1"
     */
    private function containsWord(string $responseBody, string $word): bool
    {
        // Escape special regex characters in the search word
        $escapedWord = preg_quote($word, '/');

        // Use word boundaries (\b) to match whole words only
        // The 'u' flag enables Unicode support
        $pattern = '/\b'.$escapedWord.'\b/u';

        return preg_match($pattern, $responseBody) === 1;
    }
}
