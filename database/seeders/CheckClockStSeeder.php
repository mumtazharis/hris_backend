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
            'latitude' => -7.944240526367352,
            'longitude' => 112.61488447149941,
            'radius' => 100,
        ]);
    }
}
