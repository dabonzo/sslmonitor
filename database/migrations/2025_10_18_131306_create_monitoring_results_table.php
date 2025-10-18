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
        Schema::create('monitoring_results', function (Blueprint $table) {
            // Primary Key & UUID
            $table->id();
            $table->char('uuid', 36)->unique()->comment('UUID for external API references');

            // Relationships (CRITICAL: Both monitor_id AND website_id for flexible queries)
            // Note: monitor_id uses unsignedInteger because monitors table uses INT (Spatie package)
            $table->unsignedInteger('monitor_id')->comment('FK to monitors table');
            $table->foreignId('website_id')->comment('FK to websites table (enables direct queries)');

            // Check Classification
            $table->enum('check_type', ['uptime', 'ssl_certificate', 'both'])->default('both');
            $table->enum('trigger_type', ['scheduled', 'manual_immediate', 'manual_bulk', 'system']);
            $table->foreignId('triggered_by_user_id')->nullable()->comment('User who triggered manual check');

            // Check Timing (MILLISECOND PRECISION for accurate performance analysis)
            $table->timestamp('started_at', 3)->comment('High-precision start time');
            $table->timestamp('completed_at', 3)->nullable()->comment('High-precision completion time');
            $table->unsignedInteger('duration_ms')->nullable()->comment('Total check duration in milliseconds');

            // Overall Check Status
            $table->enum('status', ['success', 'failed', 'timeout', 'error']);
            $table->text('error_message')->nullable()->comment('Detailed error if failed');

            // ==================== UPTIME-SPECIFIC DATA ====================
            $table->enum('uptime_status', ['up', 'down'])->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable()->comment('HTTP status code (200, 404, 500, etc.)');
            $table->unsignedInteger('response_time_ms')->nullable()->comment('Response time in milliseconds');
            $table->unsignedInteger('response_body_size_bytes')->nullable()->comment('Response body size');
            $table->unsignedTinyInteger('redirect_count')->nullable()->default(0)->comment('Number of redirects followed');
            $table->string('final_url', 2048)->nullable()->comment('Final URL after redirects');

            // ==================== SSL CERTIFICATE-SPECIFIC DATA ====================
            $table->enum('ssl_status', ['valid', 'invalid', 'expired', 'expires_soon', 'self_signed'])->nullable();
            $table->string('certificate_issuer')->nullable()->comment('Certificate issuer (e.g., Let\'s Encrypt)');
            $table->string('certificate_subject')->nullable()->comment('Certificate subject');
            $table->timestamp('certificate_expiration_date')->nullable()->comment('Certificate expiration');
            $table->timestamp('certificate_valid_from_date')->nullable()->comment('Certificate valid from');
            $table->integer('days_until_expiration')->nullable()->comment('Days until certificate expires');
            $table->json('certificate_chain')->nullable()->comment('Full certificate chain data');

            // ==================== CONTENT VALIDATION DATA ====================
            $table->boolean('content_validation_enabled')->default(false);
            $table->enum('content_validation_status', ['passed', 'failed', 'not_checked'])->nullable();
            $table->json('expected_strings_found')->nullable()->comment('Array of expected strings that were found');
            $table->json('forbidden_strings_found')->nullable()->comment('Array of forbidden strings that were found');
            $table->json('regex_matches')->nullable()->comment('Regex pattern match results');
            $table->boolean('javascript_rendered')->default(false)->comment('Was JavaScript rendering used?');
            $table->unsignedTinyInteger('javascript_wait_seconds')->nullable()->comment('Seconds waited for JS rendering');
            $table->string('content_hash', 64)->nullable()->comment('SHA-256 hash of content for change detection');

            // ==================== TECHNICAL DETAILS ====================
            $table->string('check_method', 20)->default('GET')->comment('HTTP method used');
            $table->string('user_agent')->nullable()->comment('User agent string used');
            $table->json('request_headers')->nullable()->comment('Request headers sent');
            $table->json('response_headers')->nullable()->comment('Response headers received');
            $table->string('ip_address', 45)->nullable()->comment('Server IP address (IPv4/IPv6)');
            $table->string('server_software')->nullable()->comment('Server software identification');

            // ==================== MONITORING CONTEXT ====================
            $table->json('monitor_config')->nullable()->comment('Monitor configuration at time of check (for debugging)');
            $table->unsignedSmallInteger('check_interval_minutes')->nullable()->comment('Configured check interval');

            // Timestamps
            $table->timestamps();

            // ==================== INDEXES FOR PERFORMANCE ====================
            // CRITICAL: Composite index for dashboard queries (90%+ improvement)
            $table->index(['monitor_id', 'website_id', 'started_at'], 'idx_monitor_website_time');

            // Individual relationship indexes
            $table->index('monitor_id', 'idx_monitor_results_monitor_id');
            $table->index('website_id', 'idx_monitor_results_website_id');

            // Query filters
            $table->index(['check_type', 'status', 'started_at'], 'idx_check_type_status');
            $table->index(['trigger_type', 'started_at'], 'idx_trigger_type');
            $table->index(['status', 'started_at'], 'idx_status_time');

            // SSL-specific queries
            $table->index(['certificate_expiration_date', 'ssl_status'], 'idx_ssl_expiration');

            // Time-based queries (for data retention)
            $table->index('started_at', 'idx_started_at');

            // ==================== FOREIGN KEY CONSTRAINTS ====================
            $table->foreign('monitor_id', 'fk_monitoring_results_monitor')
                ->references('id')->on('monitors')->onDelete('cascade');
            $table->foreign('website_id', 'fk_monitoring_results_website')
                ->references('id')->on('websites')->onDelete('cascade');
            $table->foreign('triggered_by_user_id', 'fk_monitoring_results_user')
                ->references('id')->on('users')->onDelete('set null');
        });

        // Set table engine and charset (MySQL/MariaDB only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE monitoring_results ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Complete historical record of all monitoring checks (SSL + Uptime)"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_results');
    }
};
