<?php

use App\Models\User;
use App\Models\Website;

test('user can add new website', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="add-website-button"]')
        ->waitForText('Add New Website')
        ->fill('[data-testid="website-name"]', 'GitHub')
        ->fill('[data-testid="website-url"]', 'https://github.com')
        ->check('[data-testid="ssl-monitoring-enabled"]')
        ->check('[data-testid="uptime-monitoring-enabled"]')
        ->click('[data-testid="save-website"]')
        ->waitForText('Website added successfully')
        ->assertSee('GitHub')
        ->assertNoJavascriptErrors();

    // Verify website was created in database
    $this->assertDatabaseHas('websites', [
        'name' => 'GitHub',
        'url' => 'https://github.com',
        'user_id' => $user->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
    ]);
});

test('user can edit existing website', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'url' => 'https://old-url.com',
    ]);

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="edit-website-' . $website->id . '"]')
        ->waitForText('Edit Website')
        ->clear('[data-testid="website-name"]')
        ->fill('[data-testid="website-name"]', 'New Name')
        ->clear('[data-testid="website-url"]')
        ->fill('[data-testid="website-url"]', 'https://new-url.com')
        ->click('[data-testid="save-website"]')
        ->waitForText('Website updated successfully')
        ->assertSee('New Name')
        ->assertNoJavascriptErrors();

    // Verify website was updated in database
    $this->assertDatabaseHas('websites', [
        'id' => $website->id,
        'name' => 'New Name',
        'url' => 'https://new-url.com',
    ]);
});

test('user can delete website', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'To Be Deleted',
    ]);

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="delete-website-' . $website->id . '"]')
        ->waitForText('Confirm Deletion')
        ->assertSee('To Be Deleted')
        ->click('[data-testid="confirm-delete"]')
        ->waitForText('Website deleted successfully')
        ->assertDontSee('To Be Deleted')
        ->assertNoJavascriptErrors();

    // Verify website was deleted from database
    $this->assertDatabaseMissing('websites', [
        'id' => $website->id,
    ]);
});

test('website form validation works correctly', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="add-website-button"]')
        ->waitForText('Add New Website')
        ->click('[data-testid="save-website"]')
        ->waitForText('The name field is required')
        ->assertSee('The url field is required')
        ->assertNoJavascriptErrors();
});

test('website url validation works correctly', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="add-website-button"]')
        ->waitForText('Add New Website')
        ->fill('[data-testid="website-name"]', 'Test Site')
        ->fill('[data-testid="website-url"]', 'invalid-url')
        ->click('[data-testid="save-website"]')
        ->waitForText('Please enter a valid URL')
        ->assertNoJavascriptErrors();
});

test('duplicate url validation works correctly', function () {
    $user = User::factory()->create();
    Website::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com',
    ]);

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="add-website-button"]')
        ->waitForText('Add New Website')
        ->fill('[data-testid="website-name"]', 'Duplicate Site')
        ->fill('[data-testid="website-url"]', 'https://example.com')
        ->click('[data-testid="save-website"]')
        ->waitForText('You already have a website with this URL')
        ->assertNoJavascriptErrors();
});

test('website monitoring settings can be configured', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="add-website-button"]')
        ->waitForText('Add New Website')
        ->fill('[data-testid="website-name"]', 'Configured Site')
        ->fill('[data-testid="website-url"]', 'https://configured.com')
        ->click('[data-testid="advanced-settings"]')
        ->waitFor('[data-testid="check-interval"]')
        ->select('[data-testid="check-interval"]', '3600')
        ->fill('[data-testid="timeout"]', '30')
        ->fill('[data-testid="alert-days-before-expiry"]', '14')
        ->click('[data-testid="save-website"]')
        ->waitForText('Website added successfully')
        ->assertNoJavascriptErrors();

    // Verify monitoring configuration was saved
    $this->assertDatabaseHas('websites', [
        'name' => 'Configured Site',
        'check_interval' => 3600,
    ]);
});

test('website list pagination works', function () {
    $user = User::factory()->create();

    // Create more websites than fit on one page
    Website::factory(25)->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/websites');

    $page->assertSee('1')
        ->assertSee('2')
        ->click('[data-testid="next-page"]')
        ->waitFor('[data-testid="page-2"]')
        ->assertVisible('[data-testid="page-2"]')
        ->assertNoJavascriptErrors();
});

test('website search functionality works', function () {
    $user = User::factory()->create();

    Website::factory()->create(['user_id' => $user->id, 'name' => 'GitHub Site']);
    Website::factory()->create(['user_id' => $user->id, 'name' => 'Google Site']);

    $page = $this->actingAs($user)->visit('/websites');

    $page->fill('[data-testid="search-websites"]', 'GitHub')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('GitHub Site')
        ->assertDontSee('Google Site')
        ->clear('[data-testid="search-websites"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('GitHub Site')
        ->assertSee('Google Site')
        ->assertNoJavascriptErrors();
});

test('website status filter works', function () {
    $user = User::factory()->create();

    $activeWebsite = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'Active Site',
        'is_active' => true,
    ]);

    $inactiveWebsite = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'Inactive Site',
        'is_active' => false,
    ]);

    $page = $this->actingAs($user)->visit('/websites');

    // Filter by active websites
    $page->click('[data-testid="filter-active"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Active Site')
        ->assertDontSee('Inactive Site');

    // Filter by inactive websites
    $page->click('[data-testid="filter-inactive"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Inactive Site')
        ->assertDontSee('Active Site');

    // Show all websites
    $page->click('[data-testid="filter-all"]')
        ->waitFor('[data-testid="website-list"]')
        ->assertSee('Active Site')
        ->assertSee('Inactive Site')
        ->assertNoJavascriptErrors();
});

test('website bulk actions work', function () {
    $user = User::factory()->create();

    $website1 = Website::factory()->create(['user_id' => $user->id, 'name' => 'Site 1']);
    $website2 = Website::factory()->create(['user_id' => $user->id, 'name' => 'Site 2']);

    $page = $this->actingAs($user)->visit('/websites');

    // Select multiple websites
    $page->check('[data-testid="select-website-' . $website1->id . '"]')
        ->check('[data-testid="select-website-' . $website2->id . '"]')
        ->assertVisible('[data-testid="bulk-actions"]')
        ->click('[data-testid="bulk-delete"]')
        ->waitForText('Confirm Bulk Deletion')
        ->click('[data-testid="confirm-bulk-delete"]')
        ->waitForText('Websites deleted successfully')
        ->assertDontSee('Site 1')
        ->assertDontSee('Site 2')
        ->assertNoJavascriptErrors();
});

test('website import functionality works', function () {
    $user = User::factory()->create();

    $csvContent = "name,url\n" .
                 "GitHub,https://github.com\n" .
                 "Google,https://google.com";

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="import-websites"]')
        ->waitForText('Import Websites')
        ->attachFile('[data-testid="csv-file"]', $csvContent, 'websites.csv')
        ->click('[data-testid="upload-csv"]')
        ->waitForText('Import completed successfully')
        ->assertSee('GitHub')
        ->assertSee('Google')
        ->assertNoJavascriptErrors();
});

test('website export functionality works', function () {
    $user = User::factory()->create();

    Website::factory(3)->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/websites');

    $page->click('[data-testid="export-websites"]')
        ->waitForText('Export started')
        ->assertNoJavascriptErrors();
});