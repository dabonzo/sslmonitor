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
        // Run the TestUserSeeder which sets up our real test data
        $this->call(TestUserSeeder::class);

        $this->command->info('Database seeded successfully with real test data!');
    }
}