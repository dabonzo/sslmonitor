<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');

            // Core SSL certificate data (from old_docs)
            $table->string('issuer');
            $table->timestamp('expires_at');
            $table->string('subject');
            $table->string('serial_number');
            $table->string('signature_algorithm');
            $table->boolean('is_valid')->default(true);

            // Plugin-ready: Extended certificate data
            $table->json('certificate_chain')->nullable(); // Full certificate chain for agent analysis
            $table->json('security_metrics')->nullable(); // Plugin-extensible security metrics
            $table->string('certificate_hash')->nullable(); // For change detection
            $table->json('plugin_analysis')->nullable(); // Agent-collected certificate analysis

            $table->timestamps();

            $table->index('website_id');
            $table->index('expires_at');
            $table->index(['website_id', 'expires_at']);
            $table->index('certificate_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
