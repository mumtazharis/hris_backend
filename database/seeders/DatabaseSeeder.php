<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            CheckClockStSeeder::class,
            CheckClockStTimesSeeder::class,
            EmployeeSeeder::class,
            CheckClockSeeder::class,
        ]);
    }
}
