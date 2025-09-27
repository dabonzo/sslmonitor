<?php

use App\Models\User;
use App\Models\Website;

it('displays alert settings page with proper data structure', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Alerts')
        ->has('alertConfigurations')
        ->has('defaultConfigurations')
        ->has('websites')
        ->has('alertsByWebsite')
        ->has('alertTypes')
        ->has('notificationChannels')
        ->has('alertLevels')
        ->where('websites', function ($websites) {
            return count($websites) === 3;
        })
    );
});

it('includes comprehensive alert types in response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->where('alertTypes.ssl_expiry', 'SSL Certificate Expiry')
        ->where('alertTypes.uptime_down', 'Website Down')
        ->where('alertTypes.response_time', 'Response Time Monitoring')
        ->where('alertTypes.ssl_invalid', 'SSL Certificate Invalid')
        ->where('alertTypes.lets_encrypt_renewal', 'Let\'s Encrypt Renewal')
    );
});

it('includes proper notification channels', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->where('notificationChannels.email', 'Email')
        ->where('notificationChannels.slack', 'Slack')
        ->where('notificationChannels.dashboard', 'Dashboard')
    );
});

it('includes all alert levels with proper labels', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->where('alertLevels.critical', 'Critical')
        ->where('alertLevels.urgent', 'Urgent')
        ->where('alertLevels.warning', 'Warning')
        ->where('alertLevels.info', 'Info')
    );
});

it('can create new alert configuration', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'ssl_expiry',
        'alert_level' => 'critical',
        'threshold_days' => 7,
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Alert configuration created successfully. It will now monitor all your websites.');
});

it('can create alert without threshold days', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'uptime_down',
        'alert_level' => 'urgent',
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('validates required fields when creating alert', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/alerts', []);

    $response->assertSessionHasErrors(['alert_type', 'alert_level']);
});

it('validates alert type is valid', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'invalid_type',
        'alert_level' => 'critical',
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertSessionHasErrors(['alert_type']);
});

it('validates alert level is valid', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'ssl_expiry',
        'alert_level' => 'invalid_level',
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertSessionHasErrors(['alert_level']);
});

it('validates threshold days is within range', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'ssl_expiry',
        'alert_level' => 'critical',
        'threshold_days' => 500, // Too high
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertSessionHasErrors(['threshold_days']);
});

it('validates threshold days is positive', function () {
    $user = User::factory()->create();

    $alertData = [
        'alert_type' => 'ssl_expiry',
        'alert_level' => 'critical',
        'threshold_days' => 0, // Too low
    ];

    $response = $this->actingAs($user)->post('/settings/alerts', $alertData);

    $response->assertSessionHasErrors(['threshold_days']);
});

it('requires authentication to access alerts settings', function () {
    $response = $this->get('/settings/alerts');

    $response->assertRedirect('/login');
});

it('requires authentication to create alerts', function () {
    $alertData = [
        'alert_type' => 'ssl_expiry',
        'alert_level' => 'critical',
    ];

    $response = $this->post('/settings/alerts', $alertData);

    $response->assertRedirect('/login');
});

it('includes mock alert configurations in response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->has('alertConfigurations') // Just ensure the key exists
        ->has('defaultConfigurations') // Ensure defaults exist
    );
});

it('includes default configurations for users to enable', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->has('defaultConfigurations') // Just ensure defaults exist
        ->has('alertTypes') // Ensure alert types exist
        ->has('notificationChannels') // Ensure notification channels exist
    );
});

it('groups alerts by website correctly', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/settings/alerts');

    $response->assertInertia(fn ($page) => $page
        ->has('alertsByWebsite', 2) // Should group by website count
        ->has('alertsByWebsite.0.website')
        ->has('alertsByWebsite.0.alerts')
        ->has('alertsByWebsite.1.website')
        ->has('alertsByWebsite.1.alerts')
    );
});
