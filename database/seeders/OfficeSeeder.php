<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            [
                'office_name' => 'City Planning and Development Office',
                'office_description' => 'Handles city planning and development permits',
                'office_acronym' => 'CPDO'
            ],
            [
                'office_name' => 'City Assessor’s Office',
                'office_description' => 'Delivering fair, accurate and uniform appraisal and assessments of real properties',
                'office_acronym' => 'CAO'
            ],
            [
                'office_name' => 'City Health Office',
                'office_description' => 'Provides health services and certificates',
                'office_acronym' => 'CHO'
            ],
            [
                'office_name' => 'Office of the City Social Welfare and Development Officer',
                'office_description' => 'Provides social welfare and development services',
                'office_acronym' => 'CSWDO'
            ],
            [
                'office_name' => 'Office of the City Local Civil Registrar',
                'office_description' => 'Handles civil registration and issuance of certificates',
                'office_acronym' => 'CLCR'
            ],
        ];

        foreach ($offices as $office) {
            $officeId = DB::table('offices')->insertGetId([
                'office_name' => $office['office_name'],
                'office_description' => $office['office_description'],
                'office_acronym' => $office['office_acronym'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add sample services for each office
            $services = [
                ['Business Permit', 'BP', 'Application for business permit'],
                ['Clearance', 'CL', 'Issuance of clearance'],
                ['Certification', 'CT', 'Issuance of certifications'],
            ];

            foreach ($services as $service) {
                DB::table('services')->insert([
                    'office_id' => $officeId,
                    'service_name' => $service[0],
                    'service_code' => $service[1],
                    'service_description' => $service[2],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Add sample counters
            for ($i = 1; $i <= 3; $i++) {
                DB::table('counters')->insert([
                    'office_id' => $officeId,
                    'counter_number' => $i,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
