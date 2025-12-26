<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Charger;
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
}
