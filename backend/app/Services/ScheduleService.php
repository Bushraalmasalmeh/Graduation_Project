<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChargerStation;
use Carbon\Carbon;

class ScheduleService
{
    public function generateDailySchedule()
    {
        $stations = ChargerStation::all();
        foreach ($stations as $station) {
            $start = Carbon::today()->setTime(8, 0);
            $end = Carbon::today()->setTime(20, 0);
            while ($start->lt($end)) {
                Booking::firstOrCreate([
                    'station_id' => $station->id,
                    'start_time' => $start,
                    'end_time'   => $start->copy()->addHours(2),
                ], [
                    'status'     => 'pending',
                    'created_by' => 'system',
                ]);
                $start->addHours(2);
            }
        }
    }

    public function rebuildScheduleFrom($stationId, Carbon $fromTime)
    {
        $station = ChargerStation::findOrFail($stationId);
        Booking::where('station_id', $station->id)
            ->where('start_time', '>=', $fromTime)
            ->delete();

        $start = $fromTime->copy();
        $end = Carbon::today()->setTime(20, 0);

        while ($start->lt($end)) {
            $nextEnd = $start->copy()->addHours(2);
            if ($nextEnd->gt($end)) break;

            Booking::create([
                'station_id' => $station->id,
                'start_time' => $start,
                'end_time'   => $nextEnd,
                'status'     => 'pending',
                'created_by' => 'system',
            ]);
            $start = $nextEnd;
        }
    }
}
