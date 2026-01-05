<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdminCreateBookingRequest;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller
{
    public function index()
    {
        return response()->json([
            'bookings' => Booking::with(['user', 'station', 'cabinet', 'charger'])
                ->orderByDesc('created_at')
                ->get()
        ]);
    }

    public function store(AdminCreateBookingRequest $request)
    {
        $data = $request->validated();

        $startTime = Carbon::parse($data['start_time'], 'Asia/Amman');
        $endTime = $startTime->copy()->addMinutes($data['duration_minutes']);

        // تحقق من التداخل الزمني لنفس المستخدم
        $overlap = Booking::where('user_id', $data['user_id'])
            ->whereIn('status', ['pending', 'active', 'confirmed'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->lockForUpdate()
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'User already has a booking at this time.',
                'code'    => 'USER_TIME_CONFLICT'
            ], 422);
        }

        // تحقق من ساعات العمل
        if ($startTime->hour < 7 || $startTime->hour >= 20) {
            return response()->json([
                "message" => "Booking must be between 07:00 AM and 08:00 PM.",
                "code"    => "OUT_OF_WORKING_HOURS"
            ], 422);
        }

        // إنشاء الحجز
        $booking = DB::transaction(function () use ($data, $startTime, $endTime) {
            return Booking::create([
                'user_id'    => $data['user_id'],
                'station_id' => $data['station_id'],
                'cabinet_id' => $data['cabinet_id'],
                'charger_id' => $data['charger_id'],
                'UID'        => $data['uid'],
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'duration'   => $data['duration_minutes'],
                'status'     => $data['status'],
            ]);
        });

        return response()->json([
            'message' => 'Booking_created_successfully_by_admin',
            'booking' => $booking
        ], 201);
    }

    public function cancel($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Optional: check if already cancelled
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking already cancelled'], 400);
        }

        $booking->status = 'cancelled';
        $booking->cancelled_by = 'admin';
        $booking->cancelled_at = now();
        $booking->save();

        return response()->json(['message' => 'Booking cancelled successfully']);
    }
}
