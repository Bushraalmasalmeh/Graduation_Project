<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Charger;
use App\Models\ChargerStation;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'total_users'     => User::count(),
            'total_chargers'  => Charger::count(),
            'today_sessions'  => Booking::whereDate('start_time', today())->count(),
            'recent_bookings' => Booking::latest()->take(10)->get(),
        ]);
    }
    public function reportSummary()
    {
        $monthlyBookings = Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->get();

        $topUsers = User::withCount('bookings')
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $topStation = ChargerStation::withCount('bookings')
            ->orderByDesc('bookings_count')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'monthlyBookings' => $monthlyBookings,
                'topUsers' => $topUsers,
                'topStation' => $topStation,
            ]
        ]);
    }
}
