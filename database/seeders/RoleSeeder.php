<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'SUPERADMIN',
            ],
            [
                'name' => 'OFFICE FRONTDESK',
            ],
            [
                'name' => 'CITY MAYOR',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
