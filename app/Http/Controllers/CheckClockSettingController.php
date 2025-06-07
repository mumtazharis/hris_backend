<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSetting;
use Illuminate\Http\Request;

class CheckClockSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = CheckClockSetting::all();
        return response()->json($settings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'radius' => 'nullable|string',
        ]);

        $setting = CheckClockSetting::create($validatedData);

        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CheckClockSetting $checkClockSetting)
    {
        return response()->json($checkClockSetting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'data_id' => 'required|integer|exists:check_clock_settings,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
            ]);

            $record = CheckClockSetting::findOrFail($validatedData['data_id']);
            
            $record->fill([
                'latitude' => $validatedData['latitude'] ?? $record->latitude,
                'longitude' => $validatedData['longitude'] ?? $record->longitude,
                'radius' => $validatedData['radius'] ?? $record->radius,
            ]);

            if ($record->save()) {
                return response()->json([
                    'success' => ['message' => 'Successfully update the location'],
                    'data' => $record
                ], 200);
            }

            return response()->json([
                'errors' => ['message' => 'Failed to update the data']
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'errors' => ['message' => 'Record not found']
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['message' => 'Failed to update the data: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckClockSetting $checkClockSetting)
    {
        $checkClockSetting->delete();

        return response()->json(['message' => 'Resource deleted successfully']);
    }
}
