<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HardwareAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Hardware-Key');

        if (!$apiKey && $request->bearerToken()) {
            $apiKey = $request->bearerToken();
        }

        $validKey = config('hardware.api_key');

        if (!$apiKey || $apiKey !== $validKey) {
            Log::warning('Hardware API unauthorized access', [
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized hardware access'
            ], 401);
        }

        Log::channel('hardware')->info('Hardware API accessed', [
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
            'time' => now()
        ]);

        return $next($request);
    }
}
