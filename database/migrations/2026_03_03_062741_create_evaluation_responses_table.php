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
        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_transaction_id')->constrained('queue_transactions')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('evaluation_questions')->onDelete('cascade');
            $table->string('answer_value', 50); // For LIKERT: STRONGLY_DISAGREE, DISAGREE, etc.
            $table->unsignedTinyInteger('rating_value')->nullable(); // For LIKERT: 1-5, null for multiple choice
            $table->timestamps();

            $table->unique(['queue_transaction_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_responses');
    }
};
