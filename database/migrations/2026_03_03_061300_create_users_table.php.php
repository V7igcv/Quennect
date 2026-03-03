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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password_hash');
            $table->foreignId('office_id')->nullable()->constrained('offices')->onDelete('set null');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict'); // Add role_id
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['office_id', 'role_id']);
        });

        // Create unique partial index for one frontdesk per office
        // DB::statement('
        //     CREATE UNIQUE INDEX one_frontdesk_per_office 
        //     ON users (office_id) 
        //     WHERE role_id = (SELECT id FROM roles WHERE name = \'FRONTDESK\' LIMIT 1)
        // ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
