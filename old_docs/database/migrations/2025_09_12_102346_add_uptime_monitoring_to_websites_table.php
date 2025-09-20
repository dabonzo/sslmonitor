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
            $table->boolean('uptime_monitoring')->default(false);
            $table->integer('expected_status_code')->default(200);
            $table->text('expected_content')->nullable();
            $table->text('forbidden_content')->nullable();
            $table->integer('max_response_time')->default(30000);
            $table->boolean('follow_redirects')->default(true);
            $table->integer('max_redirects')->default(3);
            $table->string('uptime_status')->default('unknown');
            $table->timestamp('last_uptime_check_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'uptime_monitoring',
                'expected_status_code',
                'expected_content',
                'forbidden_content',
                'max_response_time',
                'follow_redirects',
                'max_redirects',
                'uptime_status',
                'last_uptime_check_at',
            ]);
        });
    }
};
