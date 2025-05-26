<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'company_id' => 1,
                'full_name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '081234567890',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 1,
                'full_name' => 'Employee One',
                'email' => 'employee1@example.com',
                'phone' => '081298765431',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 1,
                'full_name' => 'Employee Two',
                'email' => 'employee2@example.com',
                'phone' => '081298765432',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 1,
                'full_name' => 'Employee Three',
                'email' => 'employee3@example.com',
                'phone' => '081298765433',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 1,
                'full_name' => 'Employee Four',
                'email' => 'employee4@example.com',
                'phone' => '081298765434',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 2,
                'full_name' => 'Employee Five',
                'email' => 'employee5@example.com',
                'phone' => '081298765435',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 2,
                'full_name' => 'Admin User Two',
                'email' => 'admin2@example.com',
                'phone' => '081298765436',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 2,
                'full_name' => 'Employee Seven',
                'email' => 'employee7@example.com',
                'phone' => '081298765437',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 2,
                'full_name' => 'Employee Eight',
                'email' => 'employee8@example.com',
                'phone' => '081298765438',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 2,
                'full_name' => 'Employee Nine',
                'email' => 'employee9@example.com',
                'phone' => '081298765439',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
