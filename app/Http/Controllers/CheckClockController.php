<?php

namespace App\Http\Controllers;

use App\Models\CheckClock;
use \App\Models\AccessToken;
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
        $checkClocks = CheckClock::select(
            'check_clocks.employee_id',
            DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) AS employeeName"),
            'positions.name AS position',
            'check_clocks.check_clock_date AS date',
            DB::raw("MAX(CASE WHEN check_clocks.check_clock_type = 'in' THEN check_clocks.check_clock_time ELSE NULL END) AS clockIn"),
            DB::raw("MAX(CASE WHEN check_clocks.check_clock_type = 'out' THEN check_clocks.check_clock_time ELSE NULL END) AS clockOut"),
            'check_clock_settings.name AS workType',
            DB::raw("MAX(CASE 
                WHEN check_clocks.check_clock_type = 'in' AND check_clocks.check_clock_time > check_clock_setting_times.clock_in THEN 'Late'
                WHEN check_clocks.check_clock_type = 'in' AND check_clocks.check_clock_time <= check_clock_setting_times.clock_in THEN 'On Time'
                ELSE NULL 
            END) AS status"),
            DB::raw("MAX(check_clocks.status) AS approvalStatus")
        )
            ->join('employees', 'check_clocks.employee_id', '=', 'employees.id')
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->join('check_clock_settings', 'employees.ck_setting_id', '=', 'check_clock_settings.id')
            ->join('check_clock_setting_times', 'check_clock_setting_times.ck_setting_id', '=', 'check_clock_settings.id')
            ->groupBy(
                'check_clocks.employee_id',
                'employees.first_name',
                'employees.last_name',
                'positions.name',
                'check_clocks.check_clock_date',
                'check_clock_settings.name'
            )
            ->orderBy('check_clocks.check_clock_date')
            ->orderBy(DB::raw("CONCAT(employees.first_name, ' ', employees.last_name)"))
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
            'check_clock_type' => 'required|string|in:in,out,break_start,break_end,permit,sick,leave', // Enum validation for 'in' and 'out'
            'check_clock_date' => 'required|date',
            'check_clock_time' => 'required|date_format:H:i:s', // Corrected to validate time format
            'latitude' => 'sometimes|string',
            'longitude' => 'sometimes|string',
            'evidence' => 'sometimes|string',
            'status' => 'required|string|in:pending,approved,rejected', // Enum validation for status
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $checkClock = CheckClock::create($request->all());
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
}
