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
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('uptime_alerts')->default(true)->after('error_alerts');
            $table->boolean('downtime_recovery_alerts')->default(true)->after('uptime_alerts');
            $table->boolean('slow_response_alerts')->default(true)->after('downtime_recovery_alerts');
            $table->boolean('content_mismatch_alerts')->default(true)->after('slow_response_alerts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'uptime_alerts',
                'downtime_recovery_alerts',
                'slow_response_alerts',
                'content_mismatch_alerts',
            ]);
        });
    }
};
