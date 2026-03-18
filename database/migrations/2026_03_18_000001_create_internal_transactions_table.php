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
        Schema::create('internal_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->date('transaction_date')->default(DB::raw('CURRENT_DATE'));
            $table->string('full_name', 150);
            $table->string('contact_number', 20);
            $table->string('requirement_link', 255)->nullable();
            $table->enum('status', ['PENDING', 'ON-PROCESS', 'COMPLETED', 'DENIED', 'OVERDUE'])->default('PENDING');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->decimal('average_satisfaction_rating', 3, 2)->nullable();
            $table->timestamp('expected_completion_date');
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['office_id', 'status', 'transaction_date']);
            $table->index('full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transactions');
    }
};
