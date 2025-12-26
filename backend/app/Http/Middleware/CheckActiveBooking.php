<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Booking;

class CheckActiveBooking
{
    public function handle(Request $request, Closure $next)
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'No active booking found'
            ], 400);
        }

        // ✅ FIXED: Use attributes instead of merge
        $request->attributes->set('active_booking', $booking);

        return $next($request);
    }
}
