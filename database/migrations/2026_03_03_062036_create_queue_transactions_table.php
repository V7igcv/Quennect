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
        Schema::create('queue_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->date('queue_date')->default(DB::raw('CURRENT_DATE'));
            $table->integer('queue_number');
            $table->string('queue_prefix', 20);
            $table->boolean('is_priority')->default(false);
            $table->string('full_queue_number', 30);
            $table->string('client_name', 150);
            $table->string('contact_number', 20);
            $table->foreignId('barangay_id')->nullable()->constrained('barangays');
            $table->enum('status', ['WAITING', 'SERVING', 'COMPLETED', 'SKIPPED'])->default('WAITING');
            $table->foreignId('counter_id')->nullable()->constrained('counters')->onDelete('set null');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();
            
            // Ensure daily reset per office
            $table->unique(['office_id', 'queue_date', 'queue_number'], 'unique_daily_queue');
            
            // Indexes for faster queries
            $table->index(['office_id', 'status', 'queue_date']);
            $table->index('full_queue_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_transactions');
    }
};
