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
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('monitor_id'); // INT to match monitors table
            $table->foreignId('website_id'); // BIGINT

            // Alert Classification
            $table->enum('alert_type', [
                'uptime_down',
                'uptime_recovery',
                'ssl_expiring',
                'ssl_expired',
                'ssl_invalid',
                'performance_degradation',
                'content_validation_failed',
            ]);
            $table->enum('alert_severity', ['info', 'warning', 'urgent', 'critical']);
            $table->string('alert_title');
            $table->text('alert_message')->nullable();

            // Alert Lifecycle Timestamps
            $table->timestamp('first_detected_at')->comment('When alert condition first detected');
            $table->timestamp('last_occurred_at')->nullable()->comment('Most recent occurrence');
            $table->timestamp('acknowledged_at')->nullable()->comment('When user acknowledged');
            $table->timestamp('resolved_at')->nullable()->comment('When alert condition resolved');

            // User Actions
            $table->foreignId('acknowledged_by_user_id')->nullable(); // BIGINT
            $table->text('acknowledgment_note')->nullable()->comment('User note when acknowledging');

            // Alert Context (CRITICAL for debugging)
            $table->json('trigger_value')->nullable()->comment('Value that triggered alert (e.g., {"response_time": 5000})');
            $table->json('threshold_value')->nullable()->comment('Threshold that was exceeded (e.g., {"max_response_time": 2000})');
            $table->unsignedBigInteger('affected_check_result_id')->nullable()->comment('FK to monitoring_results');

            // Notification Tracking
            $table->json('notifications_sent')->nullable()->comment('Array of notifications sent with timestamps');
            $table->string('notification_channels')->nullable()->comment('Comma-separated: email,slack,webhook');
            $table->enum('notification_status', ['pending', 'sent', 'failed', 'acknowledged'])->default('pending');

            // Alert Suppression
            $table->boolean('suppressed')->default(false)->comment('Is alert suppressed during maintenance?');
            $table->timestamp('suppressed_until')->nullable()->comment('Suppression end time');

            // Metadata
            $table->unsignedInteger('occurrence_count')->default(1)->comment('Number of times this alert occurred');
            $table->timestamps();

            // ==================== INDEXES ====================
            $table->index(['monitor_id', 'alert_type', 'first_detected_at'], 'idx_alerts_monitor');
            $table->index(['website_id', 'alert_severity', 'first_detected_at'], 'idx_alerts_website');
            $table->index(['alert_type', 'alert_severity', 'resolved_at'], 'idx_alerts_type_severity');
            $table->index(['notification_status', 'first_detected_at'], 'idx_alerts_status');
            $table->index(['resolved_at', 'first_detected_at'], 'idx_alerts_unresolved');

            // ==================== FOREIGN KEYS ====================
            $table->foreign('monitor_id', 'fk_alerts_monitor')
                ->references('id')->on('monitors')->onDelete('cascade');
            $table->foreign('website_id', 'fk_alerts_website')
                ->references('id')->on('websites')->onDelete('cascade');
            $table->foreign('acknowledged_by_user_id', 'fk_alerts_user')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('affected_check_result_id', 'fk_alerts_check_result')
                ->references('id')->on('monitoring_results')->onDelete('set null');
        });

        // Set table engine and charset
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE monitoring_alerts ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Alert lifecycle tracking with notifications and acknowledgments"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_alerts');
    }
};
