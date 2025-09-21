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
        // Drop redundant SSL tables since we're using Spatie Laravel Uptime Monitor
        // which handles both uptime and SSL certificate monitoring
        Schema::dropIfExists('ssl_checks');
        Schema::dropIfExists('ssl_certificates');
    }
};
