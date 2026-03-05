<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get role IDs
        $superadminRole = DB::table('roles')->where('name', 'SUPERADMIN')->first();
        $frontdeskRole = DB::table('roles')->where('name', 'OFFICE FRONTDESK')->first();

        if (!$superadminRole || !$frontdeskRole) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        // Create Superadmin
        DB::table('users')->updateOrInsert(
            ['username' => 'superadmin'],
            [
                'password_hash' => Hash::make('superadmin'),
                'office_id' => null,
                'role_id' => $superadminRole->id,
                'last_login_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create Front Desk users for each active office
        $offices = DB::table('offices')->where('is_active', true)->get();
        
        foreach ($offices as $office) {
            DB::table('users')->updateOrInsert(
                ['username' => 'frontdesk_' . strtolower($office->office_acronym)],
                [
                    'password_hash' => Hash::make('12345678'),
                    'office_id' => $office->id,
                    'role_id' => $frontdeskRole->id,
                    'last_login_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Users seeded successfully!');
        $this->command->warn('Default passwords: superadmin/SuperAdmin@2024, frontdesk/password123');
    }
}
