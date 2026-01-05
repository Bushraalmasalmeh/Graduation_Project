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

            if (!$startTime->isSameDay($now)) {
                return response()->json([
                    'message' => 'Booking must be for today only.',
                    'code' => 'INVALID_BOOKING_DATE'
                ], 422);
            }

            $diffInMinutes = $startTime->diffInMinutes($now, false);
            if ($diffInMinutes < 0 || $diffInMinutes > 30) {
                return response()->json([
                    'message' => 'Booking must be within the next 30 minutes.',
                    'code' => 'TOO_EARLY'
                ], 422);
            }

            if ($startTime->hour < 7 || $startTime->hour >= 20) {
                return response()->json([
                    "message" => "Booking must be between 07:00 AM and 08:00 PM.",
                    "code"    => "OUT_OF_WORKING_HOURS"
                ], 422);
            }

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

            $station = ChargerStation::whereRaw('LOWER(station_name) = ?', [strtolower(trim($request->station_name))])
                ->where('status', 'active')
                ->first();

            if (!$station) {
                return response()->json(['message' => 'Station not found.'], 404);
            }

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

            app(\App\Services\ScheduleService::class)->rebuildScheduleFrom(
                $booking->station_id,
                Carbon::parse($booking->end_time)
            );

            if ($booking->start_time) {
                SendSessionStartReminder::dispatch($booking->id)
                    ->delay($booking->start_time->subMinutes(30));
            }

            $charger->update(['status' => 'busy']);

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

    public function getSchedule(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'date' => 'nullable|date'
        ]);

        $uid = $request->uid;
        $date = $request->date ?? now()->toDateString();

        $startHour = 8;
        $endHour = 20;

        $slots = collect(range($startHour, $endHour))->map(function ($hour) use ($date) {
            $start = Carbon::parse("$date $hour:00:00");
            $end = Carbon::parse("$date $hour:59:59");
            return [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'status' => 'available'
            ];
        });

        $booked = Booking::where('UID', $uid)
            ->whereDate('start_time', $date)
            ->get();

        foreach ($slots as &$slot) {
            foreach ($booked as $booking) {
                $bookingStart = Carbon::parse($booking->start_time);
                $bookingEnd = Carbon::parse($booking->end_time);
                $slotStart = Carbon::parse($slot['start']);
                $slotEnd = Carbon::parse($slot['end']);

                if ($slotStart->between($bookingStart, $bookingEnd) || $slotEnd->between($bookingStart, $bookingEnd)) {
                    $slot['status'] = 'booked';
                    break;
                }
            }
        }

        return response()->json(['schedule' => $slots]);
    }
    public function create(Request $request)
    {
        $data = $request->validate([
            'station_id' => 'required|exists:charger_stations,id',
            'charger_id' => 'required|exists:chargers,id',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|in:60,90,120',
        ]);

        $user = $request->user();
        $start = Carbon::parse($data['start_time']);
        $end = $start->copy()->addMinutes($data['duration_minutes']);

        if (!$start->isSameDay(Carbon::now())) {
            return response()->json(['code' => 'ONLY_TODAY_ALLOWED'], 422);
        }

        $station = \App\Models\ChargerStation::findOrFail($data['station_id']);
        $workingStart = Carbon::parse($start->format('Y-m-d') . ' 08:00');
        $workingEnd = Carbon::parse($start->format('Y-m-d') . ' 20:00');
        if ($start < $workingStart || $end > $workingEnd) {
            return response()->json(['code' => 'OUT_OF_WORKING_HOURS'], 422);
        }

        $bufferMinutes = 5;
        $overlapExists = \App\Models\Booking::where('charger_id', $data['charger_id'])
            ->whereIn('status', ['pending', 'active'])
            ->where(function ($q) use ($start, $end, $bufferMinutes) {
                $q->where('start_time', '<', $end->copy()->addMinutes($bufferMinutes))
                    ->where('end_time', '>', $start->copy()->subMinutes($bufferMinutes));
            })->exists();

        if ($overlapExists) {
            return response()->json(['code' => 'TIME_CONFLICT'], 422);
        }

        $booking = \App\Models\Booking::create([
            'user_id' => $user->id,
            'station_id' => $data['station_id'],
            'charger_id' => $data['charger_id'],
            'UID' => \App\Models\Charger::find($data['charger_id'])->uid,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'BOOKING_CREATED',
            'booking' => [
                'id' => $booking->id,
                'station_id' => $booking->station_id,
                'charger_id' => $booking->charger_id,
                'uid' => $booking->UID,
                'start_time' => $booking->start_time->toIso8601String(),
                'end_time' => $booking->end_time->toIso8601String(),
                'status' => $booking->status,
            ]
        ], 201);
    }
}
