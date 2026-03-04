<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateOfficesSeeder extends Seeder
{
    public function run(): void
    {
        // Add logo to existing offices (set to null for now)
        DB::table('offices')
            ->whereNull('logo')
            ->update(['logo' => null]); // This just ensures the column is set
        
        // Add the new CMO office if it doesn't exist
        $cmoExists = DB::table('offices')
            ->where('office_acronym', 'CMO')
            ->exists();
            
        if (!$cmoExists) {
            $officeId = DB::table('offices')->insertGetId([
                'office_name' => 'Office of the City Mayor',
                'office_description' => 'Handles city mayor-related services and concerns',
                'office_acronym' => 'CMO',
                'logo' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Add services for CMO
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
            
            // Add counters for CMO
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
        
        $this->command->info('Offices table updated successfully!');
    }
}