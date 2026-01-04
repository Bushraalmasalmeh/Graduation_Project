<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChargerStation;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * توليد جدول الجلسات اليومية لكل محطة
     */
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
                    'end_time' => $start->copy()->addHours(2),
                ], [
                    'status' => 'available',
                    'created_by' => 'system',
                ]);

                $start->addHours(2);
            }
        }
    }

    /**
     * إعادة بناء الجدول من نهاية حجز معين
     */
    public function rebuildScheduleFrom($stationId, Carbon $fromTime)
    {
        $station = ChargerStation::findOrFail($stationId);

        // حذف الجلسات القادمة
        Booking::where('station_id', $station->id)
            ->where('start_time', '>=', $fromTime)
            ->delete();

        $start = $fromTime->copy();
        $end = Carbon::today()->setTime(20, 0);

        while ($start->lt($end)) {
            $nextEnd = $start->copy()->addHours(2);

            if ($nextEnd->gt($end)) {
                break;
            }

            Booking::create([
                'station_id' => $station->id,
                'start_time' => $start,
                'end_time' => $nextEnd,
                'status' => 'available',
                'created_by' => 'system',
            ]);

            $start = $nextEnd;
        }
    }
}
