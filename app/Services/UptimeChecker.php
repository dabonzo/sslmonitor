<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class UptimeChecker
{
    public function checkWebsite(Website $website): UptimeCheckResult
    {
        $config = $website->monitoring_config ?? [];

        // Use JavaScript-enabled checker if enabled for this website
        if ($config['javascript_enabled'] ?? false) {
            $jsChecker = new JavaScriptUptimeChecker;

            return $jsChecker->checkWebsite($website);
        }

        // Use standard HTTP checker for non-JavaScript websites
        $startTime = microtime(true);

        try {
            $response = $this->makeRequest($website);
            $endTime = microtime(true);
            $responseTime = (int) (($endTime - $startTime) * 1000); // Convert to milliseconds

            $finalUrl = $response->effectiveUri() ?? $website->url;
            $httpStatusCode = $response->status();
            $responseSize = strlen($response->body());

            // Check if status code matches expected
            $expectedStatusCode = $config['expected_status_code'] ?? 200;
            if ($httpStatusCode !== $expectedStatusCode) {
                $errorMessage = $httpStatusCode >= 500
                    ? "HTTP {$httpStatusCode} Server Error"
                    : "Expected status {$expectedStatusCode}, got {$httpStatusCode}";

                return new UptimeCheckResult(
                    status: 'down',
                    httpStatusCode: $httpStatusCode,
                    responseTime: $responseTime,
                    responseSize: $responseSize,
                    contentCheckPassed: false,
                    finalUrl: $finalUrl,
                    errorMessage: $errorMessage
                );
            }

            // Check content validation
            $contentValidation = $this->validateContent($response->body(), $website);

            // Determine status based on response time and content
            if (! $contentValidation['passed']) {
                return new UptimeCheckResult(
                    status: 'content_mismatch',
                    httpStatusCode: $httpStatusCode,
                    responseTime: $responseTime,
                    responseSize: $responseSize,
                    contentCheckPassed: false,
                    contentCheckError: $contentValidation['error'],
                    finalUrl: $finalUrl,
                );
            }

            $maxResponseTime = $config['max_response_time'] ?? 30000; // Default 30 seconds in ms
            if ($responseTime > $maxResponseTime) {
                return new UptimeCheckResult(
                    status: 'slow',
                    httpStatusCode: $httpStatusCode,
                    responseTime: $responseTime,
                    responseSize: $responseSize,
                    contentCheckPassed: true,
                    finalUrl: $finalUrl,
                );
            }

            return new UptimeCheckResult(
                status: 'up',
                httpStatusCode: $httpStatusCode,
                responseTime: $responseTime,
                responseSize: $responseSize,
                contentCheckPassed: true,
                finalUrl: $finalUrl,
            );

        } catch (ConnectionException $e) {
            return new UptimeCheckResult(
                status: 'down',
                errorMessage: $e->getMessage()
            );
        } catch (\Exception $e) {
            return new UptimeCheckResult(
                status: 'down',
                errorMessage: $e->getMessage()
            );
        }
    }

    private function makeRequest(Website $website): Response
    {
        $config = $website->monitoring_config ?? [];
        $timeout = ($config['timeout'] ?? 30); // Timeout in seconds

        $client = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => 'SSL-Monitor-Uptime-Checker/4.0',
            ]);

        $followRedirects = $config['follow_redirects'] ?? true;
        $maxRedirects = $config['max_redirects'] ?? 5;

        if ($followRedirects) {
            $redirectCount = 0;
            $currentUrl = $website->url;

            while ($redirectCount < $maxRedirects) {
                $response = $client->withoutRedirecting()->get($currentUrl);

                if (! $response->redirect()) {
                    return $response;
                }

                $redirectCount++;
                $currentUrl = $response->header('Location');

                if (! $currentUrl) {
                    throw new \Exception('Redirect without Location header');
                }

                // Handle relative URLs
                if (! parse_url($currentUrl, PHP_URL_SCHEME)) {
                    $baseUrl = parse_url($website->url);
                    $currentUrl = $baseUrl['scheme'].'://'.$baseUrl['host'].$currentUrl;
                }
            }

            throw new \Exception('Too many redirects');
        } else {
            $response = $client->withoutRedirecting()->get($website->url);

            if ($response->redirect()) {
                return $response; // Return the redirect response as-is (will be marked as down)
            }

            return $response;
        }
    }

    private function validateContent(string $content, Website $website): array
    {
        $config = $website->monitoring_config ?? [];
        $expectedContent = $config['expected_content'] ?? null;
        $forbiddenContent = $config['forbidden_content'] ?? null;

        // If no content validation configured, consider it passed
        if (empty($expectedContent) && empty($forbiddenContent)) {
            return ['passed' => true, 'error' => null];
        }

        // Check for expected content
        if ($expectedContent && ! str_contains($content, $expectedContent)) {
            return [
                'passed' => false,
                'error' => "Expected content not found: '{$expectedContent}'",
            ];
        }

        // Check for forbidden content
        if ($forbiddenContent && str_contains($content, $forbiddenContent)) {
            return [
                'passed' => false,
                'error' => "Forbidden content found: '{$forbiddenContent}'",
            ];
        }

        return ['passed' => true, 'error' => null];
    }
}
