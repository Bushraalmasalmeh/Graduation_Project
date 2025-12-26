<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminSettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache; // ✅ ADD THIS

class AdminSettingsController extends Controller
{
    public function show()
    {
        return response()->json([
            'settings' => Setting::first()
        ]);
    }

    public function update(AdminSettingsRequest $request)
    {
        $settings = Setting::first();
        $settings->update($request->validated());

        Cache::forget('maintenance_mode');

        return response()->json([
            'message'  => 'Settings updated',
            'settings' => $settings
        ]);
    }
}
