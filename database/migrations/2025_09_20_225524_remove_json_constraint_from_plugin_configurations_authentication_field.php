<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove JSON validation constraint from authentication field since it's encrypted
        DB::statement('ALTER TABLE plugin_configurations MODIFY COLUMN authentication longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add JSON validation constraint
        DB::statement('ALTER TABLE plugin_configurations MODIFY COLUMN authentication longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`authentication`))');
    }
};
