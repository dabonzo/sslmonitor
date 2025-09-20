<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Plugin identification
            $table->string('plugin_type'); // 'agent', 'webhook', 'external_service'
            $table->string('plugin_name'); // 'system_metrics_agent', 'disk_space_monitor'
            $table->string('plugin_version')->default('1.0.0');

            // Configuration
            $table->json('configuration'); // Plugin-specific configuration
            $table->json('authentication')->nullable(); // API keys, tokens, certificates
            $table->json('endpoints')->nullable(); // Agent communication endpoints

            // Status and health
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_contacted_at')->nullable();
            $table->string('status')->default('pending'); // pending, active, error, disabled
            $table->text('status_message')->nullable();

            // Data collection settings
            $table->json('collection_schedule')->nullable(); // Cron expressions for data collection
            $table->json('data_retention')->nullable(); // How long to keep collected data
            $table->json('alert_thresholds')->nullable(); // Plugin-specific alert rules

            // Metadata
            $table->string('description')->nullable();
            $table->json('capabilities')->nullable(); // What this plugin can monitor
            $table->json('metadata')->nullable(); // Additional plugin metadata

            $table->timestamps();

            $table->index('user_id');
            $table->index(['plugin_type', 'plugin_name']);
            $table->index('is_enabled');
            $table->index('status');
            $table->unique(['user_id', 'plugin_type', 'plugin_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_configurations');
    }
};
