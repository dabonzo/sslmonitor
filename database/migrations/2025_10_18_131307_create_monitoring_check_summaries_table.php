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
        Schema::create('monitoring_check_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('monitor_id'); // INT to match monitors table
            $table->foreignId('website_id'); // BIGINT

            // Summary Period
            $table->enum('summary_period', ['hourly', 'daily', 'weekly', 'monthly']);
            $table->timestamp('period_start');
            $table->timestamp('period_end');

            // ==================== UPTIME SUMMARY STATISTICS ====================
            $table->unsignedInteger('total_uptime_checks')->default(0);
            $table->unsignedInteger('successful_uptime_checks')->default(0);
            $table->unsignedInteger('failed_uptime_checks')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(0.00)->comment('Uptime % (0.00 to 100.00)');

            // Response Time Metrics
            $table->unsignedInteger('average_response_time_ms')->default(0);
            $table->unsignedInteger('min_response_time_ms')->default(0);
            $table->unsignedInteger('max_response_time_ms')->default(0);
            $table->unsignedInteger('p95_response_time_ms')->default(0)->comment('95th percentile');
            $table->unsignedInteger('p99_response_time_ms')->default(0)->comment('99th percentile');

            // ==================== SSL SUMMARY STATISTICS ====================
            $table->unsignedInteger('total_ssl_checks')->default(0);
            $table->unsignedInteger('successful_ssl_checks')->default(0);
            $table->unsignedInteger('failed_ssl_checks')->default(0);
            $table->unsignedInteger('certificates_expiring')->default(0)->comment('Certificates expiring in < 30 days');
            $table->unsignedInteger('certificates_expired')->default(0);

            // ==================== PERFORMANCE METRICS ====================
            $table->unsignedInteger('total_checks')->default(0)->comment('Total checks (SSL + uptime)');
            $table->unsignedBigInteger('total_check_duration_ms')->default(0);
            $table->unsignedInteger('average_check_duration_ms')->default(0);

            // ==================== CONTENT VALIDATION METRICS ====================
            $table->unsignedInteger('total_content_validations')->default(0);
            $table->unsignedInteger('successful_content_validations')->default(0);
            $table->unsignedInteger('failed_content_validations')->default(0);

            // Timestamps
            $table->timestamps();

            // ==================== INDEXES ====================
            // Prevent duplicate summaries for same period
            $table->unique(['monitor_id', 'website_id', 'summary_period', 'period_start'], 'unique_summary');

            // Time-based queries
            $table->index(['summary_period', 'period_start'], 'idx_summary_period');
            $table->index(['monitor_id', 'summary_period', 'period_start'], 'idx_monitor_period');
            $table->index(['website_id', 'summary_period', 'period_start'], 'idx_website_period');

            // ==================== FOREIGN KEYS ====================
            $table->foreign('monitor_id', 'fk_summaries_monitor')
                ->references('id')->on('monitors')->onDelete('cascade');
            $table->foreign('website_id', 'fk_summaries_website')
                ->references('id')->on('websites')->onDelete('cascade');
        });

        // Set table engine and charset
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE monitoring_check_summaries ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Pre-calculated summary statistics for fast dashboard queries"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_check_summaries');
    }
};
