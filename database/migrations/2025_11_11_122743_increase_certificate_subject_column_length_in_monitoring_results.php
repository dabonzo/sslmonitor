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
            // Change certificate_subject from VARCHAR(255) to TEXT
            // to accommodate certificates with many Subject Alternative Names (SANs)
            // Example: Wikipedia has 54 SANs which exceeds VARCHAR(255) limit
            $table->text('certificate_subject')->nullable()->change();
        });
    }

};
