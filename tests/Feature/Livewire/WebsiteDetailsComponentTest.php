<?php

declare(strict_types=1);

use App\Livewire\WebsiteDetails;
use App\Models\User;
use App\Models\Website;
use App\Models\SslCheck;
use App\Services\SslCertificateChecker;
use App\Services\SslStatusCalculator;
use Livewire\Livewire;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->for($this->user)->create([
        'name' => 'Test Website',
        'url' => 'https://example.com'
    ]);
});

test('authenticated users can view website details page', function () {
    $this->actingAs($this->user)
        ->get(route('websites.show', $this->website))
        ->assertSeeLivewire('website-details')
        ->assertStatus(200);
});

test('guests cannot access website details page', function () {
    $this->get(route('websites.show', $this->website))
        ->assertRedirect(route('login'));
});

test('users cannot view websites belonging to other users', function () {
    $otherUser = User::factory()->create();
    $otherWebsite = Website::factory()->for($otherUser)->create();

    $this->actingAs($this->user)
        ->get(route('websites.show', $otherWebsite))
        ->assertForbidden();
});

test('website details component displays website information', function () {
    SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_VALID,
        'expires_at' => now()->addDays(30),
        'issuer' => 'Test CA',
        'subject' => 'example.com',
        'serial_number' => '123456789',
        'checked_at' => now()->subHours(1),
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('Test Website')
        ->assertSee('https://example.com')
        ->assertSee('SSL Certificate Details');
});

test('website details component shows ssl check history', function () {
    // Create SSL checks with different statuses and times
    $oldCheck = SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_VALID,
        'checked_at' => now()->subDays(7),
        'days_until_expiry' => 45,
    ]);
    
    $recentCheck = SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_EXPIRING_SOON,
        'checked_at' => now()->subHours(2),
        'days_until_expiry' => 7,
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('SSL Check History')
        ->assertSee('Valid')
        ->assertSee('Expiring Soon')
        ->assertViewHas('sslChecks', function ($checks) use ($oldCheck, $recentCheck) {
            return $checks->contains($oldCheck) && $checks->contains($recentCheck);
        });
});

test('website details component shows current ssl status', function () {
    $latestCheck = SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_VALID,
        'checked_at' => now()->subHours(1),
        'expires_at' => now()->addDays(45),
        'days_until_expiry' => 45,
        'issuer' => 'Let\'s Encrypt Authority X3',
        'subject' => 'CN=example.com',
        'is_valid' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('Current SSL Status')
        ->assertSee('Valid')
        ->assertSee('Let\'s Encrypt Authority X3')
        ->assertSee('CN=example.com')
        ->assertSee('45 days');
});

test('website details component handles websites with no ssl checks', function () {
    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('No SSL checks performed yet')
        ->assertSee('Check SSL Certificate');
});

test('website details component can trigger manual ssl check', function () {
    Queue::fake();

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->call('checkSslCertificate')
        ->assertDispatched('ssl-check-triggered')
        ->assertSee('SSL check queued successfully');

    Queue::assertPushed(\App\Jobs\CheckSslCertificateJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('website details component shows loading state during manual check', function () {
    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->set('isCheckingNow', true)
        ->assertSee('Checking SSL certificate...');
});

test('website details component can navigate to edit website', function () {
    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->call('editWebsite')
        ->assertRedirect(route('websites', ['edit' => $this->website->id]));
});

test('website details component can delete website', function () {
    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->call('deleteWebsite')
        ->assertRedirect(route('websites'));

    $this->assertDatabaseMissing('websites', [
        'id' => $this->website->id,
    ]);
});

test('website details component shows ssl certificate error messages', function () {
    SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_ERROR,
        'error_message' => 'Connection timeout while checking certificate',
        'checked_at' => now()->subHours(1),
        'is_valid' => false,
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('Connection timeout while checking certificate')
        ->assertSee('Error');
});

test('website details component displays ssl checks in chronological order', function () {
    $check1 = SslCheck::factory()->for($this->website)->create([
        'checked_at' => now()->subDays(3),
        'status' => SslStatusCalculator::STATUS_VALID,
    ]);
    
    $check2 = SslCheck::factory()->for($this->website)->create([
        'checked_at' => now()->subDays(1),
        'status' => SslStatusCalculator::STATUS_EXPIRING_SOON,
    ]);
    
    $check3 = SslCheck::factory()->for($this->website)->create([
        'checked_at' => now()->subHours(2),
        'status' => SslStatusCalculator::STATUS_EXPIRED,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website]);

    // Verify checks are in descending order (most recent first)
    $sslChecks = $component->instance()->sslChecks;
    expect($sslChecks->first()->id)->toBe($check3->id);
    expect($sslChecks->get(1)->id)->toBe($check2->id);
    expect($sslChecks->last()->id)->toBe($check1->id);
});

test('website details component paginates ssl check history', function () {
    // Create many SSL checks
    SslCheck::factory()->for($this->website)->count(25)->create([
        'checked_at' => now()->subDays(rand(1, 30)),
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertViewHas('sslChecks', function ($checks) {
            return $checks->count() <= 20; // Default pagination limit
        });
});

test('website details component shows ssl expiry countdown', function () {
    SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_EXPIRING_SOON,
        'expires_at' => now()->addDays(7),
        'days_until_expiry' => 7,
        'checked_at' => now()->subHours(1),
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('7 days until expiry')
        ->assertSee('Expires on');
});

test('website details component handles expired certificates', function () {
    SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_EXPIRED,
        'expires_at' => now()->subDays(5),
        'days_until_expiry' => -5,
        'checked_at' => now()->subHours(1),
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('Certificate expired')
        ->assertSee('5 days ago');
});

test('website details component refreshes data', function () {
    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->call('refreshData')
        ->assertDispatched('data-refreshed');
});

test('website details component shows certificate technical details', function () {
    SslCheck::factory()->for($this->website)->create([
        'status' => SslStatusCalculator::STATUS_VALID,
        'issuer' => 'Let\'s Encrypt Authority X3',
        'subject' => 'CN=example.com',
        'serial_number' => '03:AC:F7:4A:B2:C3:9E:8F:12:34:56:78:90:AB:CD:EF',
        'signature_algorithm' => 'sha256WithRSAEncryption',
        'checked_at' => now()->subHours(1),
    ]);

    Livewire::actingAs($this->user)
        ->test('website-details', ['website' => $this->website])
        ->assertSee('Technical Details')
        ->assertSee('Let\'s Encrypt Authority X3')
        ->assertSee('CN=example.com')
        ->assertSee('03:AC:F7:4A:B2:C3:9E:8F:12:34:56:78:90:AB:CD:EF')
        ->assertSee('sha256WithRSAEncryption');
});