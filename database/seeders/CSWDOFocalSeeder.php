<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class CSWDOFocalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create the role if it doesn't exist
        $role = Role::firstOrCreate([
            'name' => 'CSWDO FOCAL'
        ]);

        // Create the user if it doesn't exist
        $username = 'focal_cswdo';
        $exists = User::where('username', $username)->exists();

        if (!$exists) {
            User::create([
                'username' => $username,
                'password_hash' => Hash::make('focal_cswdo'),
                'role_id' => $role->id,
                'office_id' => null,
            ]);
        }
    }
}
