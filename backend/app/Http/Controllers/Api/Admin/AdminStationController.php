<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminStationRequest;
use App\Models\ChargerStation;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
                    $chargerCode = $data['station_code'] . $i . $j;
                    $uid = 'UID-' . $chargerCode;

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

    public function destroy($id)
    {
        $station = ChargerStation::findOrFail($id);
        $station->delete();

        return response()->json(['message' => 'Station deleted']);
    }

    public function getStationSchedule($id)
    {
        $station = ChargerStation::find($id);
        if (!$station) {
            return response()->json(['message' => 'STATION_NOT_FOUND'], 404);
        }

        $bookings = Booking::with('user')
            ->where('station_id', $station->id)
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

    public function deleteBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted by admin']);
    }
    public function getAvailability(Request $request, $stationId)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'duration' => 'required|integer|in:60,90,120',
        ]);

        $station = \App\Models\ChargerStation::with('cabinets.chargers')->findOrFail($stationId);

        $startOfDay = Carbon::parse($request->date . ' 08:00');
        $endOfDay = Carbon::parse($request->date . ' 20:00');

        $chargerIds = $station->cabinets->flatMap->chargers->pluck('id');
        $bookings = \App\Models\Booking::whereIn('charger_id', $chargerIds)
            ->whereDate('start_time', $request->date)
            ->whereIn('status', ['pending', 'active'])
            ->get()
            ->groupBy('charger_id');

        $bufferMinutes = 5;
        $slotStep = 15;
        $duration = (int)$request->duration;
        $response = [];

        foreach ($station->cabinets as $cabinet) {
            foreach ($cabinet->chargers as $charger) {
                $taken = $bookings->get($charger->id, collect())->map(function ($b) use ($bufferMinutes) {
                    return [
                        'start' => Carbon::parse($b->start_time)->subMinutes($bufferMinutes),
                        'end' => Carbon::parse($b->end_time)->addMinutes($bufferMinutes),
                    ];
                });

                $slots = [];
                $cursor = $startOfDay->copy();
                while ($cursor->copy()->addMinutes($duration) <= $endOfDay) {
                    $slotStart = $cursor->copy();
                    $slotEnd = $cursor->copy()->addMinutes($duration);
                    $overlap = false;
                    foreach ($taken as $t) {
                        if ($slotStart < $t['end'] && $slotEnd > $t['start']) {
                            $overlap = true;
                            break;
                        }
                    }
                    if (!$overlap) {
                        $slots[] = [
                            'start' => $slotStart->toIso8601String(),
                            'end' => $slotEnd->toIso8601String(),
                            'duration_minutes' => $duration,
                        ];
                    }
                    $cursor->addMinutes($slotStep);
                }

                $response[] = [
                    'charger_id' => $charger->id,
                    'uid' => $charger->uid,
                    'code' => $charger->code ?? null,
                    'cabinet_id' => $cabinet->id,
                    'cabinet_num' => $cabinet->cabinet_number,
                    'status' => $charger->status,
                    'available_slots' => $slots,
                ];
            }
        }

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->station_name,
                'code' => $station->station_code,
            ],
            'date' => $request->date,
            'duration' => $duration,
            'chargers' => $response
        ]);
    }
    public function show($stationId)
    {
        $station = \App\Models\ChargerStation::with('cabinets.chargers')->findOrFail($stationId);
        return response()->json(['station' => $station]);
    }
    public function reportSummary($stationId)
    {
        $station = ChargerStation::with(['cabinets.chargers.bookings'])->findOrFail($stationId);

        $totalMinutes = $station->cabinets
            ->flatMap->chargers
            ->flatMap->bookings
            ->sum(function ($booking) {
                return $booking->duration ?? 0;
            });

        return response()->json([
            'station_id' => $station->id,
            'station_name' => $station->station_name,
            'total_usage_minutes' => $totalMinutes,
        ]);
    }
}
