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
        Schema::create('queue_priority_sectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_transaction_id')->constrained('queue_transactions')->onDelete('cascade');
            $table->foreignId('priority_sector_id')->constrained('priority_sectors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_priority_sectors');
    }
};
