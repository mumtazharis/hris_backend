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
        $times = CheckClockSettingTimes::select(
            'check_clock_setting_times.id as data_id',
            'check_clock_settings.name as worktype',
            'check_clock_setting_times.day',
            'check_clock_setting_times.clock_in',
            'check_clock_setting_times.clock_out',
            'check_clock_settings.latitude',
            'check_clock_settings.longitude',
            'check_clock_settings.radius',
            'check_clock_setting_times.created_at',
        )
            ->join('check_clock_settings', 'check_clock_setting_times.ck_setting_id', '=', 'check_clock_settings.id')
            ->whereNull('check_clock_setting_times.deleted_at')
            ->orderBy('check_clock_setting_times.created_at', 'desc')
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

        if ($time) {
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
        return response()->json(['errors' => ['message' => 'Failed to get the data']], 401);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $checkClockSettingTimes)
    {
        try {
            $validated = $request->validate([
                'clockIn' => 'required|date_format:H:i',
                'clockOut' => 'required|date_format:H:i|after:clock_in',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
            ]);

            $record = CheckClockSettingTimes::findOrFail($checkClockSettingTimes);
        
            if (!$record) {
                return response()->json(['errors' => ['message' => 'Record not found']], 404);
            }

            $record->clock_in = $validated['clockIn'];
            $record->clock_out = $validated['clockOut'];

            if (isset($validated['latitude'])) {
                $record->latitude = $validated['latitude'];
            }
            if (isset($validated['longitude'])) {
                $record->longitude = $validated['longitude'];
            }
            if (isset($validated['radius'])) {
                $record->radius = $validated['radius'];
            }
            if ($record->save()) {
                return response()->json(['success' => ['message' => 'Successfully update the data']], 200);
            } 
            return response()->json(['errors' => ['message' => 'Failed to update the data']], 400);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => 'Failed to update the data: ' . $e->getMessage()]], 401);
        }
        

        // return response()->json(['errors' => ['message' => 'Failed to update the data']], 401);
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
