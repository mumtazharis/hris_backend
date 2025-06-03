<?php

namespace Database\Seeders;

// Removed unused WithoutModelEvents import
use Illuminate\Database\Seeder;
use App\Models\CheckClock;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CheckClockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employeeIds = DB::table('employees')->whereIn('employee_status', ['Active'])->pluck('id')->toArray();
        $statuses = ['Present', 'Sick Leave', 'Annual Leave'];
        $approvalStatuses = ['Approved', 'Pending', 'Rejected'];

        $forcedRejectedIds = Arr::random($employeeIds, min(3, count($employeeIds))); // Just in case there are < 3 employees
        $usedIds = [];

        foreach ($employeeIds as $employeeId) {
            $status = Arr::random($statuses);
            $approval = in_array($employeeId, $forcedRejectedIds) ? 'Pending' : Arr::random($approvalStatuses);

            CheckClock::create([
                'employee_id' => $employeeId,
                'approver_id' => Arr::random($employeeIds),
                'check_clock_date' => Carbon::now(),
                'status' => $status,
                'status_approval' => $approval,
                'reject_reason' => $approval === 'Rejected' ? 'No reason provided' : null,
            ]);
        }

        // $minPending = 0;
        // foreach ($employeeIds as $employeeId) {
        //     $status = Arr::random($statuses);
        //     $approval = Arr::random($approvalStatuses);

        //     if ($approval === 'Pending') {
        //         $minPending++;
        //     }

        //     CheckClock::create([
        //         'employee_id' => $employeeId,
        //         'approver_id' => Arr::random($employeeIds), // Fixed: Use $employeeIds array instead of $employeeId
        //         'check_clock_date' => Carbon::now(),
        //         'status' => $status, 
        //         'status_approval' => $approval,
        //         'reject_reason' => $approval === 'Rejected' ? 'No reason provided' : null,
        //     ]);
        // }
    }
}
