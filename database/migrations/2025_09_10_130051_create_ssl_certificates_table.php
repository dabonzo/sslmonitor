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
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('issuer');
            $table->timestamp('expires_at');
            $table->string('subject');
            $table->string('serial_number');
            $table->string('signature_algorithm');
            $table->boolean('is_valid')->default(true);
            $table->timestamps();

            $table->index('website_id');
            $table->index('expires_at');
            $table->index(['website_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
