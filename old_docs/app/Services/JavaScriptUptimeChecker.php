<?php

namespace App\Services;

use App\Models\Website;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Spatie\Browsershot\Browsershot;

class JavaScriptUptimeChecker
{
    public function checkWebsite(Website $website): UptimeCheckResult
    {
        $startTime = microtime(true);

        try {
            // Use Browsershot to get JavaScript-rendered content
            $html = Browsershot::url($website->url)
                ->noSandbox() // Required for Docker environments
                ->disableGpu() // Better compatibility in containers
                ->waitUntilNetworkIdle() // Wait for JavaScript redirects
                ->timeout($website->max_response_time / 1000) // Convert ms to seconds
                ->bodyHtml();

            $endTime = microtime(true);
            $responseTime = (int) (($endTime - $startTime) * 1000); // Convert to milliseconds

            $responseSize = strlen($html);
            $httpStatusCode = 200; // Browsershot only returns content if page loads successfully

            // Check content validation with JavaScript-rendered HTML
            $contentValidation = $this->validateContent($html, $website);

            // Determine status based on response time and content
            if (! $contentValidation['passed']) {
                return new UptimeCheckResult(
                    status: 'content_mismatch',
                    httpStatusCode: $httpStatusCode,
                    responseTime: $responseTime,
                    responseSize: $responseSize,
                    contentCheckPassed: false,
                    contentCheckError: $contentValidation['error'],
                    finalUrl: $website->url, // Browsershot handles redirects internally
                );
            }

            if ($responseTime > $website->max_response_time) {
                return new UptimeCheckResult(
                    status: 'slow',
                    httpStatusCode: $httpStatusCode,
                    responseTime: $responseTime,
                    responseSize: $responseSize,
                    contentCheckPassed: true,
                    finalUrl: $website->url,
                );
            }

            return new UptimeCheckResult(
                status: 'up',
                httpStatusCode: $httpStatusCode,
                responseTime: $responseTime,
                responseSize: $responseSize,
                contentCheckPassed: true,
                finalUrl: $website->url,
            );

        } catch (ConnectionException $e) {
            return new UptimeCheckResult(
                status: 'down',
                errorMessage: 'Connection error: '.$e->getMessage()
            );
        } catch (Exception $e) {
            return new UptimeCheckResult(
                status: 'down',
                errorMessage: 'Browser error: '.$e->getMessage()
            );
        }
    }

    private function validateContent(string $content, Website $website): array
    {
        // If no content validation configured, consider it passed
        if (empty($website->expected_content) && empty($website->forbidden_content)) {
            return ['passed' => true, 'error' => null];
        }

        // Check for expected content
        if ($website->expected_content && ! str_contains($content, $website->expected_content)) {
            return [
                'passed' => false,
                'error' => "Expected content not found: '{$website->expected_content}'",
            ];
        }

        // Check for forbidden content
        if ($website->forbidden_content && str_contains($content, $website->forbidden_content)) {
            return [
                'passed' => false,
                'error' => "Forbidden content found: '{$website->forbidden_content}'",
            ];
        }

        return ['passed' => true, 'error' => null];
    }
}
