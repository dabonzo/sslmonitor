<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create global alert templates for all existing users
        $this->call([
            GlobalAlertTemplatesSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Global alert templates have been created for all existing users.');
        $this->command->info('');
        $this->command->info('For development with test data, run:');
        $this->command->info('  php artisan db:seed --class=TestUserSeeder');
    }
}