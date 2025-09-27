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
        Schema::table('websites', function (Blueprint $table) {
            // Composite index for user + team queries (most common query pattern)
            $table->index(['user_id', 'team_id'], 'websites_user_team_index');

            // Index for monitoring status queries
            $table->index(['ssl_monitoring_enabled', 'uptime_monitoring_enabled'], 'websites_monitoring_status_index');

            // Index for updated_at ordering
            $table->index('updated_at', 'websites_updated_at_index');
        });

        Schema::table('monitors', function (Blueprint $table) {
            // Composite index for certificate queries
            $table->index(['certificate_check_enabled', 'certificate_status'], 'monitors_cert_status_index');

            // Composite index for uptime queries
            $table->index(['uptime_status', 'uptime_last_check_date'], 'monitors_uptime_status_index');

            // Index for expiration date queries
            $table->index('certificate_expiration_date', 'monitors_cert_expiration_index');

            // Index for response time queries
            $table->index('uptime_check_response_time_in_ms', 'monitors_response_time_index');
        });

        Schema::table('team_members', function (Blueprint $table) {
            // Composite index for user role queries
            $table->index(['user_id', 'role'], 'team_members_user_role_index');

            // Index for team + role queries
            $table->index(['team_id', 'role'], 'team_members_team_role_index');
        });

        Schema::table('alert_configurations', function (Blueprint $table) {
            // Composite index for enabled alerts
            $table->index(['user_id', 'enabled', 'alert_type'], 'alert_configs_user_enabled_type_index');

            // Index for website-specific alerts
            $table->index(['website_id', 'enabled'], 'alert_configs_website_enabled_index');

            // Index for team alerts
            $table->index(['team_id', 'enabled'], 'alert_configs_team_enabled_index');
        });

        Schema::table('sessions', function (Blueprint $table) {
            // Composite index for session cleanup
            $table->index(['user_id', 'last_activity'], 'sessions_user_activity_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropIndex('websites_user_team_index');
            $table->dropIndex('websites_monitoring_status_index');
            $table->dropIndex('websites_updated_at_index');
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex('monitors_cert_status_index');
            $table->dropIndex('monitors_uptime_status_index');
            $table->dropIndex('monitors_cert_expiration_index');
            $table->dropIndex('monitors_response_time_index');
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->dropIndex('team_members_user_role_index');
            $table->dropIndex('team_members_team_role_index');
        });

        Schema::table('alert_configurations', function (Blueprint $table) {
            $table->dropIndex('alert_configs_user_enabled_type_index');
            $table->dropIndex('alert_configs_website_enabled_index');
            $table->dropIndex('alert_configs_team_enabled_index');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('sessions_user_activity_index');
        });
    }
};
