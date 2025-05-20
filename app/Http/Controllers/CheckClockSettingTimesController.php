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
        $times = CheckClockSettingTimes::whereNull('deleted_at')
        ->orderBy('created_at', 'desc')
        ->distinct()
        ->get();
        if ($times) {
            return response()->json($times);
        }
        return response()->json(['errors' => ['message' => 'Failed to feth the data']], 401);
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

        if ($time){
            return response()->json(['success' => ['message' => 'Successfully update the data']], 200);
        }

        return response()->json(['error' => ['message' => 'Failed to update the data']], 401);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = CheckClockSettingTimes::find($id);

        if ($record) {
            return response()->json($record);
        }
        return response()->json(['error' => ['message' => 'Failed to get the data']], 401);
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
    public function destroy($id)
    {
        $data = CheckClockSettingTimes::find($id);
        if ($data) {

            $data->delete();
            return response()->json(['errors' => ['message' => 'The Setting Times have been removed']], 200);
        }
        return response()->json(['errors' => ['message' => 'Cannot find the data']], 401);

    }
}
