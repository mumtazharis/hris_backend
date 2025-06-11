<?php

namespace Database\Seeders;

use App\Models\OvertimeSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OvertimeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OvertimeSetting::insert([
            [
                'company_id' => 'COMP001',
                'name' => 'Weekday Overtime Government',
                'type' => 'Government Regulation',
                'category' => 'Regular Weekday',
                'working_days' => '5 days',
                'status' => 'Active'
            ],
            [
                "company_id" => "COMP001",
                "name" => "Flat 1",
                "type" => "Flat",
                "category" => "Regular Weekday",
                'working_days' => null,
                'status' => 'Inactive'
            ],
            [
                "company_id" => "COMP001",
                "name" => "Flat 2",
                "type" => "Flat",
                "category" => "Regular Weekday",
                'working_days' => null,
                'status' => 'Inactive'
            ],
            [
                "company_id" => "COMP001",
                "name" => "Flat 3",
                "type" => "Flat",
                "category" => "Regular Weekday",
                'working_days' => null,
                'status' => 'Inactive'
            ],
            [
                "company_id" => "COMP001",
                "name" => "Flat 4",
                "type" => "Flat",
                "category" => "Regular Weekday",
                'working_days' => null,
                'status' => 'Inactive'
            ]
        ]);
    }
}
