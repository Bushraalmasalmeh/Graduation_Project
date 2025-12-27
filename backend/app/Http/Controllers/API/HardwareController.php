<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Charger;
use App\Models\User;
use App\Models\UsageSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class HardwareController extends Controller
{
    protected NotificationService $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        // Force Jordan timezone for all date operations
        date_default_timezone_set('Asia/Amman');
        Carbon::setLocale('en');
    }

    // ========== START SESSION ==========
    public function startSession(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'uid'        => 'required|string'
        ]);

        // Get current time in Jordan timezone
        $now = Carbon::now('Asia/Amman');

        Log::info('=== START SESSION ===', [
            'current_jordan_time' => $now->format('Y-m-d H:i:s'),
            'current_utc_time' => $now->copy()->utc()->format('Y-m-d H:i:s'),
            'job_number' => $request->job_number,
            'uid' => $request->uid
        ]);

        $user = User::where('job_number', $request->job_number)->first();
        if (!$user) {
            return response()->json(['message' => 'USER_NOT_FOUND'], 404);
        }

        $activeSession = UsageSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($activeSession) {
            return response()->json(['message' => 'ACTIVE_SESSION_EXISTS'], 409);
        }

        // Find booking - compare dates in Jordan timezone
        $todayJordan = $now->format('Y-m-d');

        $booking = Booking::where('user_id', $user->id)
            ->where('UID', $request->uid)
            ->where('status', 'pending')
            ->first(); // Remove the whereDate filter temporarily for debugging

        if (!$booking) {
            // Debug: show what bookings exist
            $allBookings = Booking::where('user_id', $user->id)->get();
            Log::info('All bookings for user:', $allBookings->map(function ($b) {
                $startJordan = Carbon::parse($b->start_time)->setTimezone('Asia/Amman');
                $endJordan = Carbon::parse($b->end_time)->setTimezone('Asia/Amman');

                return [
                    'id' => $b->id,
                    'uid' => $b->UID,
                    'status' => $b->status,
                    'start_utc' => $b->start_time,
                    'start_jordan' => $startJordan->format('Y-m-d H:i:s'),
                    'end_utc' => $b->end_time,
                    'end_jordan' => $endJordan->format('Y-m-d H:i:s'),
                    'is_today' => $startJordan->isToday() ? 'YES' : 'NO'
                ];
            })->toArray());

            return response()->json([
                'message' => 'NO_BOOKING_FOUND',
                'user_job_number' => $user->job_number,
                'uid_requested' => $request->uid,
                'current_time_jordan' => $now->format('Y-m-d H:i:s')
            ], 404);
        }

        // Convert booking times to Jordan timezone for comparison
        $bookingStartJordan = Carbon::parse($booking->start_time)->setTimezone('Asia/Amman');
        $bookingEndJordan = Carbon::parse($booking->end_time)->setTimezone('Asia/Amman');

        Log::info('Booking times comparison:', [
            'booking_id' => $booking->id,
            'booking_start_utc' => $booking->start_time,
            'booking_start_jordan' => $bookingStartJordan->format('Y-m-d H:i:s'),
            'booking_end_utc' => $booking->end_time,
            'booking_end_jordan' => $bookingEndJordan->format('Y-m-d H:i:s'),
            'current_time_jordan' => $now->format('Y-m-d H:i:s'),
            'is_within_time' => $now->between($bookingStartJordan, $bookingEndJordan) ? 'YES' : 'NO'
        ]);

        // Check if booking is for today (Jordan time)
        if (!$bookingStartJordan->isToday()) {
            return response()->json([
                'message' => 'BOOKING_NOT_FOR_TODAY',
                'booking_date' => $bookingStartJordan->format('Y-m-d'),
                'today' => $now->format('Y-m-d')
            ], 403);
        }

        // Check if current time is within booking window
        if ($now->lt($bookingStartJordan)) {
            return response()->json([
                'message' => 'BOOKING_NOT_STARTED_YET',
                'booking_start' => $bookingStartJordan->format('Y-m-d H:i:s'),
                'current_time' => $now->format('Y-m-d H:i:s'),
                'minutes_until_start' => $now->diffInMinutes($bookingStartJordan)
            ], 403);
        }

        if ($now->gt($bookingEndJordan)) {
            return response()->json([
                'message' => 'BOOKING_EXPIRED',
                'booking_end' => $bookingEndJordan->format('Y-m-d H:i:s'),
                'current_time' => $now->format('Y-m-d H:i:s'),
                'minutes_since_end' => $now->diffInMinutes($bookingEndJordan)
            ], 403);
        }

        return DB::transaction(function () use ($booking, $user, $request, $now) {
            $booking->update([
                'status' => 'active',
                'actual_start_time' => $now->toDateTimeString()
            ]);

            $charger = Charger::where('UID', $request->uid)->first();
            if (!$charger) {
                return response()->json(['message' => 'CHARGER_NOT_FOUND'], 404);
            }

            $charger->update(['status' => 'busy']);

            $session = UsageSession::create([
                'booking_id'    => $booking->id,
                'user_id'       => $user->id,
                'charger_id'    => $charger->id,
                'status'        => 'active',
                'session_start' => $now,
            ]);

            Log::info('Session created successfully:', [
                'session_id' => $session->id,
                'user' => $user->name,
                'charger' => $charger->UID,
                'start_time' => $now->format('Y-m-d H:i:s')

            ]);

            $this->notificationService->notifyUser(
                $user->id,
                'Charging Started',
                'Your charging session has started.',
                'session'
            );
            return response()->json([
                'status'    => 'success',
                'message'   => 'START_CONFIRMED',
                'user_name' => $user->name,
                'session_id' => $session->id,
                'start_time_jordan' => $now->format('Y-m-d H:i:s')
            ], 200);
        });
    }
    // ========== STOP SESSION ==========
    public function stopSession(Request $request)
    {
        $request->validate([
            'uid' => 'required|string'
        ]);

        $now = Carbon::now('Asia/Amman');

        return DB::transaction(function () use ($request, $now) {
            // ابحث عن الحجز المرتبط بالـ UID
            $booking = Booking::where('UID', $request->uid)
                ->where('status', 'active')
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'NO_ACTIVE_BOOKING_FOUND',
                    'success' => false
                ], 404);
            }

            // ابحث عن الجلسة المرتبطة بهذا الحجز
            $session = UsageSession::where('booking_id', $booking->id)
                ->where('status', 'active')
                ->latest('session_start')
                ->first();

            if (!$session) {
                return response()->json([
                    'message' => 'NO_ACTIVE_SESSION_FOUND',
                    'success' => false
                ], 404);
            }

            // حساب المدة
            $sessionStart = Carbon::parse($session->session_start)->setTimezone('Asia/Amman');
            $duration = $now->diffInMinutes($sessionStart);

            // تحديث الجلسة
            $session->update([
                'session_end' => $now,
                'duration'    => $duration,
                'status'      => 'completed'
            ]);

            // تحديث الحجز
            $booking->update([
                'status' => 'completed',
                'actual_end_time' => $now->toDateTimeString()
            ]);


            // تحديث حالة الشاحن
            if ($session->charger) {
                $session->charger->update(['status' => 'available']);
            }
            $this->notificationService->notifyUser(
                $session->user_id,
                'Charging Ended',
                'Your charging session has ended.',
                'session'
            );
            return response()->json([
                'message' => 'SESSION_STOPPED_SUCCESSFULLY',
                'success' => true,
                'duration_minutes' => $duration,
                'session_start' => $sessionStart->format('Y-m-d H:i:s'),
                'session_end' => $now->format('Y-m-d H:i:s'),
                'timezone' => 'Asia/Amman'
            ], 200);
        });
    }
}
