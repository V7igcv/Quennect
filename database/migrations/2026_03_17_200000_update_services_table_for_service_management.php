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
        Schema::table('services', function (Blueprint $table) {
            $table->enum('service_type', ['External', 'Internal'])
                ->default('External')
                ->after('service_description');

            $table->enum('classification', ['Simple', 'Complex', 'Highly_Technical'])
                ->default('Simple')
                ->after('service_type');

            $table->boolean('is_free')
                ->default(false)
                ->after('classification');

            $table->enum('status', ['active', 'inactive'])
                ->default('active')
                ->after('is_free');

            $table->unsignedBigInteger('used_count')
                ->default(0)
                ->after('status');

            $table->boolean('is_locked')
                ->default(false)
                ->after('used_count');

            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'service_type',
                'classification',
                'is_free',
                'status',
                'used_count',
                'is_locked',
            ]);
        });
    }
};
