<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            [
                'office_name' => 'Office of the City Mayor',
                'office_description' => 'The Office of the City Mayor oversees the local government operations, issues permits and clearances, and manages executive functions of the city.',
                'office_acronym' => 'CMO',
                'map_image' => '/storage/maps/cmo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'Office of the City Mayor - Library Services',
                'office_description' => 'The City Library Services provides library membership, book borrowing services, children\'s programs, and accepts book donations for the community.',
                'office_acronym' => 'CML',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'Ligao Community College',
                'office_description' => 'Ligao Community College offers higher education programs and provides academic records, certifications, and student services.',
                'office_acronym' => 'CM-LiComCo',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City General Services Office',
                'office_description' => 'The City General Services Office manages government property, procurement, maintenance of facilities, vehicles, and equipment, and provides logistical support.',
                'office_acronym' => 'CGSO',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City Local Civil Registrar',
                'office_description' => 'The City Local Civil Registrar handles civil registry documents including birth, death, and marriage registrations, issuances of certificates, and corrections of clerical errors.',
                'office_acronym' => 'CLCR',
                'map_image' => '/storage/maps/clcr-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Treasurer\'s Office',
                'office_description' => 'The City Treasurer\'s Office manages revenue collection, real property tax payments, issuance of community tax certificates, and disbursement of funds.',
                'office_acronym' => 'CTO',
                'map_image' => '/storage/maps/cto-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Treasurer\'s Office - Office of the Economic Enterprise',
                'office_description' => 'The Office of the Economic Enterprise manages public markets, slaughterhouse operations, cemetery plots, and collection of fees and charges for various city enterprises.',
                'office_acronym' => 'CTO-OEE',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City Assessor\'s Office',
                'office_description' => 'The City Assessor\'s Office appraises and assesses real properties, issues tax declarations, and maintains property records for taxation purposes.',
                'office_acronym' => 'CAO',
                'map_image' => '/storage/maps/cao-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'Business Permit and Licensing Office',
                'office_description' => 'The Business Permit and Licensing Office processes business permits and licenses for business establishments operating within the city.',
                'office_acronym' => 'BPLO',
                'map_image' => '/storage/maps/bplo-office-map.png',
                'is_active' => false,
            ],
            [
                'office_name' => 'City Environment and Natural Resources Office',
                'office_description' => 'The City Environment and Natural Resources Office implements environmental programs, issues permits for tree cutting, distributes seedlings, and manages fisherfolk registration.',
                'office_acronym' => 'CENRO',
                'map_image' => '/storage/maps/cenro-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Engineering Office',
                'office_description' => 'The City Engineering Office oversees infrastructure projects, construction, maintenance of public buildings and facilities, and issues certificates for completed projects.',
                'office_acronym' => 'CEO',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City Cooperative Development Office',
                'office_description' => 'The City Cooperative Development Office promotes cooperative development, provides livelihood assistance, and conducts skills training programs.',
                'office_acronym' => 'CCDO',
                'map_image' => '/storage/maps/ccdo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Legal Office',
                'office_description' => 'The City Legal Office provides legal services including document drafting, contract review, legal opinions, and handles legal matters for the local government.',
                'office_acronym' => 'CLO',
                'map_image' => '/storage/maps/clo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'Administrative Office',
                'office_description' => 'The Administrative Office manages human resources, administrative processes, property acquisition, and handles correspondence and documentation for the city government.',
                'office_acronym' => 'ADMIN',
                'map_image' => '/storage/maps/admin-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Health Office',
                'office_description' => 'The City Health Office provides healthcare services including medical consultations, immunization programs, maternal and child care, family planning, and health certifications.',
                'office_acronym' => 'CHO',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City Agriculture Office',
                'office_description' => 'The City Agriculture Office supports farmers through registration programs, technical assistance, provision of agricultural inputs, training, credit assistance, and farm mechanization.',
                'office_acronym' => 'AGRICULTURE',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'City Veterinary Office',
                'office_description' => 'The City Veterinary Office provides animal health services including anti-rabies vaccination, veterinary health certificates, animal consultation, and animal control operations.',
                'office_acronym' => 'VETERINARY',
                'map_image' => '/storage/maps/veterinary-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Social Welfare and Development Office',
                'office_description' => 'The City Social Welfare and Development Office provides assistance to individuals in crisis, social case studies, sector ID issuance, shelter assistance, and community-based programs.',
                'office_acronym' => 'CSWDO',
                'map_image' => '/storage/maps/cswdo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'Public Employment Service Office',
                'office_description' => 'The Public Employment Service Office facilitates employment through job placement, employer accreditation, job posting, and referral services to government agencies.',
                'office_acronym' => 'PESO',
                'map_image' => '/storage/maps/peso-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Planning and Development Office',
                'office_description' => 'The City Planning and Development Office handles zoning certifications, locational clearances, development permits, and urban planning for the city.',
                'office_acronym' => 'CPDO',
                'map_image' => '/storage/maps/cpdo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Disaster Risk Reduction and Management Office',
                'office_description' => 'The City Disaster Risk Reduction and Management Office coordinates disaster preparedness, response, rescue services, and conducts DRRM training and assessments.',
                'office_acronym' => 'CDRRMO',
                'map_image' => null,
                'is_active' => true,
            ],
            [
                'office_name' => 'Human Resource Management Office',
                'office_description' => 'The Human Resource Management Office manages employee records, leave applications, payroll processing, performance evaluations, and personnel administration.',
                'office_acronym' => 'HRMO',
                'map_image' => '/storage/maps/hrmo-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Accounting Office',
                'office_description' => 'The City Accounting Office handles financial transactions, disbursement processing, voucher preparation, tax certificate issuance, and financial reporting.',
                'office_acronym' => 'ACCOUNTING',
                'map_image' => '/storage/maps/accounting-office-map.png',
                'is_active' => true,
            ],
            [
                'office_name' => 'City Budget Office',
                'office_description' => 'The City Budget Office prepares and executes the city budget, reviews barangay and SK budgets, and manages budget allocation and utilization.',
                'office_acronym' => 'BUDGET',
                'map_image' => '/storage/maps/budget-office-map.png',
                'is_active' => true,
            ],
        ];

        foreach ($offices as $office) {
            DB::table('offices')->updateOrInsert(
                ['office_acronym' => $office['office_acronym']],
                [
                    'office_name' => $office['office_name'],
                    'office_description' => $office['office_description'],
                    'is_active' => $office['is_active'],
                    'logo' => null,
                    'map_image' => $office['map_image'],
                    'requires_evaluation' => true,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Offices seeded successfully!');
    }
}