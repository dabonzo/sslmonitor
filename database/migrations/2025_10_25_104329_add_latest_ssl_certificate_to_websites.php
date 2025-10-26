<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->json('latest_ssl_certificate')->nullable()->after('uptime_monitoring_enabled');
            $table->timestamp('ssl_certificate_analyzed_at')->nullable()->after('latest_ssl_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn(['latest_ssl_certificate', 'ssl_certificate_analyzed_at']);
        });
    }
};
