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
        Schema::table('service_assistance', function (Blueprint $table) {
            $table->unsignedTinyInteger('indicator')
                ->nullable()
                ->after('assistance_provided')
                ->comment('Optional assistance indicator value: 1 or 2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_assistance', function (Blueprint $table) {
            $table->dropColumn('indicator');
        });
    }
};
