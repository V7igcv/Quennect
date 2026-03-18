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
        Schema::create('evaluation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_transaction_id')->nullable()->constrained('queue_transactions')->onDelete('cascade');
            $table->foreignId('internal_transaction_id')->nullable()->constrained('internal_transactions')->onDelete('cascade');
            $table->enum('client_type', ['Citizen', 'Business', 'Government'])->nullable();
            $table->enum('sex', ['Male', 'Female'])->nullable();
            $table->integer('age')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index('queue_transaction_id');
            $table->index('internal_transaction_id');
        });

        // Add check constraint: Either queue_transaction_id or internal_transaction_id must be set, but not both
        DB::statement('ALTER TABLE evaluation_sessions ADD CONSTRAINT check_one_transaction_id CHECK ((queue_transaction_id IS NOT NULL AND internal_transaction_id IS NULL) OR (queue_transaction_id IS NULL AND internal_transaction_id IS NOT NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_sessions');
    }
};
