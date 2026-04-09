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
            $table->dropColumn(['assistance_provided', 'assistance_provided_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->decimal('assistance_provided', 12, 2)->nullable()->after('serving_time');
            $table->timestamp('assistance_provided_at')->nullable()->after('assistance_provided');
        });
    }
};
