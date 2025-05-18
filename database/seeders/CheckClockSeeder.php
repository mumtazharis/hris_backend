<?php

namespace Database\Seeders;

// Removed unused WithoutModelEvents import
use Illuminate\Database\Seeder;
use App\Models\CheckClock;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CheckClockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [2, 3, 6, 7];
        $checkClockTypes = ['in', 'out', 'break_in', 'break_out'];

        foreach ($employees as $employeeId) {
            foreach ($checkClockTypes as $type) {
                CheckClock::create([
                    'employee_id' => $employeeId,
                    'approver_id' => Arr::random($employees),
                    // 'check_clock_type' => $type,
                    'check_clock_date' => Carbon::now()->toDateString(),
                    // 'check_clock_time' => match ($type) {
                    //     'in' => Carbon::now()->setTime(9, 0)->toTimeString(),
                    //     'out' => Carbon::now()->setTime(17, 0)->toTimeString(),
                    //     'break_start' => Carbon::now()->setTime(12, 0)->toTimeString(),
                    //     'break_ended' => Carbon::now()->setTime(13, 0)->toTimeString(),
                    //     default => Carbon::now()->toTimeString(),
                    // },
                    'status' => 'present',
                    // 'latitude' => fake()->latitude,
                    // 'longitude' => fake()->longitude,
                    // 'evidence' => fake()->imageUrl(),
                    'status_approval' => 'approved', // Ensure this matches the allowed values in the database constraint
                ]);
            }
        }
    }
}
