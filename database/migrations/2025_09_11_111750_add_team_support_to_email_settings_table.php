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
        Schema::table('email_settings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('notification_emails')->nullable(); // Multiple recipient emails
            $table->json('notification_phones')->nullable(); // Multiple recipient phones (future SMS)

            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn(['team_id', 'notification_emails', 'notification_phones']);
        });
    }
};
