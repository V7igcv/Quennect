<?php

namespace Database\Seeders;

use App\Models\AssistanceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            AssistanceType::firstOrCreate(
                [
                    'service_id' => $assistanceType['service_id'],
                    'assistance_name' => $assistanceType['assistance_name'],
                ],
                $assistanceType
            );
        }
    }
}
