<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Position::create([
            'name' => 'CEO',
            'department_id' => 1,
            // 'salary' => 150000.00,
        ]);

        Position::create([
            'name' => 'Manager',
            'department_id' => 1,
            // 'salary' => 50000.00,
        ]);

        Position::create([
            'name' => 'CEO',
            'department_id' => 2,
            // 'salary' => 150000.00,
        ]);

        Position::create([
            'name' => 'Manager',
            'department_id' => 2,
            // 'salary' => 50000.00,
        ]);

        Position::create([
            'name' => 'CEO',
            'department_id' => 3,
            // 'salary' => 150000.00,
        ]);

        Position::create([
            'name' => 'Manager',
            'department_id' => 3,
            // 'salary' => 50000.00,
        ]);

        Position::create([
            'name' => 'Software Engineer',
            'department_id' => 1,
            // 'salary' => 75000.00,
        ]);

        Position::create([
            'name' => 'Project Manager',
            'department_id' => 2,
            // 'salary' => 85000.00,
        ]);

        Position::create([
            'name' => 'HR Specialist',
            'department_id' => 3,
            // 'salary' => 60000.00,
        ]);
        
        Position::create([
            'name' => 'Secretary',
            'department_id' => 3,
            // 'salary' => 60000.00,
        ]);
    }
}
