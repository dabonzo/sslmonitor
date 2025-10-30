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
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->foreignId('website_id')->nullable()->change();
        });

        // Also fix monitoring_alerts table
        Schema::table('monitoring_alerts', function (Blueprint $table) {
            $table->foreignId('website_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_results', function (Blueprint $table) {
            $table->foreignId('website_id')->nullable(false)->change();
        });

        Schema::table('monitoring_alerts', function (Blueprint $table) {
            $table->foreignId('website_id')->nullable(false)->change();
        });
    }
};
