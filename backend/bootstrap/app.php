<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'check.user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'hardware.auth' => \App\Http\Middleware\HardwareAuth::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'validate.uid' => \App\Http\Middleware\ValidateUID::class,
            'validateStartSession' => \App\Http\Middleware\ValidateStartSession::class
        ]);

        // لا تضع أي شيء آخر هنا
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('sessions:close-timeouts')->everyMinute();
    })
    ->create();
