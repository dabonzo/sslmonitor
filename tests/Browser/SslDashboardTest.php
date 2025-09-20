<?php

use App\Models\User;
use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;

test('ssl dashboard displays websites correctly', function () {
    $user = User::factory()->create();

    // Create websites with different SSL statuses
    $validWebsite = Website::factory()->create(['user_id' => $user->id, 'name' => 'Valid Site']);
    $expiredWebsite = Website::factory()->create(['user_id' => $user->id, 'name' => 'Expired Site']);
    $expiringSoonWebsite = Website::factory()->create(['user_id' => $user->id, 'name' => 'Expiring Soon Site']);

    // Create SSL certificates
    SslCertificate::factory()->create(['website_id' => $validWebsite->id]);
    SslCertificate::factory()->expired()->create(['website_id' => $expiredWebsite->id]);
    SslCertificate::factory()->expiringSoon()->create(['website_id' => $expiringSoonWebsite->id]);

    // Create recent SSL checks
    SslCheck::factory()->valid()->create(['website_id' => $validWebsite->id]);
    SslCheck::factory()->expired()->create(['website_id' => $expiredWebsite->id]);
    SslCheck::factory()->expiringSoon()->create(['website_id' => $expiringSoonWebsite->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('Valid Site')
        ->assertSee('Expired Site')
        ->assertSee('Expiring Soon Site')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

test('ssl dashboard shows certificate status indicators', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    SslCertificate::factory()->expiringSoon()->create(['website_id' => $website->id]);
    SslCheck::factory()->expiringSoon()->create(['website_id' => $website->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('expiring')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard allows checking certificate manually', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Site',
        'url' => 'https://example.com'
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->assertSee('Test Site')
        ->click('[data-testid="check-ssl-button"]')
        ->waitForText('Checking...')
        ->waitForText('Certificate check complete', 10)
        ->assertNoJavascriptErrors();
});

test('ssl dashboard filters work correctly', function () {
    $user = User::factory()->create();

    // Create websites with different statuses
    $validWebsite = Website::factory()->create(['user_id' => $user->id, 'name' => 'Valid Site']);
    $expiredWebsite = Website::factory()->create(['user_id' => $user->id, 'name' => 'Expired Site']);

    SslCheck::factory()->valid()->create(['website_id' => $validWebsite->id]);
    SslCheck::factory()->expired()->create(['website_id' => $expiredWebsite->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    // Test filtering by expired certificates
    $page->click('[data-testid="filter-expired"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Expired Site')
        ->assertDontSee('Valid Site');

    // Test filtering by valid certificates
    $page->click('[data-testid="filter-valid"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Valid Site')
        ->assertDontSee('Expired Site');

    // Test showing all
    $page->click('[data-testid="filter-all"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Valid Site')
        ->assertSee('Expired Site')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard search functionality works', function () {
    $user = User::factory()->create();

    Website::factory()->create(['user_id' => $user->id, 'name' => 'GitHub Website']);
    Website::factory()->create(['user_id' => $user->id, 'name' => 'Google Website']);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->fill('[data-testid="search-input"]', 'GitHub')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('GitHub Website')
        ->assertDontSee('Google Website');

    $page->fill('[data-testid="search-input"]', '')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('GitHub Website')
        ->assertSee('Google Website')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard responds to different screen sizes', function () {
    $user = User::factory()->create();
    Website::factory()->create(['user_id' => $user->id, 'name' => 'Test Site']);

    // Test desktop view
    $page = $this->actingAs($user)->visitOnDevice('/dashboard', 'laptop');

    $page->assertSee('Test Site')
        ->assertVisible('[data-testid="desktop-navigation"]')
        ->assertNoJavascriptErrors();

    // Test mobile view
    $page = $this->actingAs($user)->visitOnDevice('/dashboard', 'iPhone 12');

    $page->assertSee('Test Site')
        ->assertVisible('[data-testid="mobile-navigation"]')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard works in dark mode', function () {
    $user = User::factory()->create();
    Website::factory()->create(['user_id' => $user->id, 'name' => 'Test Site']);

    $page = $this->actingAs($user)->visit('/dashboard');

    // Switch to dark mode
    $page->click('[data-testid="theme-toggle"]')
        ->waitFor('.dark')
        ->assertHasClass('html', 'dark')
        ->assertSee('Test Site')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard certificate details modal works', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id, 'name' => 'Test Site']);

    $certificate = SslCertificate::factory()->create([
        'website_id' => $website->id,
        'issuer' => 'Let\'s Encrypt Authority X3',
        'subject' => 'test.example.com',
    ]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('[data-testid="certificate-details-button"]')
        ->waitForText('Certificate Details')
        ->assertSee('Let\'s Encrypt Authority X3')
        ->assertSee('test.example.com')
        ->click('[data-testid="close-modal"]')
        ->waitUntilMissingText('Certificate Details')
        ->assertNoJavascriptErrors();
});

test('ssl dashboard bulk actions work', function () {
    $user = User::factory()->create();

    $website1 = Website::factory()->create(['user_id' => $user->id, 'name' => 'Site 1']);
    $website2 = Website::factory()->create(['user_id' => $user->id, 'name' => 'Site 2']);

    $page = $this->actingAs($user)->visit('/dashboard');

    // Select multiple websites
    $page->check('[data-testid="checkbox-website-' . $website1->id . '"]')
        ->check('[data-testid="checkbox-website-' . $website2->id . '"]')
        ->assertVisible('[data-testid="bulk-actions"]')
        ->click('[data-testid="bulk-check-ssl"]')
        ->waitForText('Checking SSL certificates...')
        ->waitForText('Bulk SSL check completed', 15)
        ->assertNoJavascriptErrors();
});

test('ssl dashboard real-time updates work', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id, 'name' => 'Test Site']);

    $page = $this->actingAs($user)->visit('/dashboard');

    // Simulate real-time SSL check completion
    $page->waitForText('Test Site')
        ->executeScript('
            window.Echo.private("user.' . $user->id . '")
                .listen("SslCheckCompleted", function(e) {
                    // Simulate real-time update
                });
        ')
        ->waitFor('[data-testid="real-time-indicator"]', 5)
        ->assertNoJavascriptErrors();
});

test('ssl dashboard export functionality works', function () {
    $user = User::factory()->create();

    $website = Website::factory()->create(['user_id' => $user->id]);
    SslCheck::factory()->create(['website_id' => $website->id]);

    $page = $this->actingAs($user)->visit('/dashboard');

    $page->click('[data-testid="export-data"]')
        ->waitForText('Export Options')
        ->click('[data-testid="export-csv"]')
        ->waitForText('Export started')
        ->assertNoJavascriptErrors();
});