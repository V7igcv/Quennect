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
        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('from_office_id')->nullable();
            $table->unsignedBigInteger('to_office_id')->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->json('service_ids')->nullable();
            $table->text('request_notes')->nullable();
            $table->text('denial_reason')->nullable();
            $table->text('completion_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'from_office_id',
                'to_office_id',
                'transaction_id',
                'accepted_at',
                'overdue_at',
                'service_ids',
                'request_notes',
                'denial_reason',
                'completion_notes',
                'created_by',
                'processed_by'
            ]);
        });
    }
};
