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
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->index(['office_id', 'queue_date'], 'idx_qt_office_date');
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->index(['office_id', 'transaction_date'], 'idx_it_office_date');
        });

        Schema::table('evaluation_responses', function (Blueprint $table) {
            $table->index(['question_id', 'queue_transaction_id'], 'idx_er_question_queue_tx');
            $table->index(['question_id', 'internal_transaction_id'], 'idx_er_question_internal_tx');
        });

        Schema::table('queue_transaction_services', function (Blueprint $table) {
            $table->index(['service_id', 'queue_transaction_id'], 'idx_qts_service_queue_tx');
            $table->index(['service_id', 'internal_transaction_id'], 'idx_qts_service_internal_tx');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['service_type', 'id'], 'idx_services_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('idx_services_type_id');
        });

        Schema::table('queue_transaction_services', function (Blueprint $table) {
            $table->dropIndex('idx_qts_service_internal_tx');
            $table->dropIndex('idx_qts_service_queue_tx');
        });

        Schema::table('evaluation_responses', function (Blueprint $table) {
            $table->dropIndex('idx_er_question_internal_tx');
            $table->dropIndex('idx_er_question_queue_tx');
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_it_office_date');
        });

        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_qt_office_date');
        });
    }
};
