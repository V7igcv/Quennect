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
            BarangaySeeder::class,
            PrioritySectorSeeder::class,
            EvaluationQuestionSeeder::class,
            OfficeSeeder::class, // This will also seed services and counters
            RoleSeeder::class,
        ]);
    }
}
