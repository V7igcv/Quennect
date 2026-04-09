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
        Schema::create('service_assistance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_transaction_service_id')
                ->constrained('queue_transaction_services')
                ->onDelete('cascade');
            $table->decimal('assistance_provided', 12, 2)
                ->comment('Monetary assistance provided for this service');
            $table->timestamp('assistance_provided_at')
                ->nullable()
                ->comment('Date and time when the assistance was provided');
            $table->timestamps();

            $table->index('queue_transaction_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_assistance');
    }
};
