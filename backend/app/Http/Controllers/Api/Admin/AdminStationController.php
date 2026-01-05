<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminStationRequest;
use App\Models\ChargerStation;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            // إنشاء المحطة
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

            // إنشاء الكابينات والشواحن
            for ($i = 1; $i <= $data['total_cabinets']; $i++) {
                $cabinet = $station->cabinets()->create([
                    'name'           => 'Cabinet ' . $i,
                    'cabinet_number' => $i,
                    'status'         => 'available',
                    'total_chargers' => 2,
                ]);

                for ($j = 1; $j <= 2; $j++) {
                    $chargerCode = $data['station_code'] . $i . $j;
                    $uid = 'UID-' . $chargerCode;

                    // ✅ تحقق من التكرار
                    $exists = \App\Models\Charger::where('uid', $uid)->exists();
                    if ($exists) {
                        throw new \Exception("Charger UID already exists: " . $uid);
                    }

                    $cabinet->chargers()->create([
                        'name'           => 'Charger ' . $j,
                        'status'         => 'available',
                        'charger_number' => $j,
                        'code'           => $chargerCode,
                        'uid'            => $uid,
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

    // ✅ حذف محطة بدون شروط
    public function destroy($id)
    {
        $station = ChargerStation::findOrFail($id);
        $station->delete();

        return response()->json(['message' => 'Station deleted']);
    }

    // ✅ جدول زمني للجلسات المرتبطة بمحطة
    public function getStationSchedule($id)
    {
        $station = ChargerStation::find($id);
        if (!$station) {
            return response()->json(['message' => 'STATION_NOT_FOUND'], 404);
        }

        $bookings = Booking::with('user')
            ->where('charger_station_id', $station->id)
            ->orderBy('start_time')
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->user->name ?? 'Unassigned',
                'start' => Carbon::parse($booking->start_time)->toIso8601String(),
                'end' => Carbon::parse($booking->end_time)->toIso8601String(),
                'status' => $booking->status,
                'color' => match ($booking->status) {
                    'pending' => '#f39c12',
                    'active' => '#3498db',
                    'completed' => '#2ecc71',
                    default => '#bdc3c7'
                }
            ];
        });

        return response()->json($data);
    }

    // ✅ حذف أي حجز لأي مستخدم بدون شروط
    public function deleteBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted by admin']);
    }
}
