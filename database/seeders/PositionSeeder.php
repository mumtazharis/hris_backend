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
        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 1,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 1,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 2,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 2,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 3,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 3,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'Software Engineer',
        //     'department_id' => 1,
        //     // 'salary' => 75000.00,
        // ]);

        // Position::create([
        //     'name' => 'Project Manager',
        //     'department_id' => 2,
        //     // 'salary' => 85000.00,
        // ]);

        // Position::create([
        //     'name' => 'HR Specialist',
        //     'department_id' => 3,
        //     // 'salary' => 60000.00,
        // ]);
        
        // Position::create([
        //     'name' => 'Secretary',
        //     'department_id' => 3,
        //     // 'salary' => 60000.00,
        // ]);

        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 4,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 4,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 5,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 5,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'CEO',
        //     'department_id' => 6,
        //     // 'salary' => 150000.00,
        // ]);

        // Position::create([
        //     'name' => 'Manager',
        //     'department_id' => 6,
        //     // 'salary' => 50000.00,
        // ]);

        // Position::create([
        //     'name' => 'Software Engineer',
        //     'department_id' => 4,
        //     // 'salary' => 75000.00,
        // ]);

        // Position::create([
        //     'name' => 'Project Manager',
        //     'department_id' => 5,
        //     // 'salary' => 85000.00,
        // ]);

        // Position::create([
        //     'name' => 'HR Specialist',
        //     'department_id' => 6,
        //     // 'salary' => 60000.00,
        // ]);
        
        // Position::create([
        //     'name' => 'Secretary',
        //     'department_id' => 6,
        //     // 'salary' => 60000.00,
        // ]);

        Position::create([
            'company_id' => 'COMP001', // Menggunakan string literal 'COMP001'
            'name' => 'CEO',
            'department_id' => 1,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Manager',
            'department_id' => 1,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'CEO',
            'department_id' => 2,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Manager',
            'department_id' => 2,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'CEO',
            'department_id' => 3,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Manager',
            'department_id' => 3,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Software Engineer',
            'department_id' => 1,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Project Manager',
            'department_id' => 2,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'HR Specialist',
            'department_id' => 3,
        ]);
        Position::create([
            'company_id' => 'COMP001',
            'name' => 'Secretary',
            'department_id' => 3,
        ]);

        // ==========================================================
        // POSISI UNTUK PERUSAHAAN 'COMP002'
        // (Asumsi: Departemen 4, 5, 6 adalah milik Perusahaan 'COMP002')
        // ==========================================================
        Position::create([
            'company_id' => 'COMP002', // Menggunakan string literal 'COMP002'
            'name' => 'CEO',
            'department_id' => 4,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Manager',
            'department_id' => 4,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'CEO',
            'department_id' => 5,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Manager',
            'department_id' => 5,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'CEO',
            'department_id' => 6,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Manager',
            'department_id' => 6,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Software Engineer',
            'department_id' => 4,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Project Manager',
            'department_id' => 5,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'HR Specialist',
            'department_id' => 6,
        ]);
        Position::create([
            'company_id' => 'COMP002',
            'name' => 'Secretary',
            'department_id' => 6,
        ]);
    }
}
