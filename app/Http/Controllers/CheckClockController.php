<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use \App\Models\AccessToken;
use App\Models\CheckClockSettingTimes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class CheckClockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $checkClocks = DB::table('check_clocks as cc')
        ->join('employees as e', 'cc.employee_id', '=', 'e.id')
        ->join('positions as p', 'e.position_id', '=', 'p.id')
        ->join('check_clock_settings as ccs', 'e.ck_setting_id', '=', 'ccs.id')
        ->join('check_clock_setting_times as ccst', 'ccst.ck_setting_id', '=', 'ccs.id')
        ->leftJoin('present_detail_cc as pdc', 'pdc.ck_id', '=', 'cc.id')
        ->leftJoin('absent_detail_cc as adc', function($join) {
            $join->on('adc.ck_id', '=', 'cc.id')
                ->whereRaw('cc.check_clock_date BETWEEN adc.start_date AND adc.end_date');
        })
        ->groupBy([
            'cc.id',
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
            'cc.employee_id',
            DB::raw("CONCAT(e.first_name, ' ', e.last_name) as employee_name"),
            'p.name as position',
            'cc.check_clock_date as date',
            DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.check_clock_time END) as clock_in'),
            DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'out\' THEN pdc.check_clock_time END) as clock_out'),
            'ccs.name as work_type',
            DB::raw('CASE 
                WHEN MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.check_clock_time END) IS NULL THEN cc.status
                WHEN MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.check_clock_time END) < MIN(ccst.clock_in) THEN \'Late\'
                ELSE \'On Time\'
            END as status'),
            DB::raw('MAX(cc.status_approval) as approval_status'),
            DB::raw('MAX(CASE WHEN adc.start_date IS NOT NULL THEN adc.start_date END) as absent_start_date'),
            DB::raw('MAX(CASE WHEN adc.end_date IS NOT NULL THEN adc.end_date END) as absent_end_date'),
            DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.latitude END) as latitude'),
            DB::raw('MAX(CASE WHEN pdc.check_clock_type = \'in\' THEN pdc.longitude END) as longitude')
        ])
        ->orderBy('cc.check_clock_date')
        ->orderBy('employee_name')
        ->get();
        return response()->json($checkClocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'approver_id' => 'sometimes|nullable|exists:employees,id',
            'check_clock_date' => 'required|date',
            'check_clock_type' => 'sometimes|string|in:in,out',
            'check_clock_time' => 'required|date_format:H:i:s',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'latitude' => 'sometimes|string',
            'longitude' => 'sometimes|string',
            'evidence' => 'sometimes|string',
            'reject_reason' => 'sometimes|string|max:255',
            'status' => 'required|string|in:Present,Sick Leave,Annual Leave',
            'status_approval' => 'required|string|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $checkClock = CheckClock::create([
            'employee_id' => $request->employee_id,
            'approver_id' => $request->approver_id,
            'check_clock_type' => $request->check_clock_type,
            'check_clock_date' => $request->check_clock_date,
            'status' => $request->status,
            'status_approval' => $request->status_approval,
        ]);

        $checkClockSettingTimes = CheckClockSettingTimes::where('latitude', $request->latitude);
        return response()->json($checkClock, 201);
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
        if ($validator['reject_reason']){
            $record->reject_reason = $request->reject_reason;
        } else {
            $record->reject_reason = "No Reason Provided"; 
        }
        $record->save();

        return response()->json(['message' => 'Check clock status updated successfully']);
    }
}
