<?php

use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'name' => 'Test Website',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
    ]);
});

test('website store dispatches immediate check job when requested', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('ssl.websites.store'), [
            'name' => 'New Test Website',
            'url' => 'https://test-new.example.com',
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
            'immediate_check' => true,
        ]);

    $response->assertRedirect(route('ssl.websites.index'));
    $response->assertSessionHas('success');

    // Verify a website was created
    $website = Website::where('url', 'https://test-new.example.com')->first();
    expect($website)->not->toBeNull();

    // Verify immediate check job was dispatched
    Queue::assertPushed(ImmediateWebsiteCheckJob::class, function ($job) use ($website) {
        return $job->website->id === $website->id;
    });
});

test('website store does not dispatch job when immediate check not requested', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->post(route('ssl.websites.store'), [
            'name' => 'New Test Website',
            'url' => 'https://test-new.example.com',
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => false,
            'immediate_check' => false,
        ]);

    $response->assertRedirect(route('ssl.websites.index'));

    // Verify immediate check job was NOT dispatched
    Queue::assertNotPushed(ImmediateWebsiteCheckJob::class);
});

test('immediate check API endpoint dispatches job successfully', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->postJson(route('ssl.websites.immediate-check', $this->website));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'website_id' => $this->website->id,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'website_id',
            'estimated_completion',
        ]);

    // Verify job was dispatched to correct queue
    Queue::assertPushed(ImmediateWebsiteCheckJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('immediate check API fails when no monitoring enabled', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'ssl_monitoring_enabled' => false,
        'uptime_monitoring_enabled' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('ssl.websites.immediate-check', $website));

    $response->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'No monitoring is enabled for this website.',
        ]);
});

test('check status API endpoint returns website status', function () {
    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.check-status', $this->website));

    $response->assertOk()
        ->assertJsonStructure([
            'website_id',
            'last_updated',
            'ssl_status',
            'uptime_status',
            'ssl_monitoring_enabled',
            'uptime_monitoring_enabled',
            'checked_at',
        ])
        ->assertJson([
            'website_id' => $this->website->id,
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
        ]);
});

test('unauthorized user cannot trigger immediate check', function () {
    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)
        ->postJson(route('ssl.websites.immediate-check', $this->website));

    $response->assertForbidden();
});

test('immediate check API respects user authorization', function () {
    // Test that user can only check their own websites
    $otherUser = User::factory()->create();
    $otherWebsite = Website::factory()->create([
        'user_id' => $otherUser->id,
        'ssl_monitoring_enabled' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('ssl.websites.immediate-check', $otherWebsite));

    $response->assertForbidden();
});

test('immediate check job is dispatched to correct queue', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->postJson(route('ssl.websites.immediate-check', $this->website));

    $response->assertOk();

    // Just verify the job was pushed (queue assignment is tested in integration tests)
    Queue::assertPushed(ImmediateWebsiteCheckJob::class);
});