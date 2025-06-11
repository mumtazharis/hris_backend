<?php

namespace App\Http\Controllers\employee;

use App\Models\AbsentDetail;
use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\PresentDetail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckClockControllerEmp extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $employee_id = DB::table('employees')
            ->where('user_id', Auth::user()->id)
            ->value('id');

        if (!$employee_id) {
            return response()->json([
                'errors' => ['message' => 'Your employee record was not found. Please contact HR or admin.']
            ], 404);
        }

        $checkClocks = DB::table('check_clocks as cc')
            ->join('employees as e', 'cc.employee_id', '=', 'e.id')
            ->join('check_clock_settings as ccs', 'cc.ck_setting_id', '=', 'ccs.id')
            ->join('check_clock_setting_times as ccst', 'ccst.ck_setting_id', '=', 'ccs.id')
            ->leftJoin('present_detail_cc as pdc', 'pdc.ck_id', '=', 'cc.id')
            ->leftJoin('absent_detail_cc as adc', 'adc.ck_id', '=', 'cc.id')
            ->leftJoin('users as u', 'cc.submitter_id', '=', 'u.id')
            // ->where('e.company_id', $companyId)
            ->where('e.id', $employee_id)
            ->groupBy([
                'cc.id',
                'cc.check_clock_date',
                'ccs.name',
                // 'pdc.latitude',
                // 'pdc.longitude',
                'cc.status',
            ])
            ->select([
                'cc.id as data_id',
                'cc.check_clock_date as date',
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.check_clock_time END) as clock_in'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'out\' THEN pdc.check_clock_time END) as clock_out'),
                'ccs.name as work_type',
                DB::raw("
                CASE
                    WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) IS NULL THEN cc.status
                    WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) < MIN(ccst.min_clock_in) THEN 'Invalid (Too Early)'
                    WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) <= MIN(ccst.clock_in) THEN 'On Time'
                    WHEN MAX(CASE WHEN pdc.check_clock_type = 'in' THEN pdc.check_clock_time END) <= MIN(ccst.max_clock_in) THEN 'Late'
                    ELSE 'Absent'
                END as status
            "),
                DB::raw('MAX(cc.status_approval) as approval_status'),
                DB::raw('MAX(CASE WHEN adc.start_date IS NOT NULL THEN adc.start_date END) as absent_start_date'),
                DB::raw('MAX(CASE WHEN adc.end_date IS NOT NULL THEN adc.end_date END) as absent_end_date'),
                // DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.latitude END) as latitude'),
                // DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.longitude END) as longitude'),
                DB::raw('MAX(cc.reject_reason) as reject_reason'),
            ])
            ->orderBy('cc.check_clock_date')
            ->get();

        $location = CheckClockSetting::select(
            'check_clock_settings.id as data_id',
            'check_clock_settings.latitude',
            'check_clock_settings.longitude',
            'check_clock_settings.radius'
        )
            ->where('check_clock_settings.name', '=', 'WFO')
            ->where('check_clock_settings.company_id', $companyId)
            ->get();
        return response()->json(['location_rule' => $location, 'check_clock_data' => $checkClocks]);
    }

    public function store(Request $request)
    {
        $user = Auth::user()->id;
        $companyId = Auth::user()->company_id;
        $employee_id = DB::table('employees')
            ->where('user_id', Auth::user()->id)
            ->value('id');

        if (!$employee_id) {
            return response()->json([
                'errors' => ['message' => 'Your employee record was not found. Please contact HR or admin.']
            ], 404);
        }

        $request->validate([
            // 'employee_id' => 'required|exists:employees,id',
            'ck_setting_name' => 'required|in:WFA,WFO',
            'check_clock_date' => 'required|date',
            'status' => 'required|in:Present,Sick Leave,Annual Leave',

            'check_clock_type' => 'nullable|required_if:status,Present|in:in,out',
            'check_clock_time' => 'required_if:status,Present|date_format:H:i',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'evidence' => 'required_if:status,Sick Leave,Annual Leave|file|mimes:jpeg,png,jpg|max:5120',

            'start_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date|after_or_equal:today',
            'end_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date|after_or_equal:today',
        ], [
            // 'employee_id.required' => 'Employee is required.',
            // 'employee_id.exists' => 'Selected employee does not exist.',

            'ck_setting_name.required' => 'Work Type is required.',
            'ck_setting_name.in' => 'Work Type must be either WFA or WFO.',

            'check_clock_date.required' => 'Check clock date is required.',
            'check_clock_date.date' => 'Check clock date must be a valid date.',

            'status.required' => 'Attendance Type is required.',
            'status.in' => 'Attendance Type must be Present, Sick Leave, or Annual Leave.',

            'check_clock_type.required_if' => 'Check clock type is required when status is Present.',
            'check_clock_type.in' => 'Check clock type must be either "in" or "out".',

            'check_clock_time.required_if' => 'Check clock time is required when status is Present.',
            'check_clock_time.date_format' => 'Invalid Check Clock Time Input.',

            'evidence.required_if' => 'Evidence is required when status is Sick Leave or Annual Leave.',
            'evidence.image' => 'Evidence must be an image file.',
            'evidence.max' => 'Evidence image must not exceed 5MB.',

            'start_date.required_if' => 'Start date is required when status is Sick Leave or Annual Leave.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',

            'end_date.required_if' => 'End date is required when status is Sick Leave or Annual Leave.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be today or a future date.',
        ]);

        $evidencePath = null;

        if ($request->status === 'Present') {
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('evidence_present', 's3');
            }

            // find ck setting
            $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                ->where('name', $request->ck_setting_name)->first();

            if (!$ckSetting) {
                return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
            }


            // find cc setting times
            $dayName = strtolower(Carbon::parse($request->check_clock_date)->format('l')); // e.g., "Monday"

            $settingTime = \App\Models\CheckClockSettingTimes::where('ck_setting_id', $ckSetting->id)
                ->where('day', $dayName)
                ->first();

            if (!$settingTime) {
                return response()->json([
                    'errors' => ['message' => "No schedule settings found for {$dayName}."]
                ], 422);
            }

            $checkClockTime = Carbon::createFromFormat('H:i', $request->check_clock_time);

            $minCI = Carbon::createFromFormat('H:i:s', $settingTime->min_clock_in);
            $actualCI = Carbon::createFromFormat('H:i:s', $settingTime->clock_in);
            $maxCI = Carbon::createFromFormat('H:i:s', $settingTime->max_clock_in);

            if ($request->check_clock_type === 'in') {
                // Check if CheckClock exists for this employee and date (with any PresentDetail)
                $exists = CheckClock::where('employee_id', $employee_id)
                    ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                    ->exists();

                if ($exists) {
                    return response()->json(['errors' => ['message' => 'You have already check clock today']], 422);
                }

                if ($checkClockTime->lt($minCI) || $checkClockTime->gt($maxCI)) {
                    return response()->json([
                        'errors' => ['message' => 'Your clock-in time is outside the allowed range for this day.']
                    ], 422);
                }

                $latitude_rule =  $ckSetting->latitude;
                $longitude_rule =  $ckSetting->longitude;
                $radius_rule = $ckSetting->radius;

                if (!$this->isWithinRadius(
                    $request->latitude,
                    $request->longitude,
                    $latitude_rule,
                    $longitude_rule,
                    $radius_rule
                ) && $request->ck_setting_name === "WFO" && $request->status === "Present") {
                    return response()->json([
                        'errors' => ['message' => 'Your location is outside the allowed radius.']
                    ], 422);
                }

                // check if the user is within the radius

                DB::beginTransaction();
                try {
                    $checkClock = CheckClock::create([
                        'employee_id' => $employee_id,
                        'submitter_id' => $user,
                        'ck_setting_id' => $ckSetting->id,
                        'check_clock_date' => $request->check_clock_date,
                        'status' => $request->status,
                        'status_approval' => in_array($request->status, ['Annual Leave', 'Sick Leave']) ? 'Pending' : 'Approved',
                        'reject_reason' => $request->reject_reason,
                    ]);

                    PresentDetail::create([
                        'ck_id' => $checkClock->id,
                        'check_clock_type' => 'in',
                        'check_clock_time' => $request->check_clock_time,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'evidence' => $evidencePath,
                    ]);

                    DB::commit();
                    return response()->json(['success' => ['message' => 'Check clock with clock-in recorded successfully.']], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
                }
            } elseif ($request->check_clock_type === 'out') {
                // $clockOutExists = PresentDetail::where('ck_id', $checkClock->id)
                //     ->where('check_clock_type', 'out')->exists();
                // if ($clockOutExists) {
                //     return response()->json(['errors' => ['message' => 'You have already clocked out.']], 422);
                // }

                // For 'out', find existing CheckClock for employee and date
                $checkClock = CheckClock::where('employee_id', $request->employee_id)
                    ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                    ->first();

                if (!$checkClock) {
                    return response()->json(['errors' => ['message' => 'No existing check clock found for clock-out. Please clock-in first.']], 422);
                }

                // Create PresentDetail with type 'out'
                PresentDetail::create([
                    'ck_id' => $checkClock->id,
                    'check_clock_type' => 'out',
                    'check_clock_time' => $request->check_clock_time,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'evidence' => $evidencePath,
                ]);

                return response()->json(['success' => ['message' => 'Clock-out recorded successfully.']], 201);
            }
        } else {
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('evidence_absent', 's3');
            }
            // Handle Sick Leave, Annual Leave as before
            $exists = CheckClock::where('employee_id', $request->employee_id)
                ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['message' => 'This employee have already check clock today']], 422);
            }

            $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                ->where('name', $request->ck_setting_name)->first();

            if (!$ckSetting) {
                return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
            }

            $userIsAdmin = Auth::user()->role == "admin";

            $statusApproval = $userIsAdmin
                ? 'Approved'
                : ($request->status_approval ?? 'Pending');

            DB::beginTransaction();
            try {
                $checkClock = CheckClock::create([
                    'employee_id' => $request->employee_id,
                    'submitter_id' => $user,
                    'ck_setting_id' => $ckSetting->id,
                    'check_clock_date' => $request->check_clock_date,
                    'status' => $request->status,
                    'status_approval' => $statusApproval,
                    'reject_reason' => $request->reject_reason,
                ]);

                AbsentDetail::create([
                    'ck_id' => $checkClock->id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'evidence' => $evidencePath,
                ]);

                DB::commit();
                return response()->json(['success' => ['message' => 'Check clock recorded successfully.']], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
            }
        }
    }

    private function isWithinRadius($userLat, $userLon, $targetLat, $targetLon, $radiusMeters): bool
    {
        $earthRadius = 6371; // in km

        $latFrom = deg2rad($userLat);
        $lonFrom = deg2rad($userLon);
        $latTo = deg2rad($targetLat);
        $lonTo = deg2rad($targetLon);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c * 1000; // meters

        return $distance <= $radiusMeters;
    }

    public function getServerTime(): JsonResponse
    {
        return response()->json([
            'serverTime' => Carbon::now()->toDateTimeString(), // Returns the current server time
        ]);
    }
}
