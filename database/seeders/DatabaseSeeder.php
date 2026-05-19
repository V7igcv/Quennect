<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // Basic data with no dependencies
            BarangaySeeder::class,
            PrioritySectorSeeder::class,
            EvaluationQuestionSeeder::class,
            
            // Roles must be created before UserSeeder
            RoleSeeder::class,
            
            // Offices must be created before everything that depends on offices
            OfficeSeeder::class,
            
            // Services depend on OfficeSeeder (must run before AssistanceTypeSeeder)
            ServiceSeeder::class,
            
            // Counters depend on OfficeSeeder
            CounterSeeder::class,
            
            // Assistance types depend on ServiceSeeder (service_id FK)
            AssistanceTypeSeeder::class,
            
            // Users must run last (depends on RoleSeeder and OfficeSeeder)
            UserSeeder::class,
        ]);
    }
}
