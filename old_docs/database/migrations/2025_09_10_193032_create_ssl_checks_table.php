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
        Schema::create('ssl_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('status'); // valid, expired, expiring_soon, invalid, error
            $table->timestamp('checked_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('issuer')->nullable();
            $table->string('subject')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('signature_algorithm')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->integer('days_until_expiry')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('website_id');
            $table->index('checked_at');
            $table->index('status');
            $table->index(['website_id', 'checked_at']);
            $table->index(['status', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_checks');
    }
};
