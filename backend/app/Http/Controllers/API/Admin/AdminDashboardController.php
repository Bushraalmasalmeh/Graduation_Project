<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Charger;
use App\Models\ChargerStation;
use App\Models\UsageSession;
use Carbon\Carbon;
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
        $totalUsage = UsageSession::sum('duration');
        $stationCount = ChargerStation::count();
        $totalCapacity = $stationCount * 13 * 30;
        $overallUsage = $totalCapacity > 0 ? round(($totalUsage / $totalCapacity) * 100, 2) : 0;

        $topStation = ChargerStation::withCount('bookings')
            ->orderByDesc('bookings_count')
            ->first();

        $topUsers = User::withCount('bookings')
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $monthlyBookings = Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->get();

        $stationUsage = ChargerStation::with(['usageSessions' => function ($q) {
            $q->selectRaw('station_id, SUM(duration) as total_usage')->groupBy('station_id');
        }])->get()->map(function ($station) {
            $usage = $station->usageSessions->sum('duration');
            return [
                'station_name' => $station->station_name,
                'usage_hours' => $usage,
            ];
        });



        return response()->json([
            'status' => 'success',
            'data' => [
                'totalUsage' => $totalUsage,
                'totalCapacity' => $totalCapacity,
                'overallUsage' => $overallUsage,
                'topStation' => $topStation,
                'topUsers' => $topUsers,
                'monthlyBookings' => $monthlyBookings,
                'stationUsage' => $stationUsage,
            ]
        ]);
    }
}
