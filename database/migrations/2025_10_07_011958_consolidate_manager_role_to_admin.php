<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert all MANAGER roles to ADMIN
        DB::table('team_members')
            ->where('role', 'MANAGER')
            ->update(['role' => 'ADMIN']);
    }

    public function down(): void
    {
        // Note: We cannot reliably reverse this migration as we don't know
        // which ADMIN roles were originally MANAGER roles
        // This is a one-way consolidation
    }
};
