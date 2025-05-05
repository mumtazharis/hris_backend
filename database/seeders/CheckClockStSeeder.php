<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckClockSetting;

class CheckClockStSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CheckClockSetting::create([
            'name' => 'WFA',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);

        CheckClockSetting::create([
            'name' => 'WFO',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 500,
        ]);

        CheckClockSetting::create([
            'name' => 'Hybrid',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);
    }
}
