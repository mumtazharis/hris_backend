<?php

namespace App\Http\Controllers;

use App\Models\AbsentDetail;
use App\Models\CheckClock;
use \App\Models\AccessToken;
use App\Models\CheckClockSettingTimes;
use App\Models\PresentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckClockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $checkClocks = DB::table('check_clocks as cc')
            ->join('employees as e', 'cc.employee_id', '=', 'e.id')
            ->join('positions as p', 'e.position_id', '=', 'p.id')
            ->join('check_clock_settings as ccs', 'cc.ck_setting_id', '=', 'ccs.id')
            ->join('check_clock_setting_times as ccst', 'ccst.ck_setting_id', '=', 'ccs.id')
            ->leftJoin('present_detail_cc as pdc', 'pdc.ck_id', '=', 'cc.id')
            // ->leftJoin('absent_detail_cc as adc', function ($join) {
            //     $join->on('adc.ck_id', '=', 'cc.id')
            //         ->whereRaw('cc.check_clock_date BETWEEN adc.start_date AND adc.end_date');
            // })
            ->leftJoin('absent_detail_cc as adc', 'adc.ck_id', '=', 'cc.id')
            ->where('e.company_id', $companyId)
            ->groupBy([
                'cc.id',
                'e.employee_id',
                'cc.employee_id',
                'e.first_name',
                'e.last_name',
                'p.name',
                'cc.check_clock_date',
                'ccs.name',
                'pdc.latitude',
                'pdc.longitude',
                'cc.status'
            ])
            ->select([
                'cc.id as data_id',
                'e.employee_id as employee_number',
                'cc.employee_id',
                DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
                'p.name as position',
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
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.latitude END) as latitude'),
                DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.longitude END) as longitude'),
                DB::raw('MAX(cc.reject_reason) as reject_reason')
            ])
            ->orderBy('cc.check_clock_date')
            ->orderBy('employee_name')
            ->get();
        return response()->json($checkClocks);
    }

    public function getEmployeeData()
    {
        $companyId = Auth::user()->company_id;

        $query = '
        SELECT 
            e.id AS data_id,
            e.employee_id AS id_employee,
            CONCAT(e.first_name, \' \', e.last_name) AS Name,
            ck.check_clock_date,
            p.name AS position,
            ccs.name AS workType,
            MAX(CASE WHEN pd.check_clock_type = \'in\' THEN pd.check_clock_time END) AS clock_in,
            MAX(CASE WHEN pd.check_clock_type = \'out\' THEN pd.check_clock_time END) AS clock_out
        FROM employees e
        JOIN positions p ON e.position_id = p.id
        LEFT JOIN (
            SELECT *
            FROM check_clocks
            WHERE DATE(check_clock_date) = CURRENT_DATE
        ) ck ON ck.employee_id = e.id
        LEFT JOIN present_detail_cc pd ON pd.ck_id = ck.id
        LEFT JOIN check_clock_settings ccs ON ccs.id = ck.ck_setting_id
        WHERE e.company_id = ?
        GROUP BY e.id, e.employee_id, e.first_name, e.last_name, p.name, ccs.name, ck.check_clock_date
        ORDER BY e.first_name ASC
            ';

        $data = DB::select($query, [$companyId]);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $user = Auth::user()->id;
        $companyId = Auth::user()->company_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'ck_setting_name' => 'nullable|in:WFA,WFO',
            'check_clock_date' => 'required|date',
            'status' => 'required|in:Present,Sick Leave,Annual Leave',
            'status_approval' => 'nullable|in:Approved,Pending,Rejected',
            'reject_reason' => 'nullable|string',

            'check_clock_type' => 'nullable|required_if:status,Present|in:in,out',
            'check_clock_time' => 'required_if:status,Present|date_format:H:i',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'evidence' => 'nullable|image|max:5120',

            'start_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date',
            'end_date' => 'nullable|required_if:status,Sick Leave,Annual Leave|date',
        ]);

        if ($request->status === 'Present') {
            if ($request->check_clock_type === 'in') {
                // Check if CheckClock exists for this employee and date (with any PresentDetail)
                $exists = CheckClock::where('employee_id', $request->employee_id)
                    ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                    ->exists();

                if ($exists) {
                    return response()->json(['errors' => ['message' => 'Check clock already exists for this employee on the same date.']], 422);
                }

                // Create CheckClock and PresentDetail for 'in'
                $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                    ->where('name', $request->ck_setting_name)->first();

                if (!$ckSetting) {
                    return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
                }

                DB::beginTransaction();
                try {
                    $checkClock = CheckClock::create([
                        'employee_id' => $request->employee_id,
                        'submitter_id' => $user,
                        'ck_setting_id' => $ckSetting->id,
                        'check_clock_date' => $request->check_clock_date,
                        'status' => $request->status,
                        'status_approval' => $request->status_approval ?? 'Pending',
                        'reject_reason' => $request->reject_reason,
                    ]);

                    PresentDetail::create([
                        'ck_id' => $checkClock->id,
                        'check_clock_type' => 'in',
                        'check_clock_time' => $request->check_clock_time,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'evidence' => $request->evidence,
                    ]);

                    DB::commit();
                    return response()->json(['success' => ['message' => 'Check clock with clock-in recorded successfully.']], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
                }
            } elseif ($request->check_clock_type === 'out') {
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
                    'evidence' => $request->evidence,
                ]);

                return response()->json(['success' => ['message' => 'Clock-out recorded successfully.']], 201);
            }
        } else {
            // Handle Sick Leave, Annual Leave as before
            $exists = CheckClock::where('employee_id', $request->employee_id)
                ->whereDate('check_clock_date', \Carbon\Carbon::parse($request->check_clock_date)->toDateString())
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['message' => 'Check clock already exists for this employee on the same date.']], 422);
            }

            $ckSetting = \App\Models\CheckClockSetting::where('company_id', $companyId)
                ->where('name', $request->ck_setting_name)->first();

            if (!$ckSetting) {
                return response()->json(['error' => ['message' => 'Invalid work type, should be either WFA or WFO.']], 422);
            }

            DB::beginTransaction();
            try {
                $checkClock = CheckClock::create([
                    'employee_id' => $request->employee_id,
                    'submitter_id' => $user,
                    'ck_setting_id' => $ckSetting->id,
                    'check_clock_date' => $request->check_clock_date,
                    'status' => $request->status,
                    'status_approval' => $request->status_approval ?? 'Pending',
                    'reject_reason' => $request->reject_reason,
                ]);

                AbsentDetail::create([
                    'ck_id' => $checkClock->id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'evidence' => $request->evidence,
                ]);

                DB::commit();
                return response()->json(['success' => ['message' => 'Check clock recorded successfully.']], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
            }
        }
    }

    public function reject(Request $request)
    {
        $request->validate([
            'data_id' => 'required|exists:check_clocks,id',
            'reject_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $checkClock = CheckClock::findOrFail($request->data_id);
            $checkClock->update([
                'reject_reason' => $request->reject_reason,
                'status_approval' => 'Rejected',
            ]);

            DB::commit();
            return response()->json(['success' => ['message' => 'Check clock rejected successfully.']], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CheckClock $checkClock)
    {
        return response()->json($checkClock);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CheckClock $checkClock)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required',
            'check_clock_type' => 'sometimes|required|string|in:in,out,break_start,break_ended,permit,sick,leave', // Enum validation for 'in' and 'out'
            'check_clock_date' => 'sometimes|required|date',
            'check_clock_time' => 'sometimes|required|date_format:H:i:s', // Corrected to validate time format
            'latitude' => 'sometimes|string',
            'longitude' => 'sometimes|string',
            'evidence' => 'sometimes|string',
            'status' => 'sometimes|required|string|in:pending,approved,rejected', // Enum validation for status
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $checkClock->update($request->all());
        return response()->json($checkClock);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckClock $checkClock)
    {
        $checkClock->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function approval(Request $request, string $checkClock)
    {
        $validator = $request->validate([
            'status_approval' => 'required|string|in:Approved,Pending,Rejected',
            'reject_reason' => 'nullable|string|max:255',
        ]);

        $record = CheckClock::findOrFail($checkClock);

        $record->status_approval = $request->status_approval;
        if ($validator['reject_reason']) {
            $record->reject_reason = $request->reject_reason;
        } else {
            $record->reject_reason = "No Reason Provided";
        }
        $record->save();

        return response()->json(['message' => 'Check clock status updated successfully']);
    }
}
