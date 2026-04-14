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
        Schema::create('assistance_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');
            $table->string('assistance_name', 255)
                ->comment('Name of the assistance type (e.g., Medical Assistance, Burial Assistance)');
            $table->timestamps();

            // Composite unique constraint: same service cannot have duplicate assistance types
            $table->unique(['service_id', 'assistance_name']);
            
            // Index for fast lookup of assistance types by service
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_types');
    }
};
