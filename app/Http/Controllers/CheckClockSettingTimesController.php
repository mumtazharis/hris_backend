<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSettingTimes;
use Illuminate\Http\Request;

class CheckClockSettingTimesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $times = CheckClockSettingTimes::all();
        return response()->json($times);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ck_setting_id' => 'required|exists:check_clock_settings,id',
            'day' => 'required|string|max:255',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i|after:clock_in',
            'break_start' => 'required|date_format:H:i|after:clock_in|before:clock_out',
            'break_end' => 'required|date_format:H:i|after:break_start|before:clock_out',
        ]);

        $time = CheckClockSettingTimes::create($validated);

        return response()->json($time, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = CheckClockSettingTimes::find($id);
        return response()->json($record);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CheckClockSettingTimes $checkClockSettingTimes)
    {
        $validated = $request->validate([
            'ck_setting_id' => 'required|exists:check_clock_settings,id',
            'day' => 'required|string|max:255',
            'clock_in' => 'required|date_format:H:i:s',
            'clock_out' => 'required|date_format:H:i:s|after:clock_in',
            'break_start' => 'required|date_format:H:i:s|after:clock_in|before:clock_out',
            'break_end' => 'required|date_format:H:i:s|after:break_start|before:clock_out',
        ]);

        $checkClockSettingTimes->update($validated);
        
        return response()->json($validated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckClockSettingTimes $checkClockSettingTimes)
    {
        $checkClockSettingTimes->delete();

        return response()->json(null, 204);
    }
}
