<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartSessionRequest;
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
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            $user = User::where('job_number', $data['job_number'])->lockForUpdate()->first();
            if (!$user) return response()->json(['message' => 'invalid_job_number'], 404);

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

            $session = UsageSession::create([
                'user_id'       => $user->id,
                'charger_id'    => $charger->id,
                'booking_id'    => $booking->id,
                'session_start' => $now,
                'status'        => 'active'
            ]);

            $booking->update(['status' => 'active']);
            $charger->update(['status' => 'busy']);

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
