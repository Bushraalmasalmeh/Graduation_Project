<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargerStation;

class StationController extends Controller
{
    public function index()
    {
        $stations = ChargerStation::with('cabinets.chargers')->get();

        return response()->json([
            'stations' => $stations
        ]);
    }

    public function show($id)
    {
        $station = ChargerStation::with('cabinets.chargers')->findOrFail($id);

        return response()->json([
            'station' => $station
        ]);
    }
}
