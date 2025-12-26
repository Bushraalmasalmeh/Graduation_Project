<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $maintenanceMode = Cache::remember('maintenance_mode', 300, function () {
            return Setting::first()?->maintenance_mode ?? false;
        });

        if ($maintenanceMode) {
            return response()->json(['message' => 'system_under_maintenance'], 503);
        }

        return $next($request);
    }
}
