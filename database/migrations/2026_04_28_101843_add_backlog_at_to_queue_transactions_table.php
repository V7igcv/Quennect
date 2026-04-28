<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->timestamp('backlog_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('queue_transactions', function (Blueprint $table) {
            $table->dropColumn('backlog_at');
        });
    }
};