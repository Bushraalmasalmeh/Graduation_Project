<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartSessionRequest;
use App\Jobs\SendSessionEndReminder;
use App\Jobs\SendSessionStartReminder;
use App\Models\Booking;
use App\Models\UsageSession;
use App\Models\User;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartSessionController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function start(StartSessionRequest $request)
    {
        //Validation
        $data = $request->validate([
            'uid' => 'required|string',
            'job_number' => 'required|string|exists:users,job_number',
            'hours_requested' => 'required|numeric|in:0.5,1,2'
        ]);
        //enforce max session hours from .env
        $maxHours = env('MAX_SESSION_HOURS', 2);
        if ($data['hours_requested'] > $maxHours) {
            return response()->json(['message' => 'exceeds_max_hours'], 422);
        }
        return DB::transaction(function () use ($data) {
            //lock user by job_number
            $user = User::where('job_number', $data['job_number'])->lockForUpdate()->first();
            if (!$user) return response()->json(['message' => 'invalid_job_number'], 404);
            //check active session
            $active = UsageSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNull('session_end')
                ->first();

            if ($active) return response()->json(['message' => 'already_active_session'], 409);

            $booking = Booking::where('UID', $data['uid'])
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$booking) return response()->json(['message' => 'no_booking_for_this_uid'], 404);

            $charger = $booking->charger;
            if (!$charger) return response()->json(['message' => 'booking_has_no_charger'], 400);

            $now = now();
            if ($now->lt($booking->start_time)) return response()->json(['message' => 'booking_not_started_yet'], 403);
            if ($now->gt($booking->end_time)) return response()->json(['message' => 'booking_expired'], 403);
            //create session
            $session = UsageSession::create([
                'user_id'       => $user->id,
                'charger_id'    => $charger->id,
                'booking_id'    => $booking->id,
                'session_start' => $now,
                'status'        => 'active'
            ]);
            // update booking + charger
            $booking->update(['status' => 'active']);
            $charger->update(['status' => 'busy']);
            //schedule reminders
            SendSessionStartReminder::dispatch($session->id)->delay($session->session_start->addMinutes(5));
            if ($booking->end_time) {
                SendSessionEndReminder::dispatch($session->id)->delay($booking->end_time->subMinutes(15));
            }
            //send notification
            try {
                $this->notificationService->chargingStarted($session);
            } catch (\Exception $e) {
                Log::error("Notification failed for session {$session->id}: " . $e->getMessage());
            }
            return response()->json([
                'message' => 'session_started',
                'session' => $session
            ]);
        });
    }
}
