<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioritySectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            'Senior Citizen',
            'Pregnant',
            'PWD',
            'Member of Indigenous People'
        ];

        foreach ($sectors as $sector) {
            DB::table('priority_sectors')->insert([
                'sector_name' => $sector,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
