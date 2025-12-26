<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminStationRequest;
use App\Models\ChargerStation;

class AdminStationController extends Controller
{
    public function index()
    {
        return response()->json([
            'stations' => ChargerStation::with('cabinets.chargers')->get()
        ]);
    }

    public function store(AdminStationRequest $request)
    {
        $data = $request->validated();

        $station = ChargerStation::create([
            'station_code'   => $data['station_code'],
            'station_name'   => $data['station_name'],
            'location'       => $data['location'],
            'department'     => $data['department'],
            'total_cabinets' => $data['total_cabinets'],
            'status'         => $data['status'],
            'description'    => $data['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Station created',
            'station' => $station
        ], 201);
    }

    public function update(AdminStationRequest $request, $id)
    {
        $station = ChargerStation::findOrFail($id);
        $station->update($request->validated());

        return response()->json([
            'message' => 'Station_updated',
            'station' => $station
        ]);
    }

    public function destroy($id)
    {
        ChargerStation::findOrFail($id)->delete();

        return response()->json(['message' => 'Station_deleted']);
    }
}
