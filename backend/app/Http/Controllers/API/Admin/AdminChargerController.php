<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminChargerRequest;
use App\Models\Charger;

class AdminChargerController extends Controller
{
    public function store(AdminChargerRequest $request)
    {
        $data = $request->validated();

        // Generate UID: StationCode + Cabinet + ChargerNo
        $uid = $data['station_code'] . $data['cabinet_number'] . $data['charger_number'];

        $charger = Charger::create([
            'cabinet_id'     => $data['cabinet_id'],
            'charger_number' => $data['charger_number'],
            'uid'            => $uid,
            'status'         => $data['status'],
        ]);

        return response()->json([
            'message' => 'Charger_created',
            'charger' => $charger
        ], 201);
    }

    public function update(AdminChargerRequest $request, $id)
    {
        $charger = Charger::findOrFail($id);
        $charger->update($request->validated());

        return response()->json([
            'message' => 'Charger_updated',
            'charger' => $charger
        ]);
    }

    public function destroy($id)
    {
        Charger::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Charger_deleted'
        ]);
    }
}
