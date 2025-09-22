<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('website_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('alert_type'); // ssl_expiry, ssl_invalid, uptime_down, response_time, lets_encrypt_renewal
            $table->boolean('enabled')->default(true);
            $table->integer('threshold_days')->nullable(); // Days for expiry alerts
            $table->integer('threshold_response_time')->nullable(); // Milliseconds for response time alerts
            $table->json('notification_channels'); // email, dashboard, slack
            $table->string('alert_level'); // info, warning, urgent, critical
            $table->text('custom_message')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'enabled']);
            $table->index(['website_id', 'alert_type']);
            $table->index(['alert_type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_configurations');
    }
};