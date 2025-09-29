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
        Schema::table('monitors', function (Blueprint $table) {
            // Enhanced content validation fields
            $table->text('content_expected_strings')->nullable()->after('look_for_string')
                ->comment('JSON array of strings that must be present in response');
            $table->text('content_forbidden_strings')->nullable()->after('content_expected_strings')
                ->comment('JSON array of strings that must NOT be present in response');
            $table->text('content_regex_patterns')->nullable()->after('content_forbidden_strings')
                ->comment('JSON array of regex patterns to match against response');
            $table->boolean('javascript_enabled')->default(false)->after('content_regex_patterns')
                ->comment('Whether to render JavaScript before content validation');
            $table->integer('javascript_wait_seconds')->default(5)->after('javascript_enabled')
                ->comment('Seconds to wait for JavaScript rendering');
            $table->text('content_validation_failure_reason')->nullable()->after('javascript_wait_seconds')
                ->comment('Detailed reason for content validation failure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'content_expected_strings',
                'content_forbidden_strings',
                'content_regex_patterns',
                'javascript_enabled',
                'javascript_wait_seconds',
                'content_validation_failure_reason'
            ]);
        });
    }
};
