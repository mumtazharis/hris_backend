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
                'company_id' => 'COMP001',
                'full_name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '081234567890',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP001',
                'full_name' => 'Employee One',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP001',
                'full_name' => 'Employee Two',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP001',
                'full_name' => 'Employee Three',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP001',
                'full_name' => 'Employee Four',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP001',
                'full_name' => 'Employee Five',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP002',
                'full_name' => 'Admin User Two',
                'email' => 'admin2@example.com',
                'phone' => '081298765436',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP002',
                'full_name' => 'Employee Six',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP002',
                'full_name' => 'Employee Seven',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP002',
                'full_name' => 'Employee Eight',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_profile_complete' => true,
                'auth_provider' => 'local',
            ],
            [
                'company_id' => 'COMP002',
                'full_name' => 'Employee Nine',
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
