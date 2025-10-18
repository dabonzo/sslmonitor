<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('monitor_id')->nullable()->comment('Related monitor (if applicable)'); // INT
            $table->foreignId('website_id')->nullable()->comment('Related website (if applicable)'); // BIGINT
            $table->foreignId('user_id')->nullable()->comment('User who triggered event (if applicable)'); // BIGINT

            // Event Classification
            $table->enum('event_type', [
                'monitor_created',
                'monitor_updated',
                'monitor_deleted',
                'monitor_enabled',
                'monitor_disabled',
                'ssl_status_changed',
                'ssl_certificate_renewed',
                'ssl_issuer_changed',
                'uptime_status_changed',
                'performance_degraded',
                'performance_improved',
                'content_validation_failed',
                'content_validation_passed',
                'check_interval_changed',
                'alert_triggered',
                'alert_acknowledged',
                'alert_resolved',
                'user_action',
                'system_action',
            ]);
            $table->string('event_name')->comment('Human-readable event name');
            $table->text('description')->nullable()->comment('Detailed event description');

            // Change Tracking
            $table->json('old_values')->nullable()->comment('Previous values (for updates)');
            $table->json('new_values')->nullable()->comment('New values (for updates)');
            $table->json('event_data')->nullable()->comment('Additional event context');

            // Request Context
            $table->string('ip_address', 45)->nullable()->comment('User/system IP address');
            $table->text('user_agent')->nullable()->comment('User agent string');
            $table->enum('source', ['system', 'user', 'api', 'webhook', 'cli'])->default('system');

            // Timestamp (NO updated_at column)
            $table->timestamp('created_at')->useCurrent();

            // ==================== INDEXES ====================
            $table->index(['event_type', 'created_at'], 'idx_events_type');
            $table->index(['monitor_id', 'event_type', 'created_at'], 'idx_events_monitor');
            $table->index(['website_id', 'event_type', 'created_at'], 'idx_events_website');
            $table->index(['user_id', 'event_type', 'created_at'], 'idx_events_user');
            $table->index('created_at', 'idx_events_created');

            // ==================== FOREIGN KEYS ====================
            $table->foreign('monitor_id', 'fk_events_monitor')
                ->references('id')->on('monitors')->onDelete('cascade');
            $table->foreign('website_id', 'fk_events_website')
                ->references('id')->on('websites')->onDelete('cascade');
            $table->foreign('user_id', 'fk_events_user')
                ->references('id')->on('users')->onDelete('set null');
        });

        // Set table engine and charset
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE monitoring_events ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="System and user event audit trail for compliance and debugging"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_events');
    }
};
