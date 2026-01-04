<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminCabinetRequest;
use App\Models\Cabinet;
use Illuminate\Http\JsonResponse;

class AdminCabinetController extends Controller
{
    public function index(): JsonResponse
    {
        $cabinets = Cabinet::all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cabinets' => $cabinets
            ]
        ]);
    }

    public function store(AdminCabinetRequest $request)
    {
        $cabinet = Cabinet::create($request->validated());

        return response()->json([
            'message' => 'Cabinet_created',
            'cabinet' => $cabinet
        ], 201);
    }

    public function update(AdminCabinetRequest $request, $id)
    {
        $cabinet = Cabinet::findOrFail($id);
        $cabinet->update($request->validated());

        return response()->json([
            'message' => 'Cabinet updated',
            'cabinet' => $cabinet
        ]);
    }

    public function destroy($id)
    {
        Cabinet::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Cabinet_deleted'
        ]);
    }
}
