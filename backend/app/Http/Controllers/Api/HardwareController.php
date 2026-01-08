<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\UsageSession;
use App\Models\Charger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HardwareController extends Controller
{
    public function verifyJob(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'uid' => 'required|string',
        ]);

        $booking = Booking::with('user')
            ->where('UID', $request->uid)
            ->where('status', 'pending')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->whereHas('user', function ($q) use ($request) {
                $q->where('job_number', $request->job_number);
            })
            ->first();

        if (!$booking) {
            return response()->json(['status' => 'error', 'code' => 'NO_BOOKING']);
        }

        return response()->json([
            'status' => 'success',
            'user_name' => $booking->user->name
        ]);
    }

    public function startSession(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'uid'        => 'required|string'
        ]);

        $now = Carbon::now('Asia/Amman');

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

        $booking = Booking::where('user_id', $user->id)
            ->where('UID', $request->uid)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'NO_BOOKING_FOUND'], 404);
        }

        $bookingStart = Carbon::parse($booking->start_time)->setTimezone('Asia/Amman');
        $bookingEnd = Carbon::parse($booking->end_time)->setTimezone('Asia/Amman');

        if (!$bookingStart->isToday()) {
            return response()->json(['message' => 'BOOKING_NOT_FOR_TODAY'], 403);
        }

        if ($now->lt($bookingStart)) {
            return response()->json(['message' => 'BOOKING_NOT_STARTED_YET'], 403);
        }

        if ($now->gt($bookingEnd)) {
            return response()->json(['message' => 'BOOKING_EXPIRED'], 403);
        }

        return DB::transaction(function () use ($booking, $user, $request, $now) {
            $booking->update([
                'status' => 'active',
                'actual_start_time' => $now
            ]);

            $charger = Charger::where('UID', $request->uid)->first();
            if ($charger) {
                $charger->update(['status' => 'busy']);
            }

            $session = UsageSession::create([
                'booking_id'    => $booking->id,
                'user_id'       => $user->id,
                'charger_id'    => $charger?->id,
                'status'        => 'active',
                'session_start' => $now,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'START_CONFIRMED',
                'user_name' => $user->name,
                'session_id' => $session->id,
                'start_time_jordan' => $now->format('Y-m-d H:i:s'),
                'activate_charging' => true
            ]);
        });
    }

    public function stopSession(Request $request)
    {
        $request->validate([
            'uid' => 'required|string'
        ]);

        $now = Carbon::now('Asia/Amman');

        return DB::transaction(function () use ($request, $now) {
            $booking = Booking::where('UID', $request->uid)
                ->where('status', 'active')
                ->first();

            if (!$booking) {
                return response()->json(['message' => 'NO_ACTIVE_BOOKING_FOUND'], 404);
            }

            $session = UsageSession::where('booking_id', $booking->id)
                ->where('status', 'active')
                ->latest('session_start')
                ->first();

            if (!$session) {
                return response()->json(['message' => 'NO_ACTIVE_SESSION_FOUND'], 404);
            }

            $duration = $now->diffInMinutes(Carbon::parse($session->session_start));

            $session->update([
                'session_end' => $now,
                'duration'    => $duration,
                'status'      => 'completed'
            ]);

            $booking->update([
                'status' => 'completed',
                'actual_end_time' => $now
            ]);

            if ($session->charger) {
                $session->charger->update(['status' => 'available']);
            }

            return response()->json([
                'message' => 'SESSION_STOPPED_SUCCESSFULLY',
                'success' => true,
                'duration_minutes' => $duration
            ]);
        });
    }
}
