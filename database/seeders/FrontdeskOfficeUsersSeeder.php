<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FrontdeskOfficeUsersSeeder extends Seeder
{
    public function run(): void
    {
        $frontdeskRole = DB::table('roles')->where('name', 'OFFICE FRONTDESK')->first();

        if (!$frontdeskRole) {
            $this->command?->error('OFFICE FRONTDESK role not found. Run RoleSeeder first.');
            return;
        }

        $users = [
            ['username' => 'frontdesk_cmo', 'office_id' => 1],
            ['username' => 'frontdesk_cml', 'office_id' => 2],
            ['username' => 'frontdesk_licomco', 'office_id' => 3],
            ['username' => 'frontdesk_cgso', 'office_id' => 4],
            ['username' => 'frontdesk_clcr', 'office_id' => 5],
            ['username' => 'frontdesk_cto', 'office_id' => 6],
            ['username' => 'frontdesk_cto_oee', 'office_id' => 7],
            ['username' => 'frontdesk_cao', 'office_id' => 8],
            ['username' => 'frontdesk_bplo', 'office_id' => 9],
            ['username' => 'frontdesk_cenro', 'office_id' => 10],
            ['username' => 'frontdesk_ceo', 'office_id' => 11],
            ['username' => 'frontdesk_ccdo', 'office_id' => 12],
            ['username' => 'frontdesk_clo', 'office_id' => 13],
            ['username' => 'frontdesk_admin', 'office_id' => 14],
            ['username' => 'frontdesk_cho', 'office_id' => 15],
            ['username' => 'frontdesk_agriculture', 'office_id' => 16],
            ['username' => 'frontdesk_veterinary', 'office_id' => 17],
            ['username' => 'frontdesk_cswdo', 'office_id' => 18],
            ['username' => 'frontdesk_peso', 'office_id' => 19],
            ['username' => 'frontdesk_cpdo', 'office_id' => 20],
            ['username' => 'frontdesk_cdrrmo', 'office_id' => 21],
            ['username' => 'frontdesk_hrmo', 'office_id' => 22],
            ['username' => 'frontdesk_accounting', 'office_id' => 23],
            ['username' => 'frontdesk_budget', 'office_id' => 24],
        ];

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                DB::table('users')->updateOrInsert(
                    ['username' => $user['username']],
                    [
                        'password_hash' => Hash::make('12345678'),
                        'office_id' => $user['office_id'],
                        'role_id' => $frontdeskRole->id,
                        'last_login_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT COALESCE(MAX(id), 1) FROM users), true)");

            DB::commit();
            $this->command?->info('Frontdesk office users seeded successfully. Default password: 12345678');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
