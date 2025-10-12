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
        Schema::create('debug_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('module_type'); // 'ssl_expiry', 'monitor_status', etc.
            $table->morphs('targetable'); // website, monitor, etc.
            $table->json('override_data'); // Flexible override configuration
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable(); // Auto-expire overrides
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'module_type', 'is_active']);
            $table->index(['module_type', 'targetable_type', 'targetable_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debug_overrides');
    }
};