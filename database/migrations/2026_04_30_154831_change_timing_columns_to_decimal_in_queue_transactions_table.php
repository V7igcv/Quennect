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
            $table->decimal('waiting_time', 10, 2)->nullable()->change();
            $table->decimal('serving_time', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->integer('waiting_time')->nullable()->change();
            $table->integer('serving_time')->nullable()->change();
        });
    }
};
