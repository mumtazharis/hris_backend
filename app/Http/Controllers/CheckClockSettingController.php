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
    public function update(Request $request, CheckClockSetting $checkClockSetting)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'radius' => 'nullable|string',
        ]);

        $checkClockSetting->update($validatedData);

        return response()->json($checkClockSetting);
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
