<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Plugin-ready: Additional monitoring capabilities
            $table->json('monitoring_config')->nullable(); // Plugin-configurable monitoring settings
            $table->boolean('ssl_monitoring_enabled')->default(true);
            $table->boolean('uptime_monitoring_enabled')->default(false);
            $table->json('plugin_data')->nullable(); // Extensible data for future plugins

            $table->timestamps();
            $table->softDeletes(); // Support soft deletes for data retention

            $table->unique(['url', 'user_id']);
            $table->index('user_id');
            $table->index('ssl_monitoring_enabled');
            $table->index('uptime_monitoring_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
