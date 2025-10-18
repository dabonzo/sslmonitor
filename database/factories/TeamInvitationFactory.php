<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->email(),
            'role' => fake()->randomElement(['ADMIN', 'MANAGER', 'VIEWER']),
            'token' => \Illuminate\Support\Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by_user_id' => User::factory(),
        ];
    }
}
