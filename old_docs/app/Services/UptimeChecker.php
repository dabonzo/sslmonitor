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
        // Use JavaScript-enabled checker if enabled for this website
        if ($website->javascript_enabled) {
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
            if ($httpStatusCode !== $website->expected_status_code) {
                $errorMessage = $httpStatusCode >= 500
                    ? "HTTP {$httpStatusCode} Server Error"
                    : "Expected status {$website->expected_status_code}, got {$httpStatusCode}";

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

            if ($responseTime > $website->max_response_time) {
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
        $client = Http::timeout($website->max_response_time / 1000) // Convert ms to seconds
            ->withHeaders([
                'User-Agent' => 'SSL-Monitor-Uptime-Checker/1.0',
            ]);

        if ($website->follow_redirects) {
            $redirectCount = 0;
            $currentUrl = $website->url;

            while ($redirectCount < $website->max_redirects) {
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
