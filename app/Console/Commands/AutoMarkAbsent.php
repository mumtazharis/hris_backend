<?php

namespace App\Console\Commands;

use App\Models\AbsentDetail;
use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoMarkAbsent extends Command
{
    protected $signature = 'app:auto-mark-absent';
    protected $description = 'Automatically submit absent status for employees who missed clock-in after maxClockIn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // You can restrict by company or other logic as needed
        $employees = Employee::all();

        foreach ($employees as $employee) {
            $ckSetting = CheckClockSetting::where('company_id', $employee->company_id)
                            ->where('id', $employee->ck_setting_id) // adjust if needed
                            ->first();

            if (!$ckSetting) {
                continue;
            }

            $maxClockIn = Carbon::parse($ckSetting->max_clock_in); // e.g., 09:00
            $maxClockInToday = Carbon::today()->setTimeFromTimeString($maxClockIn->toTimeString());

            if ($now->lessThanOrEqualTo($maxClockInToday)) {
                // It's not yet time to auto-submit
                continue;
            }

            $alreadyChecked = CheckClock::where('employee_id', $employee->id)
                ->whereDate('check_clock_date', $today)
                ->exists();

            if (!$alreadyChecked) {
                DB::beginTransaction();
                try {
                    CheckClock::create([
                        'employee_id' => $employee->id,
                        'submitter_id' => null, // null or system admin
                        'ck_setting_id' => $ckSetting->id,
                        'check_clock_date' => $today,
                        'status' => 'Absent',
                        'status_approval' => 'Approved',
                        'reject_reason' => 'Auto submission: Missed clock-in',
                    ]);

                    // AbsentDetail::create([
                    //     'ck_id' => $cc->id,
                    //     'start_date' => $today,
                    //     'end_date' => $today,
                    //     'evidence' => null,
                    // ]);

                    DB::commit();
                    $this->info("Absent record auto-submitted for employee ID {$employee->id}");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Failed for employee ID {$employee->id}: " . $e->getMessage());
                }
            }
        }

        return 0;

    }
}
