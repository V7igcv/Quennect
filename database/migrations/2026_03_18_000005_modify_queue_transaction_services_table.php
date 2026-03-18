<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('queue_transaction_services', function (Blueprint $table) {
            // Update queue_transaction_id to allow rows linked to internal transactions.
            $table->dropForeign(['queue_transaction_id']);
            $table->bigInteger('queue_transaction_id')->nullable()->change();
            $table->foreign('queue_transaction_id')->references('id')->on('queue_transactions')->onDelete('cascade');

            // Add internal transaction reference.
            $table->foreignId('internal_transaction_id')
                ->nullable()
                ->after('queue_transaction_id')
                ->constrained('internal_transactions')
                ->onDelete('cascade');
        });

        // Exactly one transaction reference must be present.
        DB::statement('ALTER TABLE queue_transaction_services ADD CONSTRAINT check_qts_transaction_id CHECK ((queue_transaction_id IS NOT NULL AND internal_transaction_id IS NULL) OR (queue_transaction_id IS NULL AND internal_transaction_id IS NOT NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE queue_transaction_services DROP CONSTRAINT check_qts_transaction_id');

        Schema::table('queue_transaction_services', function (Blueprint $table) {
            $table->dropForeign(['internal_transaction_id']);
            $table->dropColumn('internal_transaction_id');

            $table->dropForeign(['queue_transaction_id']);
            $table->bigInteger('queue_transaction_id')->nullable(false)->change();
            $table->foreign('queue_transaction_id')->references('id')->on('queue_transactions')->onDelete('cascade');
        });
    }
};
