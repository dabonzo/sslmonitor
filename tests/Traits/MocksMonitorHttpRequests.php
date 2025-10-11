<?php

namespace Tests\Traits;

use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Mockery;

trait MocksMonitorHttpRequests
{
    /**
     * Mock all HTTP requests and Spatie Monitor checks to avoid real network calls
     */
    protected function mockMonitorHttpRequests(): void
    {
        // Mock HTTP client to return successful responses
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'body' => 'Mock response body',
            ], 200),
        ]);

        // Mock the Monitor's check methods directly to avoid HTTP calls
        $this->mockMonitorCheckMethods();
    }

    /**
     * Mock Monitor check methods to avoid actual HTTP requests
     */
    protected function mockMonitorCheckMethods(): void
    {
        // Partial mock Monitor to override check methods
        $this->partialMock(\Spatie\UptimeMonitor\Models\Monitor::class, function ($mock) {
            $mock->shouldReceive('checkUptime')
                ->andReturnUsing(function () {
                    /** @var \Spatie\UptimeMonitor\Models\Monitor $this */
                    $this->uptime_status = 'up';
                    $this->uptime_check_response_time_in_ms = rand(50, 200);
                    $this->uptime_check_response_status_code = 200;
                    $this->uptime_last_check_date = now();
                    $this->save();
                });

            $mock->shouldReceive('checkCertificate')
                ->andReturnUsing(function () {
                    /** @var \Spatie\UptimeMonitor\Models\Monitor $this */
                    $this->certificate_status = 'valid';
                    $this->certificate_expiration_date = now()->addDays(90);
                    $this->certificate_issuer = 'Mock CA';
                    $this->save();
                });
        });
    }

    /**
     * Mock Guzzle client used by Spatie UptimeMonitor
     */
    protected function mockGuzzleClient(): void
    {
        $responseMock = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn('Mock response');
        $responseMock->shouldReceive('getHeader')->andReturn([]);
        $responseMock->shouldReceive('getHeaders')->andReturn([]);

        $clientMock = Mockery::mock(\GuzzleHttp\Client::class);
        $clientMock->shouldReceive('request')->andReturn($responseMock);
        $clientMock->shouldReceive('get')->andReturn($responseMock);
        $clientMock->shouldReceive('head')->andReturn($responseMock);

        $this->app->instance(\GuzzleHttp\Client::class, $clientMock);
    }

    /**
     * Mock DNS lookups to avoid network calls
     */
    protected function mockDnsLookups(): void
    {
        // DNS lookups are typically done via gethostbyname
        // Most tests won't need this, but it's here if needed
    }

    /**
     * Mock successful monitor check results
     */
    protected function mockSuccessfulMonitorCheck(Monitor $monitor): void
    {
        $monitor->uptime_status = 'up';
        $monitor->uptime_check_response_time_in_ms = 150;
        $monitor->uptime_check_response_status_code = 200;
        $monitor->uptime_check_failure_reason = null;
        $monitor->certificate_status = 'valid';
        $monitor->certificate_expiration_date = now()->addDays(90);
        $monitor->certificate_issuer = 'Mock Certificate Authority';
        $monitor->save();
    }

    /**
     * Mock failed monitor check results
     */
    protected function mockFailedMonitorCheck(Monitor $monitor): void
    {
        $monitor->uptime_status = 'down';
        $monitor->uptime_check_response_time_in_ms = null;
        $monitor->uptime_check_response_status_code = null;
        $monitor->uptime_check_failure_reason = 'Connection timeout';
        $monitor->certificate_status = 'invalid';
        $monitor->certificate_expiration_date = null;
        $monitor->certificate_issuer = null;
        $monitor->save();
    }

    /**
     * Setup method to be called in beforeEach
     */
    protected function setUpMocksMonitorHttpRequests(): void
    {
        $this->mockMonitorHttpRequests();
    }
}
