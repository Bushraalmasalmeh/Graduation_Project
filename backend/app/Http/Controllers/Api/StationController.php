<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChargerStation;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
    public function getAvailability(Request $request, $stationId)
    {
        $station = ChargerStation::findOrFail($stationId);

        $duration = floatval($request->query('duration', 1));
        $slotMinutes = (int) ($duration * 60);

        $startHour = 7;
        $endHour = 20;
        $today = Carbon::today();

        $slots = [];

        for ($hour = $startHour; $hour <= $endHour - $duration; $hour += 0.5) {
            $from = $today->copy()->addMinutes($hour * 60);
            $to = $from->copy()->addMinutes($slotMinutes);

            $isBooked = Booking::where('station_id', $stationId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($from, $to) {
                    $query->whereBetween('start_time', [$from, $to])
                        ->orWhereBetween('end_time', [$from, $to])
                        ->orWhere(function ($q) use ($from, $to) {
                            $q->where('start_time', '<=', $from)
                                ->where('end_time', '>=', $to);
                        });
                })
                ->exists();

            $slots[] = [
                'from' => $from->format('H:i'),
                'to' => $to->format('H:i'),
                'status' => $isBooked ? 'busy' : 'available',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }
}
