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
            $table->index('queue_transaction_service_id', 'idx_sa_qts_id');
            $table->index('assistance_type_id', 'idx_sa_assistance_type_id');
        });

        Schema::table('queue_transaction_services', function (Blueprint $table) {
            $table->index('queue_transaction_id', 'idx_qts_queue_tx');
        });

        Schema::table('queue_transactions', function (Blueprint $table) {
            // Composite index used frequently in analytics filters
            $table->index(['office_id', 'status', 'completed_at'], 'idx_qt_office_status_completed');
            $table->index('barangay_id', 'idx_qt_barangay_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_qt_barangay_id');
            $table->dropIndex('idx_qt_office_status_completed');
        });

        Schema::table('queue_transaction_services', function (Blueprint $table) {
            $table->dropIndex('idx_qts_queue_tx');
        });

        Schema::table('service_assistance', function (Blueprint $table) {
            $table->dropIndex('idx_sa_assistance_type_id');
            $table->dropIndex('idx_sa_qts_id');
        });
    }
};
