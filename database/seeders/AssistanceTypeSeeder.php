<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssistanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // AICS (service_id = 126) assistance types
        $assistanceTypes = [
            [
                'service_id' => 126,
                'assistance_name' => 'Medical Assistance',
            ],
            [
                'service_id' => 126,
                'assistance_name' => 'Burial Assistance',
            ],
            [
                'service_id' => 126,
                'assistance_name' => 'Financial Assistance',
            ],
            [
                'service_id' => 126,
                'assistance_name' => 'Transportation Assistance',
            ],
            [
                'service_id' => 126,
                'assistance_name' => 'Educational Assistance',
            ],
            [
                'service_id' => 126,
                'assistance_name' => 'Emergency Shelter Assistance',
            ],
        ];

        foreach ($assistanceTypes as $assistanceType) {
            DB::table('assistance_types')->updateOrInsert(
                [
                    'service_id' => $assistanceType['service_id'],
                    'assistance_name' => $assistanceType['assistance_name'],
                ],
                [
                    'service_id' => $assistanceType['service_id'],
                    'assistance_name' => $assistanceType['assistance_name'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}