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
            $table->decimal('assistance_provided', 12, 2)
                ->nullable()
                ->after('serving_time')
                ->comment('Monetary assistance provided to the client');
            $table->timestamp('assistance_provided_at')
                ->nullable()
                ->after('assistance_provided')
                ->comment('Date and time when the assistance was provided');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->dropColumn(['assistance_provided', 'assistance_provided_at']);
        });
    }
};
