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
        Schema::create('downtime_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('max_response_time_ms')->nullable();
            $table->string('incident_type', 50); // 'timeout', 'http_error', 'content_mismatch'
            $table->text('error_details')->nullable();
            $table->boolean('resolved_automatically')->default(false);
            $table->timestamps();

            $table->index(['website_id', 'started_at']);
            $table->index('incident_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtime_incidents');
    }
};
