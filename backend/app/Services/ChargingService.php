<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Charger;
use App\Models\UsageSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChargingService
{
    /**
     * Start charging session
     */
    public function startSession($user, string $chargerUid)
    {
        $charger = Charger::where('uid', $chargerUid)->first();

        if (! $charger) {
            throw new \Exception('Charger not found', 404);
        }

        // لازم يكون في booking pending عند المستخدم لنفس الشاحن
        $booking = Booking::where('user_id', $user->id)
            ->where('charger_id', $charger->id)
            ->where('status', 'pending')
            ->first();

        if (! $booking) {
            throw new \Exception('No pending booking found for this charger.', 400);
        }

        // تأكيد عدم وجود جلسة نشطة
        $existing = UsageSession::where('booking_id', $booking->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            throw new \Exception('Session already active.', 400);
        }

        // إنطلاق الجلسة
        $session = UsageSession::create([
            'user_id'     => $user->id,
            'booking_id'  => $booking->id,
            'session_start' => now(),
            'status'        => 'active'
        ]);

        // تحديث حالة الشاحن والكابينة
        $charger->update(['status' => 'busy']);
        $booking->cabinet->update(['status' => 'busy']);

        // تحديث حالة الحجز
        $booking->update(['status' => 'active']);

        return [
            'session_id' => $session->id,
            'charger_uid' => $charger->uid,
            'started_at' => $session->session_start,
        ];
    }

    /**
     * Stop charging session
     */
    public function stopSession($user, string $chargerUid)
    {
        $charger = Charger::where('uid', $chargerUid)->first();

        if (! $charger) {
            throw new \Exception('Charger not found', 404);
        }

        // جلب الحجز النشط
        $booking = Booking::where('user_id', $user->id)
            ->where('charger_id', $charger->id)
            ->where('status', 'active')
            ->first();

        if (! $booking) {
            throw new \Exception('No active booking found for this charger.', 400);
        }

        // الجلسة النشطة
        $session = UsageSession::where('booking_id', $booking->id)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            throw new \Exception('No active charging session found.', 400);
        }

        $sessionEnd = now();
        $duration = $session->session_start->diffInMinutes($sessionEnd);



        // تحديث الجلسة
        $session->update([
            'session_end'        => $sessionEnd,
            'duration_minutes'   => $duration,
            'status'             => 'completed'
        ]);

        // تحرير الشاحن والكابينة
        $charger->update(['status' => 'available']);
        $booking->cabinet->update(['status' => 'available']);

        // إغلاق الحجز
        $booking->update(['status' => 'completed']);

        return [
            'session_id' => $session->id,
            'duration_minutes' => $duration,
            'ended_at' => $sessionEnd
        ];
    }
}
