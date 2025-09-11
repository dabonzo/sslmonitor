<?php

use App\Jobs\CheckSslCertificateJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('ssl check job is queued when new website is created', function () {
    Queue::fake();

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'Test Website')
        ->set('url', 'https://example.com')
        ->call('save');

    Queue::assertPushed(CheckSslCertificateJob::class, function ($job) {
        return $job->website->name === 'Test Website'
            && $job->website->url === 'https://example.com';
    });
});

test('ssl check job is queued with correct website data', function () {
    Queue::fake();

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'My Test Site')
        ->set('url', 'google.com') // Test auto-https prefix
        ->call('save');

    Queue::assertPushed(CheckSslCertificateJob::class, function ($job) {
        return $job->website->name === 'My Test Site'
            && $job->website->url === 'https://google.com'
            && $job->website->user_id === auth()->id();
    });
});

test('ssl check job is not queued when website creation fails validation', function () {
    Queue::fake();

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', '') // Invalid - required field empty
        ->set('url', 'https://example.com')
        ->call('save');

    Queue::assertNotPushed(CheckSslCertificateJob::class);
});
