<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }

        if ($user->role_type !== 'admin') {
            return response()->json([
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
