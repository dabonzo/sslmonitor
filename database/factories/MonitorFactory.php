<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'url' => 'https://example'.$counter.'.com',
            'uptime_check_enabled' => true,
            'certificate_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_status' => 'valid',
            'uptime_check_interval_in_minutes' => 5,
            'look_for_string' => '',
            'content_expected_strings' => null,
            'content_forbidden_strings' => null,
            'content_regex_patterns' => null,
            'javascript_enabled' => false,
            'javascript_wait_seconds' => 5,
            'content_validation_failure_reason' => null,
            'uptime_check_response_time_in_ms' => null,
        ];
    }
}
