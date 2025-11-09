<?php

use Illuminate\Support\Facades\Redis;

test('horizon health check command handles Redis smembers returning false', function () {
    // Mock Redis to return false (simulating missing key)
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andReturnSelf();

    Redis::shouldReceive('smembers')
        ->with('masters')
        ->andReturn(false);

    // The command should handle false gracefully and return failure (Horizon not running)
    $this->artisan('horizon:health-check')
        ->expectsOutput('❌ Horizon is not running!')
        ->assertExitCode(1);
})->group('console');

test('horizon health check command handles Redis smembers returning empty array', function () {
    // Mock Redis to return empty array
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andReturnSelf();

    Redis::shouldReceive('smembers')
        ->with('masters')
        ->andReturn([]);

    // Empty array means no masters running
    $this->artisan('horizon:health-check')
        ->expectsOutput('❌ Horizon is not running!')
        ->assertExitCode(1);
})->group('console');

test('horizon health check command detects running Horizon', function () {
    // Mock Redis to return array with masters
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andReturnSelf();

    Redis::shouldReceive('smembers')
        ->with('masters')
        ->andReturn(['master1', 'master2']);

    // Mock the default connection for queue depth check
    Redis::shouldReceive('connection')
        ->withNoArgs()
        ->andReturnSelf();

    Redis::shouldReceive('llen')
        ->andReturn(0);

    // Mock failed_jobs count
    \DB::shouldReceive('table')
        ->with('failed_jobs')
        ->andReturnSelf();
    \DB::shouldReceive('count')
        ->andReturn(0);

    $this->artisan('horizon:health-check')
        ->expectsOutput('✅ Horizon is running')
        ->assertExitCode(0);
})->group('console');

test('horizon health check command handles Redis connection exception', function () {
    // Mock Redis to throw exception
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andThrow(new \Exception('Connection failed'));

    // The command should handle the exception gracefully
    $this->artisan('horizon:health-check')
        ->expectsOutput('❌ Horizon is not running!')
        ->assertExitCode(1);
})->group('console');

test('horizon health check warns on high queue depth', function () {
    // Mock Horizon running
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andReturnSelf();

    Redis::shouldReceive('smembers')
        ->with('masters')
        ->andReturn(['master1']);

    // Mock high queue depth
    Redis::shouldReceive('connection')
        ->withNoArgs()
        ->andReturnSelf();

    Redis::shouldReceive('llen')
        ->andReturn(50, 30, 30); // Total > 100

    // Mock failed_jobs count
    \DB::shouldReceive('table')
        ->with('failed_jobs')
        ->andReturnSelf();
    \DB::shouldReceive('count')
        ->andReturn(0);

    $this->artisan('horizon:health-check')
        ->assertExitCode(0);
})->group('console');

test('horizon health check reports failed jobs count', function () {
    // Mock Horizon running
    Redis::shouldReceive('connection')
        ->with('horizon')
        ->andReturnSelf();

    Redis::shouldReceive('smembers')
        ->with('masters')
        ->andReturn(['master1']);

    // Mock queue depth
    Redis::shouldReceive('connection')
        ->withNoArgs()
        ->andReturnSelf();

    Redis::shouldReceive('llen')
        ->andReturn(0);

    // Mock failed_jobs count with high value
    \DB::shouldReceive('table')
        ->with('failed_jobs')
        ->andReturnSelf();
    \DB::shouldReceive('count')
        ->andReturn(15); // High count to trigger warning

    $this->artisan('horizon:health-check')
        ->assertExitCode(0);
})->group('console');
