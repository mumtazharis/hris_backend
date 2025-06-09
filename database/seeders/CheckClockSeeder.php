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
        $companyIds = DB::table('companies')->pluck('company_id')->toArray();
        $statuses = ['Present', 'Sick Leave', 'Annual Leave'];
        $approvalStatuses = ['Approved', 'Pending', 'Rejected'];

        foreach ($companyIds as $companyId) {
            // Get active employees of the company
            $employeeIds = DB::table('employees')
                ->where('company_id', $companyId)
                ->where('employee_status', 'Active')
                ->pluck('id')
                ->toArray();

            // Get clock settings for the company
            $ckSettingIds = DB::table('check_clock_settings')
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            // Skip if no data
            if (empty($employeeIds) || empty($ckSettingIds)) {
                continue;
            }

            $forcedRejectedIds = Arr::random($employeeIds, min(3, count($employeeIds)));

            // Force one employee to always be 'Present'
            $presentEmployeeId = Arr::random($employeeIds);

            foreach ($employeeIds as $employeeId) {
                $status = $employeeId === $presentEmployeeId ? 'Present' : Arr::random($statuses);
                $approval = in_array($employeeId, $forcedRejectedIds) ? 'Pending' : Arr::random($approvalStatuses);

                CheckClock::create([
                    'employee_id' => $employeeId,
                    'submitter_id' => Arr::random($employeeIds),
                    'ck_setting_id' => Arr::random($ckSettingIds),
                    'check_clock_date' => Carbon::now()->toDateString(),
                    'status' => $status,
                    'status_approval' => $approval,
                    'reject_reason' => $approval === 'Rejected' ? 'No reason provided' : null,
                ]);
            }
        }
    }
}
