<?php
use App\Models\AlertConfiguration;
use App\Models\User;
describe('Alert Configuration', function () {
    test('authenticated user can access alerts settings', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('settings.alerts.edit'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/AlertConfiguration')
        );
    });
    test('unauthenticated user cannot access alerts settings', function () {
        $response = $this->get(route('settings.alerts.edit'));
        $response->assertRedirect(route('login'));
    });
    test('alerts settings page displays all alert types', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('settings.alerts.edit'));
        $response->assertStatus(200);
        $response->assertSee('SSL')
            ->or($response)->assertSee('Uptime')
            ->or($response)->assertSee('Response Time');
    });
    test('user can enable SSL expiry alerts', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.alerts.store'), [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 30,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $alert = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
            ->first();
        expect($alert)->not->toBeNull();
        expect($alert->enabled)->toBeTrue();
        expect($alert->threshold_days)->toBe(30);
    });
    test('user can enable uptime alerts', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.alerts.store'), [
            'alert_type' => AlertConfiguration::ALERT_UPTIME_DOWN,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $alert = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_UPTIME_DOWN)
            ->first();
        expect($alert)->not->toBeNull();
        expect($alert->enabled)->toBeTrue();
    });
    test('user can configure notification channels', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.alerts.store'), [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 14,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [
                AlertConfiguration::CHANNEL_EMAIL,
                AlertConfiguration::CHANNEL_DASHBOARD,
            ],
        ]);
        $alert = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
            ->first();
        expect($alert->notification_channels)->toContain(AlertConfiguration::CHANNEL_EMAIL)
            ->and($alert->notification_channels)->toContain(AlertConfiguration::CHANNEL_DASHBOARD);
    });
    test('user can update alert configuration', function () {
        $user = User::factory()->create();
        $alert = AlertConfiguration::create([
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 7,
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $response = $this->actingAs($user)->put(route('settings.alerts.update', $alert), [
            'threshold_days' => 14,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
        ]);
        $alert->refresh();
        expect($alert->threshold_days)->toBe(14);
        expect($alert->alert_level)->toBe(AlertConfiguration::LEVEL_WARNING);
    });
    test('user can disable alert type', function () {
        $user = User::factory()->create();
        $alert = AlertConfiguration::create([
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 30,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $response = $this->actingAs($user)->put(route('settings.alerts.update', $alert), [
            'enabled' => false,
        ]);
        $alert->refresh();
        expect($alert->enabled)->toBeFalse();
    });
    test('alert configuration has threshold days field', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('settings.alerts.edit'));
        $response->assertStatus(200);
        $response->assertSee('days')
            ->or($response)->assertSee('threshold')
            ->or($response)->assertSee('Threshold');
    });
    test('alert configuration has alert level selector', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('settings.alerts.edit'));
        $response->assertStatus(200);
        $response->assertSee('level')
            ->or($response)->assertSee('priority')
            ->or($response)->assertSee('severity');
    });
    test('user can select multiple notification channels', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.alerts.store'), [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 30,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [
                AlertConfiguration::CHANNEL_EMAIL,
                AlertConfiguration::CHANNEL_DASHBOARD,
                AlertConfiguration::CHANNEL_SLACK,
            ],
        ]);
        $alert = AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
            ->first();
        expect($alert->notification_channels)->toHaveCount(3);
    });
    test('user can delete alert configuration', function () {
        $user = User::factory()->create();
        $alert = AlertConfiguration::create([
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 30,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $response = $this->actingAs($user)->delete(route('settings.alerts.destroy', $alert));
        expect(AlertConfiguration::find($alert->id))->toBeNull();
    });
    test('user cannot access other users alert configurations', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = AlertConfiguration::create([
            'user_id' => $user1->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'enabled' => true,
            'threshold_days' => 30,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);
        $response = $this->actingAs($user2)->delete(route('settings.alerts.destroy', $alert));
        $response->assertStatus(403);
    });
})->group('alerts', 'configuration');
