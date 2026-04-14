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
        Schema::table('service_assistance', function (Blueprint $table) {
            // Add assistance_type_id column - nullable to support both traditional and categorized assistance
            $table->foreignId('assistance_type_id')
                ->nullable()
                ->after('queue_transaction_service_id')
                ->constrained('assistance_types')
                ->onDelete('set null')
                ->comment('Foreign key to assistance_types table. NULL = traditional service, NOT NULL = categorized service (e.g., AICS)');
            
            // Enforce unique constraint on queue_transaction_service_id
            // Each service in a transaction can only have one assistance record
            $table->unique('queue_transaction_service_id');
            
            // Index for fast lookups by assistance type
            $table->index('assistance_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_assistance', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['assistance_type_id']);
            $table->dropColumn('assistance_type_id');
            
            // Drop the unique constraint and index
            $table->dropUnique(['queue_transaction_service_id']);
            $table->dropIndex(['assistance_type_id']);
        });
    }
};
