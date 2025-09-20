<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Basic settings (from old_docs)
            $table->string('mailer')->default('smtp');
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('encryption')->nullable(); // tls, ssl, or null

            // Authentication
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Will be encrypted

            // From settings
            $table->string('from_address');
            $table->string('from_name')->default('SSL Monitor');

            // Connection settings
            $table->integer('timeout')->default(30);
            $table->boolean('verify_peer')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('test_passed')->default(false);
            $table->text('test_error')->nullable();

            // Plugin-ready: Notification channels and preferences
            $table->json('notification_channels')->nullable(); // SMS, Slack, Teams, webhook URLs
            $table->json('notification_rules')->nullable(); // Plugin-configurable notification logic
            $table->json('template_config')->nullable(); // Custom email templates

            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
