<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AlertConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_alert_without_threshold_response_time()
    {
        $user = User::factory()->create();

        // Ensure no existing alerts of this type exist (they might be auto-created)
        AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
            ->delete();

        $response = $this->actingAs($user)->post('/settings/alerts', [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            // Deliberately omit threshold_response_time
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Alert configuration created successfully. It will now monitor all your websites.');

        // Verify alert was created in database
        $this->assertDatabaseHas('alert_configurations', [
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'threshold_response_time' => null,
        ]);
    }

    public function test_can_create_response_time_alert_without_threshold()
    {
        $user = User::factory()->create();

        // Ensure no existing alerts of this type exist (they might be auto-created)
        AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_RESPONSE_TIME)
            ->delete();

        $response = $this->actingAs($user)->post('/settings/alerts', [
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            // Deliberately omit threshold_response_time
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Alert configuration created successfully. It will now monitor all your websites.');

        // Verify alert was created in database
        $this->assertDatabaseHas('alert_configurations', [
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'threshold_response_time' => null,
        ]);
    }

    public function test_can_create_alert_with_threshold_days_only()
    {
        $user = User::factory()->create();

        // Ensure no existing alerts of this type exist (they might be auto-created)
        AlertConfiguration::where('user_id', $user->id)
            ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
            ->delete();

        $response = $this->actingAs($user)->post('/settings/alerts', [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'threshold_days' => 14,
            // Deliberately omit threshold_response_time
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Alert configuration created successfully. It will now monitor all your websites.');

        // Verify alert was created in database
        $this->assertDatabaseHas('alert_configurations', [
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'threshold_days' => 14,
            'threshold_response_time' => null,
        ]);
    }

    public function test_prevents_duplicate_alert_types()
    {
        $user = User::factory()->create();

        // Create first alert
        AlertConfiguration::create([
            'user_id' => $user->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'enabled' => true,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        // Try to create duplicate
        $response = $this->actingAs($user)->post('/settings/alerts', [
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You already have an alert configured for this type. Use the Configure button to modify it.');
    }
}