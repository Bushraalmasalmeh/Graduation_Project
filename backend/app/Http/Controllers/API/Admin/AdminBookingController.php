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

        $booking = DB::transaction(function () use ($data) {
            return Booking::create([
                'user_id'    => $data['user_id'],
                'station_id' => $data['station_id'],
                'cabinet_id' => $data['cabinet_id'],
                'charger_id' => $data['charger_id'],
                'UID'        => $data['uid'],
                'start_time' => $data['start_time'],
                'status'     => $data['status'],
                'duration' => $data['duration_minutes'],
                'end_time' => Carbon::parse($data['start_time'])
                    ->addMinutes($data['duration_minutes']),
            ]);
        });

        return response()->json([
            'message' => 'Booking_created_successfully_by_admin',
            'booking' => $booking
        ], 201);
    }
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);

        return response()->json(['status' => 'success', 'message' => 'Booking cancelled']);
    }
}
