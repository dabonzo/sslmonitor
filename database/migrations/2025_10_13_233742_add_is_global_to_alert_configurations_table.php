<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alert_configurations', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('custom_message');
            $table->index(['is_global', 'alert_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert_configurations', function (Blueprint $table) {
            $table->dropIndex(['is_global', 'alert_type']);
            $table->dropColumn('is_global');
        });
    }
};
