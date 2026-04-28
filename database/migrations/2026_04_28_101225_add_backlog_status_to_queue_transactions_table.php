<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing CHECK constraint
        DB::statement("ALTER TABLE queue_transactions DROP CONSTRAINT IF EXISTS queue_transactions_status_check");

        // Re-add it with BACKLOG included
        DB::statement("ALTER TABLE queue_transactions ADD CONSTRAINT queue_transactions_status_check CHECK (status IN ('WAITING', 'SERVING', 'COMPLETED', 'SKIPPED', 'BACKLOG'))");
    }

    public function down(): void
    {
        // Revert back to original without BACKLOG
        DB::statement("ALTER TABLE queue_transactions DROP CONSTRAINT IF EXISTS queue_transactions_status_check");
        DB::statement("ALTER TABLE queue_transactions ADD CONSTRAINT queue_transactions_status_check CHECK (status IN ('WAITING', 'SERVING', 'COMPLETED', 'SKIPPED'))");
    }
};