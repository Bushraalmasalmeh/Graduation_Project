<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminStationRequest;
use App\Models\ChargerStation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        DB::beginTransaction();

        try {
            $station = ChargerStation::create([
                'station_code'   => $data['station_code'],
                'station_name'   => $data['station_name'],
                'location'       => $data['location'],
                'department'     => $data['department'],
                'total_cabinets' => $data['total_cabinets'],
                'status'         => $data['status'],
                'description'    => $data['description'] ?? null,
                'charger_number' => $data['charger_number'] ?? ($data['total_cabinets'] * 2),
            ]);

            for ($i = 1; $i <= $data['total_cabinets']; $i++) {
                $cabinet = $station->cabinets()->create([
                    'name'           => 'Cabinet ' . $i,
                    'cabinet_number' => $i,
                    'status'         => 'available',
                    'total_chargers' => 2,
                ]);

                for ($j = 1; $j <= 2; $j++) {
                    $cabinet->chargers()->create([
                        'name'           => 'Charger ' . $j,
                        'status'         => 'available',
                        'charger_number' => $j,
                        'uid'            => Str::uuid(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Station created with cabinets and chargers',
                'station' => $station->load('cabinets.chargers')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create station',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(AdminStationRequest $request, $id)
    {
        $station = ChargerStation::findOrFail($id);
        $station->update($request->validated());

        return response()->json([
            'message' => 'Station updated',
            'station' => $station
        ]);
    }

    public function destroy($id)
    {
        ChargerStation::findOrFail($id)->delete();

        return response()->json(['message' => 'Station deleted']);
    }
}
