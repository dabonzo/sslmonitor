<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssl_checks', function (Blueprint $table) {
            $table->integer('response_time')->nullable()->after('error_message')->comment('Response time in milliseconds');
            $table->index('response_time');
        });
    }

    public function down(): void
    {
        Schema::table('ssl_checks', function (Blueprint $table) {
            $table->dropColumn('response_time');
        });
    }
};