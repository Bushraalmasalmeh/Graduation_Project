<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChargerStation;
use App\Models\Cabinet;
use App\Models\Charger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Create a booking for a user
     */
    public function createBooking($user, string $stationName, int $duration)
    {
        return DB::transaction(function () use ($user, $stationName, $duration) {
            $station = ChargerStation::where('station_name', $stationName)->first();

            if (! $station) {
                throw new \Exception('Station not found', 404);
            }

            // منتخب أول كابينة متاحة
            $cabinet = $station->cabinets()->where('status', 'available')->first();

            if (! $cabinet) {
                throw new \Exception('No available cabinets in this station', 400);
            }

            // اختر شاحن متاح داخل الكابينة
            $charger = $cabinet->chargers()->where('status', 'available')->first();

            if (! $charger) {
                throw new \Exception('No available chargers in this cabinet', 400);
            }

            // وقت البداية والنهاية
            $start = now();
            $end = now()->addMinutes($duration);
            $todayBooking = Booking::where('user_id', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($todayBooking) {
                throw new \Exception("عذراً، يسمح بحجز واحد فقط يومياً.");
            }
            // إنشاء الحجز
            $booking = Booking::create([
                'user_id'     => $user->id,
                'station_id'  => $station->id,
                'cabinet_id'  => $cabinet->id,
                'charger_id'  => $charger->id,
                'start_time'  => $start,
                'end_time'    => $end,
                'status'      => 'pending'
            ]);

            // تحديث حالة الشاحن والكابينة
            $charger->update(['status' => 'busy']);
            $cabinet->update(['status' => 'busy']);

            return [
                'booking' => $booking,
                'charger_uid' => $charger->uid,
                'station' => $station->station_name,
                'cabinet' => $cabinet->cabinet_number
            ];
        });
    }
    /**
     * Get user bookings
     */
    public function getUserBookings($user)
    {
        return Booking::where('user_id', $user->id)
            ->with([
                'station:id,station_name',
                'cabinet:id,cabinet_number',
                'charger:id,uid,charger_number',
            ])
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Cancel user booking
     */
    public function cancelBooking($user, $bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', $user->id)
            ->first();

        if (! $booking) {
            throw new \Exception('Booking not found', 404);
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Cannot cancel active/completed booking', 400);
        }

        $booking->update(['status' => 'cancelled']);

        // رجّع الشاحن والكابينة متاحين
        $booking->charger->update(['status' => 'available']);
        $booking->cabinet->update(['status' => 'available']);

        return $booking;
    }
}
