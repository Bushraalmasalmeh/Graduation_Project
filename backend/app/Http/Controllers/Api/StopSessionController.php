<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StopSessionRequest;
use App\Models\UsageSession;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StopSessionController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function stop(StopSessionRequest $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($user) {

            $session = UsageSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNull('session_end')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return response()->json(['message' => 'no_active_session'], 404);
            }

            $booking = $session->booking;
            $charger = $session->charger;

            if (!$booking || !$charger) {
                return response()->json(['message' => 'invalid_session_structure'], 500);
            }

            if (now()->gt($booking->end_time)) {

                $session->session_end = $booking->end_time;
                $session->duration_minutes = $session->session_start->diffInMinutes($booking->end_time);
                $session->status = 'expired';
                $session->save();

                $booking->status = 'expired';
                $booking->save();

                $charger->status = 'available';
                $charger->save();

                try {
                    $this->notificationService->chargingStopped($session);
                } catch (\Exception $e) {
                    Log::error("Notification failed for session {$session->id}: " . $e->getMessage());
                }

                return response()->json([
                    'message' => 'session_expired_before_stop',
                    'duration_minutes' => $session->duration_minutes,
                    'status' => 'expired'
                ]);
            }

            $end = now();
            $duration = $session->session_start->diffInMinutes($end);

            $session->update([
                'session_end'      => $end,
                'duration_minutes' => $duration,
                'status'           => 'completed'
            ]);

            $booking->update([
                'status'           => 'completed',
                'end_time'         => $end,
                'duration_minutes' => $duration
            ]);

            $charger->update(['status' => 'available']);

            try {
                $this->notificationService->chargingStopped($session);
            } catch (\Exception $e) {
                Log::error("Notification failed for session {$session->id}: " . $e->getMessage());
            }

            return response()->json([
                'message'          => 'session_stopped',
                'duration_minutes' => $duration,
                'status'           => 'completed'
            ]);
        });
    }
}
