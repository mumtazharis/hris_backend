<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class AllCheckClockSeeder extends Seeder
{
    // public function run(): void
    // {
    //     $companyIds = DB::table('companies')->pluck('company_id')->toArray();
    //     $statuses = ['Present', 'Sick Leave', 'Annual Leave', 'Absent'];
    //     $approvalStatuses = ['Approved', 'Pending', 'Rejected'];

    //     // Define date range (e.g., last 7 days)
    //     $startDate = Carbon::now()->subDays(6);
    //     $endDate = Carbon::now();

    //     foreach ($companyIds as $companyId) {
    //         $employeeIds = DB::table('employees')
    //             ->where('company_id', $companyId)
    //             ->where('employee_status', 'Active')
    //             ->pluck('id')
    //             ->toArray();

    //         $ckSettingIds = DB::table('check_clock_settings')
    //             ->where('company_id', $companyId)
    //             ->pluck('id')
    //             ->toArray();

    //         if (empty($employeeIds) || empty($ckSettingIds)) {
    //             continue;
    //         }

    //         foreach ($employeeIds as $employeeId) {
    //             foreach (Carbon::parse($startDate)->daysUntil($endDate) as $date) {
    //                 $positionName = DB::table('positions')
    //                     ->where('id', DB::table('employees')->where('id', $employeeId)->value('position_id'))
    //                     ->value('name');

    //                 $ckSettingId = Arr::random($ckSettingIds);

    //                 // Retrieve the schedule for the day
    //                 $dayName = strtolower($date->format('l'));
    //                 $ckTimes = DB::table('check_clock_setting_times')
    //                     ->where('ck_setting_id', $ckSettingId)
    //                     ->where('day', $dayName)
    //                     ->first();

    //                 if (!$ckTimes) {
    //                     continue;
    //                 }

    //                 // Generate a random clock-in time
    //                 $randomClockInTime = Carbon::parse($ckTimes->clock_in)
    //                     ->addMinutes(rand(-30, 30)) // Randomly adjust clock-in time
    //                     ->format('H:i:s');

    //                 // Determine the status based on the clock-in time
    //                 $status = $randomClockInTime > $ckTimes->max_clock_in ? 'Absent' : Arr::random($statuses);
    //                 $approval = $status === 'Present' ? 'Approved' : Arr::random($approvalStatuses);

    //                 // Create the CheckClock record
    //                 $checkClock = DB::table('check_clocks')->insertGetId([
    //                     'employee_id' => $employeeId,
    //                     'submitter_id' => $employeeId,
    //                     'ck_setting_id' => $ckSettingId,
    //                     'check_clock_date' => $date->toDateString(),
    //                     'position' => $positionName,
    //                     'status' => $status,
    //                     'status_approval' => $approval,
    //                     'reject_reason' => $approval === 'Rejected' ? 'No reason provided' : null,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ]);

    //                 // Handle PresentDetail or AbsentDetail based on the status
    //                 if ($status === 'Present') {
    //                     // Insert the "in" record
    //                     DB::table('present_detail_cc')->insert([
    //                         'ck_id' => $checkClock,
    //                         'ck_times_id' => $ckTimes->id,
    //                         'check_clock_type' => 'in',
    //                         'check_clock_time' => $randomClockInTime,
    //                         'latitude' => '3.597031',
    //                         'longitude' => '98.678513',
    //                         'evidence' => 'evidence_in_' . $checkClock . '.jpg',
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);

    //                     // Derive "out" time based on "in" time + 8 hours
    //                     $outTime = Carbon::parse($randomClockInTime)
    //                         ->addHours(8)
    //                         ->addMinutes(rand(0, 30)) // Small randomness
    //                         ->format('H:i:s');

    //                     // Insert the "out" record
    //                     DB::table('present_detail_cc')->insert([
    //                         'ck_id' => $checkClock,
    //                         'ck_times_id' => $ckTimes->id,
    //                         'check_clock_type' => 'out',
    //                         'check_clock_time' => $outTime,
    //                         'latitude' => '3.597031',
    //                         'longitude' => '98.678513',
    //                         'evidence' => null,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);
    //                 } elseif (in_array($status, ['Sick Leave', 'Annual Leave'])) {
    //                     // Insert AbsentDetail record
    //                     DB::table('absent_detail_cc')->insert([
    //                         'ck_id' => $checkClock,
    //                         'start_date' => $date->toDateString(),
    //                         'end_date' => $date->toDateString(),
    //                         'evidence' => 'evidence_absent_' . $checkClock . '.jpg',
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
    // }
    public function run(): void
    {
        $companyIds = DB::table('companies')->pluck('company_id')->toArray();

        // Date range: last 7 days
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        foreach ($companyIds as $companyId) {
            $employeeIds = DB::table('employees')
                ->where('company_id', $companyId)
                ->where('employee_status', 'Active')
                ->pluck('id')
                ->toArray();

            $ckSettingIds = DB::table('check_clock_settings')
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            if (empty($employeeIds) || empty($ckSettingIds)) {
                continue;
            }

            foreach ($employeeIds as $employeeId) {
                foreach (Carbon::parse($startDate)->daysUntil($endDate) as $date) {
                    $positionName = DB::table('positions')
                        ->where('id', DB::table('employees')->where('id', $employeeId)->value('position_id'))
                        ->value('name');

                    $ckSettingId = Arr::random($ckSettingIds);

                    $dayName = strtolower($date->format('l'));
                    $ckTimes = DB::table('check_clock_setting_times')
                        ->where('ck_setting_id', $ckSettingId)
                        ->where('day', $dayName)
                        ->first();

                    if (!$ckTimes) {
                        continue;
                    }

                    $randomClockInTime = Carbon::parse($ckTimes->clock_in)
                        ->addMinutes(rand(-30, 30))
                        ->format('H:i:s');

                    // Only insert if clock-in is before max_clock_in
                    if ($randomClockInTime <= $ckTimes->max_clock_in) {
                        $checkClockId = DB::table('check_clocks')->insertGetId([
                            'employee_id' => $employeeId,
                            'submitter_id' => $employeeId,
                            'ck_setting_id' => $ckSettingId,
                            'check_clock_date' => $date->toDateString(),
                            'position' => $positionName,
                            'status' => 'Present',
                            'status_approval' => 'Approved',
                            'reject_reason' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Insert "in" record
                        DB::table('present_detail_cc')->insert([
                            'ck_id' => $checkClockId,
                            'ck_times_id' => $ckTimes->id,
                            'check_clock_type' => 'in',
                            'check_clock_time' => $randomClockInTime,
                            'latitude' => '3.597031',
                            'longitude' => '98.678513',
                            'evidence' => 'evidence_in_' . $checkClockId . '.jpg',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Generate out time
                        $outTime = Carbon::parse($randomClockInTime)
                            ->addHours(8)
                            ->addMinutes(rand(0, 30))
                            ->format('H:i:s');

                        // Insert "out" record
                        DB::table('present_detail_cc')->insert([
                            'ck_id' => $checkClockId,
                            'ck_times_id' => $ckTimes->id,
                            'check_clock_type' => 'out',
                            'check_clock_time' => $outTime,
                            'latitude' => '3.597031',
                            'longitude' => '98.678513',
                            'evidence' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
