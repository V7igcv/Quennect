<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluation_responses', function (Blueprint $table) {
            // Drop the existing foreign key constraint on queue_transaction_id
            $table->dropForeign(['queue_transaction_id']);
            
            // Make queue_transaction_id nullable
            $table->bigInteger('queue_transaction_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('queue_transaction_id')->references('id')->on('queue_transactions')->onDelete('cascade');
            
            // Add new foreign keys
            $table->foreignId('internal_transaction_id')->nullable()->after('queue_transaction_id')->constrained('internal_transactions')->onDelete('cascade');
            $table->foreignId('evaluation_session_id')->nullable()->after('internal_transaction_id')->constrained('evaluation_sessions')->onDelete('cascade');
        });

        // Add check constraint: Either queue_transaction_id or internal_transaction_id must be set, but not both
        DB::statement('ALTER TABLE evaluation_responses ADD CONSTRAINT check_eval_response_transaction_id CHECK ((queue_transaction_id IS NOT NULL AND internal_transaction_id IS NULL) OR (queue_transaction_id IS NULL AND internal_transaction_id IS NOT NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE evaluation_responses DROP CONSTRAINT check_eval_response_transaction_id');
        
        Schema::table('evaluation_responses', function (Blueprint $table) {
            $table->dropForeign(['internal_transaction_id']);
            $table->dropForeign(['evaluation_session_id']);
            $table->dropColumn(['internal_transaction_id', 'evaluation_session_id']);
            
            // Revert queue_transaction_id modifications
            $table->dropForeign(['queue_transaction_id']);
            $table->bigInteger('queue_transaction_id')->nullable(false)->change();
            $table->foreign('queue_transaction_id')->references('id')->on('queue_transactions')->onDelete('cascade');
        });
    }
};
