<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CounterSeeder extends Seeder
{
    public function run(): void
    {
        // Get all active offices
        $offices = DB::table('offices')->where('is_active', true)->get();

        foreach ($offices as $office) {
            DB::table('counters')->updateOrInsert(
                [
                    'office_id' => $office->id,
                    'counter_number' => 1,
                ],
                [
                    'is_enabled' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Counters seeded successfully! (1 counter per office)');
    }
}