<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'admin@example.com',
            'phone' => '081234567890',
            'password' => Hash::make('password123'), // Always hash passwords!
            'google_id' => null,
            'role' => 'admin',
        ]);

        User::create([
            'email' => 'user@example.com',
            'phone' => '081298765432',
            'password' => Hash::make('password123'),
            'google_id' => null,
            'role' => 'user',
        ]);
    }
}
