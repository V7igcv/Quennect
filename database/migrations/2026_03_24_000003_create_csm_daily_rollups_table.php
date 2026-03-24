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
        Schema::create('csm_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->date('rollup_date');
            $table->enum('service_type', ['external', 'internal']);
            $table->string('question_code', 20);
            $table->unsignedTinyInteger('answer_option');
            $table->unsignedInteger('response_count')->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->timestamps();

            $table->unique(
                ['office_id', 'rollup_date', 'service_type', 'question_code', 'answer_option'],
                'uniq_csm_daily_rollup_key'
            );

            $table->index(['rollup_date', 'service_type'], 'idx_csm_rollup_date_service');
            $table->index(['office_id', 'rollup_date'], 'idx_csm_rollup_office_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csm_daily_rollups');
    }
};
