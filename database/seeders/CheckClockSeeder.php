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
        $statuses = ['Present', 'Sick Leave', 'Annual Leave', 'Absent'];
        $approvalStatuses = ['Approved', 'Pending', 'Rejected'];

        // Define date range (e.g., last 7 days)
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
                    $status = Arr::random($statuses);
                    $approval = Arr::random($approvalStatuses);

                    CheckClock::create([
                        'employee_id' => $employeeId,
                        'submitter_id' => $employeeId,
                        'ck_setting_id' => Arr::random($ckSettingIds),
                        'check_clock_date' => $date->toDateString(),
                        'status' => $status,
                        'status_approval' => $status === 'Present' ? 'Approved' : $approval,
                        'reject_reason' => $approval === 'Rejected' ? 'No reason provided' : null,
                    ]);
                }
            }
        }
    }
}
