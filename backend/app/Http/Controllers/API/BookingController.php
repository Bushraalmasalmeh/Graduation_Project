<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateBookingRequest;
use App\Http\Requests\Api\CancelBookingRequest;
use App\Jobs\SendSessionStartReminder;
use App\Models\Booking;
use App\Models\Cabinet;
use App\Models\Charger;
use App\Models\ChargerStation;
use App\Models\Setting;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $user = request()->user();
        $bookings = Booking::with(['station', 'cabinet', 'charger'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['bookings' => $bookings]);
    }

    public function store(CreateBookingRequest $request)
    {
        $startTime = Carbon::parse($request->start_time, 'Asia/Amman');
        $now = Carbon::now('Asia/Amman');

        return DB::transaction(function () use ($request, $startTime, $now) {
            $user = $request->user();

            // 1. Booking must be for today
            if (!$startTime->isSameDay($now)) {
                return response()->json([
                    'message' => 'Booking must be for today only.',
                    'code' => 'INVALID_BOOKING_DATE'
                ], 422);
            }

            // 2. Booking must be within the next 30 minutes
            $diffInMinutes = $startTime->diffInMinutes($now, false);
            if ($diffInMinutes < 0 || $diffInMinutes > 30) {
                return response()->json([
                    'message' => 'Booking must be within the next 30 minutes.',
                    'code' => 'TOO_EARLY'
                ], 422);
            }

            // 3. Working hours check (7 AM - 8 PM)
            if ($startTime->hour < 7 || $startTime->hour >= 20) {
                return response()->json([
                    "message" => "Booking must be between 07:00 AM and 08:00 PM.",
                    "code"    => "OUT_OF_WORKING_HOURS"
                ], 422);
            }

            // 4. Check if user already has a booking today
            $alreadyBooked = Booking::where('user_id', $user->id)
                ->whereDate('start_time', $now->toDateString())
                ->whereIn('status', ['pending', 'active', 'confirmed'])
                ->lockForUpdate()
                ->exists();

            if ($alreadyBooked) {
                return response()->json([
                    'message' => 'You already have a booking today.',
                    'code'    => 'DAILY_LIMIT_REACHED'
                ], 422);
            }

            // 5. Station lookup
            $station = ChargerStation::whereRaw('LOWER(station_name) = ?', [strtolower(trim($request->station_name))])
                ->where('status', 'active')
                ->first();

            if (!$station) {
                return response()->json(['message' => 'Station not found.'], 404);
            }

            // 6. Cabinet and charger availability
            $cabinet = Cabinet::where('station_id', $station->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (!$cabinet) {
                return response()->json(['message' => 'No available cabinets.'], 422);
            }

            $charger = Charger::where('cabinet_id', $cabinet->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (!$charger) {
                return response()->json(['message' => 'No available chargers.'], 422);
            }

            // 7. Create booking
            $booking = Booking::create([
                'user_id'    => $user->id,
                'station_id' => $station->id,
                'cabinet_id' => $cabinet->id,
                'charger_id' => $charger->id,
                'UID'        => $charger->uid,
                'start_time' => $startTime,
                'end_time'   => $startTime->copy()->addMinutes($request->duration_minutes),
                'duration'   => $request->duration_minutes,
                'status'     => 'pending',
            ]);

            // 8. Schedule reminder
            if ($booking->start_time) {
                SendSessionStartReminder::dispatch($booking->id)
                    ->delay($booking->start_time->subMinutes(30));
            }

            // 9. Mark charger as busy
            $charger->update(['status' => 'busy']);

            // 10. Notify user
            try {
                $this->notificationService->notifyUser(
                    $booking->user_id,
                    'Booking Created',
                    'Your booking is confirmed.',
                    'booking'
                );
            } catch (\Exception $e) {
                Log::error("Notification failed for booking {$booking->id}: " . $e->getMessage());
            }

            return response()->json([
                "message"     => "booking created successfully",
                "uid"         => $booking->UID,
                "booking_id"  => $booking->id,
                "cabinet_id"  => $booking->cabinet_id,
                "charger_id"  => $booking->charger_id,
                "user_id"     => $booking->user_id,
                "start_time"  => $booking->start_time->copy()->setTimezone('Asia/Amman')->toIso8601String(),
                "duration"    => $booking->duration,
                "status"      => $booking->status,
                "updated_at"  => $booking->updated_at->toIso8601String(),
                "created_at"  => $booking->created_at->toIso8601String(),
            ], 200);
        });
    }

    public function cancel(CancelBookingRequest $request)
    {
        $user = $request->user();

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'No eligible booking found.'], 404);
        }

        $booking->update(['status' => 'cancelled']);

        try {
            $this->notificationService->notifyUser(
                $booking->user_id,
                'Booking Cancelled',
                'Your booking has been cancelled.',
                'booking'
            );
        } catch (\Exception $e) {
            Log::error("Notification failed: " . $e->getMessage());
        }

        return response()->json(['message' => 'Booking cancelled successfully.'], 200);
    }

    public function stopMobile(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,id']);
        $booking = Booking::with('charger')->find($request->booking_id);

        if (!$booking || $booking->status !== 'active') {
            return response()->json(['message' => 'No active session found.'], 422);
        }

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'completed']);
            return response()->json(['message' => 'Charging stopped successfully.'], 200);
        });
    }
}
