<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove JSON validation constraint from authentication field since it's encrypted
        // Only apply MariaDB-specific changes on MariaDB
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE plugin_configurations MODIFY COLUMN authentication longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL');
        }
        // SQLite doesn't need this change as it doesn't have strict JSON constraints
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add JSON validation constraint
        // Only apply MariaDB-specific changes on MariaDB
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE plugin_configurations MODIFY COLUMN authentication longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`authentication`))');
        }
        // SQLite doesn't need this change as it doesn't have strict JSON constraints
    }
};
