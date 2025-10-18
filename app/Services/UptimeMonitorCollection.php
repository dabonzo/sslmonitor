<?php

namespace App\Services;

use App\Models\Monitor;
use Generator;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\EachPromise;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Helpers\ConsoleOutput;
use Spatie\UptimeMonitor\MonitorCollection;

class UptimeMonitorCollection extends MonitorCollection
{
    private array $startTimes = [];

    public function checkUptime(): void
    {
        $this->resetItemKeys();

        (new EachPromise($this->getPromises(), [
            'concurrency' => config('uptime-monitor.uptime_check.concurrent_checks'),
            'fulfilled' => function (ResponseInterface $response, $index) {
                $monitor = $this->getMonitorAtIndex($index);
                $responseTime = $this->calculateResponseTime($index);

                ConsoleOutput::info("Could reach {$monitor->url} in {$responseTime}ms");

                $monitor->uptimeRequestSucceeded($response, $responseTime);
            },

            'rejected' => function (TransferException $exception, $index) {
                $monitor = $this->getMonitorAtIndex($index);

                ConsoleOutput::error("Could not reach {$monitor->url} error: `{$exception->getMessage()}`");

                $monitor->uptimeRequestFailed($exception->getMessage());
            },
        ]))->promise()->wait();
    }

    protected function getPromises(): Generator
    {
        $client = GuzzleFactory::make(
            config('uptime-monitor.uptime_check.guzzle_options', []),
            config('uptime-monitor.uptime-check.retry_connection_after_milliseconds', 100)
        );

        foreach ($this->items as $index => $monitor) {
            // Ensure we have a Monitor object
            if (is_array($monitor)) {
                $monitor = new Monitor($monitor);
            }

            ConsoleOutput::info("Checking {$monitor->url}");

            // Record start time for this request
            $this->startTimes[$index] = microtime(true);

            $promise = $client->requestAsync(
                $monitor->uptime_check_method,
                $monitor->url,
                array_filter([
                    'connect_timeout' => config('uptime-monitor.uptime_check.timeout_per_site'),
                    'headers' => $this->promiseHeaders($monitor),
                    'body' => $monitor->uptime_check_payload,
                ])
            )->then(
                function (ResponseInterface $response) {
                    return $response;
                },
                function (TransferException $exception) {
                    if (in_array($exception->getCode(), config('uptime-monitor.uptime_check.additional_status_codes', []))) {
                        return $exception->getResponse();
                    }

                    throw $exception;
                }
            );

            yield $promise;
        }
    }

    private function calculateResponseTime(int $index): float
    {
        if (! isset($this->startTimes[$index])) {
            return 0;
        }

        $endTime = microtime(true);
        $startTime = $this->startTimes[$index];

        return round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
    }

    private function promiseHeaders(Monitor $monitor): array
    {
        return collect([])
            ->merge(['User-Agent' => config('uptime-monitor.uptime_check.user_agent')])
            ->merge(config('uptime-monitor.uptime_check.additional_headers') ?? [])
            ->merge($monitor->uptime_check_additional_headers)
            ->toArray();
    }

    protected function getMonitorAtIndex(int $index): Monitor
    {
        return $this->items[$index];
    }
}
