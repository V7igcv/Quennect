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
        $cityMayorRole = DB::table('roles')->where('name', 'CITY MAYOR')->first();
        $cswdoFocalRole = DB::table('roles')->where('name', 'CSWDO FOCAL')->first();
        $hrmoFocalRole = DB::table('roles')->where('name', 'HRMO FOCAL')->first();

        // Check if all roles exist
        if (!$superadminRole || !$frontdeskRole || !$cityMayorRole || !$cswdoFocalRole || !$hrmoFocalRole) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        // 1. SUPERADMIN
        DB::table('users')->updateOrInsert(
            ['username' => 'superadmin'],
            [
                'password_hash' => Hash::make('superadmin'),
                'office_id' => null,
                'role_id' => $superadminRole->id,
                'last_login_at' => null,
                'updated_at' => now(),
            ]
        );

        // 2. CITY MAYOR
        DB::table('users')->updateOrInsert(
            ['username' => 'city_mayor'],
            [
                'password_hash' => Hash::make('city_mayor'),
                'office_id' => null,
                'role_id' => $cityMayorRole->id,
                'last_login_at' => null,
                'updated_at' => now(),
            ]
        );

        // 3. CSWDO FOCAL
        DB::table('users')->updateOrInsert(
            ['username' => 'focal_cswdo'],
            [
                'password_hash' => Hash::make('focal_cswdo'),
                'office_id' => null,
                'role_id' => $cswdoFocalRole->id,
                'last_login_at' => null,
                'updated_at' => now(),
            ]
        );

        // 4. HRMO FOCAL
        DB::table('users')->updateOrInsert(
            ['username' => 'focal_hrmo'],
            [
                'password_hash' => Hash::make('focal_hrmo'),
                'office_id' => null,
                'role_id' => $hrmoFocalRole->id,
                'last_login_at' => null,
                'updated_at' => now(),
            ]
        );

        // 5. FRONT DESK users for each active office
        $offices = DB::table('offices')->where('is_active', true)->get();
        
        foreach ($offices as $office) {
            DB::table('users')->updateOrInsert(
                ['username' => 'frontdesk_' . strtolower($office->office_acronym)],
                [
                    'password_hash' => Hash::make('12345678'),
                    'office_id' => $office->id,
                    'role_id' => $frontdeskRole->id,
                    'last_login_at' => null,
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Users seeded successfully!');
        $this->command->warn('Default passwords:');
        $this->command->warn('  - superadmin: superadmin');
        $this->command->warn('  - city_mayor: city_mayor');
        $this->command->warn('  - focal_cswdo: focal_cswdo');
        $this->command->warn('  - focal_hrmo: focal_hrmo');
        $this->command->warn('  - all frontdesk_* users: 12345678');
    }
}